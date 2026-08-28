<?php

declare(strict_types=1);

namespace Smithy\Tests\Http\Client\Curl;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Async\EventLoopInterface;
use Smithy\Async\RevoltEventLoop;
use Smithy\Http\Client\Cancellation\{
    Cancellation,
    CancelledException
};
use Smithy\Http\Client\Configuration\{
    ConnectConfiguration,
    ConnectionManagerConfiguration
};
use Smithy\Http\Client\Connection\{
    ConnectionFactoryInterface,
    ConnectionState,
    HttpConnectionInterface,
    RequestContext
};
use Smithy\Http\Client\Curl\{
    CurlConnection,
    CurlConnectionManager
};
use Smithy\Http\Client\Exception\ConnectionAcquireTimeoutException;
use Smithy\Tests\Http\Client\Curl\Utils\{
    NonCurlConnectionFixture,
    PollCountingEventLoopFixture,
    TestConnectionFactoryFixture
};

#[CoversClass(CurlConnectionManager::class)]
final class CurlConnectionManagerTest extends TestCase
{
    protected function setUp(): void
    {
        if (!\extension_loaded('curl')) {
            self::markTestSkipped('The cURL extension is required.');
        }
    }

    public function testWaitersAcquirePermitsInFifoOrder(): void
    {
        $context = $this->createRequestContext();
        $eventLoop = new RevoltEventLoop();
        $manager = $this->createManager(
            maxConcurrency: 1,
            eventLoop: $eventLoop
        );
        $first = $manager->lease($context);
        $order = [];
        $activeWorkers = 0;
        $maxActiveWorkers = 0;

        $eventLoop->run([
            function () use (
                $manager,
                $context,
                $eventLoop,
                &$order,
                &$activeWorkers,
                &$maxActiveWorkers
            ): void
            {
                $exchange = $manager->lease($context);
                $order[] = 'second';
                $activeWorkers++;
                $maxActiveWorkers = \max(
                    $maxActiveWorkers,
                    $activeWorkers
                );
                $eventLoop->delay(0.01);
                $activeWorkers--;
                $manager->release($exchange);
            },
            function () use (
                $manager,
                $context,
                &$order,
                &$activeWorkers,
                &$maxActiveWorkers
            ): void
            {
                $exchange = $manager->lease($context);
                $order[] = 'third';
                $activeWorkers++;
                $maxActiveWorkers = \max(
                    $maxActiveWorkers,
                    $activeWorkers
                );
                $activeWorkers--;
                $manager->release($exchange);
            },
            function () use (
                $manager,
                $first,
                $eventLoop
            ): void
            {
                $eventLoop->delay(0.01);
                $manager->release($first);
            }
        ]);

        $this->assertSame(['second', 'third'], $order);
        $this->assertSame(1, $maxActiveWorkers);
        $this->assertSame(0, $manager->activeCount(
            (string) $context->cacheKey()
        ));
    }

    public function testWaiterClaimsCapacityReleasedBeforeRegistration(): void
    {
        $context = $this->createRequestContext();
        $eventLoop = new RevoltEventLoop();
        $manager = $this->createManager(
            maxConcurrency: 1,
            acquireTimeout: 0.01,
            eventLoop: $eventLoop
        );
        $first = $manager->lease($context);
        $acquired = false;

        $eventLoop->run([
            function () use (
                $manager,
                $context,
                &$acquired
            ): void
            {
                $exchange = $manager->lease($context);
                $acquired = true;
                $manager->release($exchange);
            },
            static function () use (
                $manager,
                $first
            ): void
            {
                $manager->release($first);
            }
        ]);

        $this->assertTrue($acquired);
        $this->assertSame(0, $manager->activeCount(
            (string) $context->cacheKey()
        ));
    }

    public function testWaiterTimesOutWithoutLeakingPermit(): void
    {
        $context = new RequestContext(
            'https',
            'example.com',
            443,
            'http://user:secret@proxy.example:8080'
        );
        $eventLoop = new PollCountingEventLoopFixture();
        $manager = $this->createManager(
            maxConcurrency: 1,
            acquireTimeout: 0.01,
            eventLoop: $eventLoop
        );
        $first = $manager->lease($context);

        try {
            $eventLoop->run([
                static fn() => $manager->lease($context)
            ]);
            $this->fail('Expected connection acquisition to time out.');
        } catch (ConnectionAcquireTimeoutException $error) {
            $this->assertStringContainsString(
                'Timed out after 0.01s',
                $error->getMessage()
            );
            $this->assertStringContainsString(
                "to 'example.com'",
                $error->getMessage()
            );
            $this->assertStringNotContainsString(
                'user:secret',
                $error->getMessage()
            );
        }

        $this->assertSame(1, $eventLoop->scheduleCalls);
        $this->assertSame(1, $manager->activeCount(
            (string) $context->cacheKey()
        ));
        $manager->release($first);
        $this->assertSame(0, $manager->activeCount(
            (string) $context->cacheKey()
        ));
    }

    public function testZeroTimeoutWaitsUntilPermitIsAvailable(): void
    {
        $context = $this->createRequestContext();
        $eventLoop = new RevoltEventLoop();
        $manager = $this->createManager(
            maxConcurrency: 1,
            acquireTimeout: 0.0,
            eventLoop: $eventLoop
        );
        $first = $manager->lease($context);
        $acquired = false;

        $eventLoop->run([
            function () use (
                $manager,
                $context,
                &$acquired
            ): void
            {
                $exchange = $manager->lease($context);
                $acquired = true;
                $manager->release($exchange);
            },
            function () use (
                $manager,
                $first,
                $eventLoop
            ): void
            {
                $eventLoop->delay(0.01);
                $manager->release($first);
            }
        ]);

        $this->assertTrue($acquired);
    }

    public function testFactoryFailureReleasesPermit(): void
    {
        $context = $this->createRequestContext();
        $calls = 0;
        $factory = new TestConnectionFactoryFixture(
            function (RequestContext $requestContext) use (
                &$calls
            ): HttpConnectionInterface
            {
                if ($calls++ === 0) {
                    throw new \RuntimeException('Connection failed.');
                }

                return $this->createConnection($requestContext);
            }
        );
        $manager = $this->createManager(
            maxConcurrency: 1,
            factory: $factory
        );

        try {
            $manager->lease($context);
            $this->fail('Expected connection creation to fail.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Connection failed.', $error->getMessage());
        }

        $this->assertSame(0, $manager->activeCount(
            (string) $context->cacheKey()
        ));

        $exchange = $manager->lease($context);
        $this->assertSame(1, $manager->activeCount(
            (string) $context->cacheKey()
        ));
        $manager->release($exchange);
    }

    public function testRejectsNonCurlFactoryConnection(): void
    {
        $context = $this->createRequestContext();
        $connection = new NonCurlConnectionFixture($context);
        $factory = new TestConnectionFactoryFixture(
            static fn(
                RequestContext $requestContext,
                ConnectConfiguration $config
            ): HttpConnectionInterface => $connection
        );
        $manager = $this->createManager(
            maxConcurrency: 1,
            factory: $factory
        );

        try {
            $manager->lease($context);
            $this->fail('Expected connection validation to fail.');
        } catch (\LogicException $error) {
            $this->assertSame(
                'CurlConnectionManager requires factories to create '
                    . 'CurlConnection instances.',
                $error->getMessage()
            );
        }

        $this->assertSame(ConnectionState::CLOSED, $connection->state());
        $this->assertSame(0, $manager->activeCount(
            (string) $context->cacheKey()
        ));
    }

    public function testCancellationRemovesWaitingFiber(): void
    {
        $context = $this->createRequestContext();
        $eventLoop = new RevoltEventLoop();
        $manager = $this->createManager(
            maxConcurrency: 1,
            eventLoop: $eventLoop
        );
        $first = $manager->lease($context);
        $cancellation = new Cancellation();
        $cancelled = false;

        $eventLoop->run([
            function () use (
                $manager,
                $context,
                $cancellation,
                &$cancelled
            ): void
            {
                try {
                    $manager->lease(
                        $context,
                        cancellation: $cancellation
                    );
                } catch (CancelledException) {
                    $cancelled = true;
                }
            },
            function () use (
                $eventLoop,
                $cancellation
            ): void
            {
                $eventLoop->delay(0.01);
                $cancellation->cancel();
            }
        ]);

        $this->assertTrue($cancelled);
        $this->assertSame(1, $manager->activeCount(
            (string) $context->cacheKey()
        ));
        $manager->release($first);
        $this->assertSame(0, $manager->activeCount(
            (string) $context->cacheKey()
        ));
    }

    public function testZeroIdleLimitsDisablePooling(): void
    {
        $context = $this->createRequestContext();
        $connection = null;
        $factory = new TestConnectionFactoryFixture(
            function (RequestContext $requestContext) use (
                &$connection
            ): HttpConnectionInterface
            {
                return $connection = $this->createConnection(
                    $requestContext
                );
            }
        );
        $manager = new CurlConnectionManager(
            new ConnectionManagerConfiguration(
                maxConcurrency: 1,
                maxIdlePerHost: 0,
                maxIdleTotal: 0
            ),
            $factory
        );

        $exchange = $manager->lease($context);
        $manager->release($exchange);

        $this->assertSame(0, $manager->idleCount());
        $this->assertInstanceOf(CurlConnection::class, $connection);
        $this->assertSame(ConnectionState::CLOSED, $connection->state());
    }

    public function testRejectsDoubleRelease(): void
    {
        $context = $this->createRequestContext();
        $manager = $this->createManager(maxConcurrency: 1);
        $exchange = $manager->lease($context);

        $manager->release($exchange);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Cannot release a connection that is not currently leased.'
        );

        $manager->release($exchange);
    }

    public function testClearPreservesInflightPermitsAndClosesOnRelease(): void
    {
        $context = $this->createRequestContext();
        $connections = [];
        $factory = new TestConnectionFactoryFixture(
            function (RequestContext $requestContext) use (
                &$connections
            ): HttpConnectionInterface
            {
                $connection = $this->createConnection($requestContext);
                $connections[] = $connection;

                return $connection;
            }
        );
        $manager = $this->createManager(
            maxConcurrency: 1,
            acquireTimeout: 0.01,
            factory: $factory
        );
        $first = $manager->lease($context);

        $manager->clear();

        $this->assertSame(1, $manager->activeCount(
            (string) $context->cacheKey()
        ));

        $timedOut = false;
        try {
            (new RevoltEventLoop())->run([
                static fn() => $manager->lease($context)
            ]);
            $this->fail('Expected connection acquisition to time out.');
        } catch (ConnectionAcquireTimeoutException) {
            $timedOut = true;
        }

        $this->assertTrue($timedOut);
        $manager->release($first);

        $this->assertSame(0, $manager->activeCount(
            (string) $context->cacheKey()
        ));
        $this->assertSame(0, $manager->idleCount());
        $this->assertSame(ConnectionState::CLOSED, $connections[0]->state());

        $second = $manager->lease($context);

        $this->assertNotSame($connections[0], $second->getConnection());
        $this->assertSame(2, \count($connections));
        $manager->release($second);
    }

    public function testPruneDropsConnectionGenerationEntries(): void
    {
        $context = $this->createRequestContext();
        $connection = null;
        $factory = new TestConnectionFactoryFixture(
            function (RequestContext $requestContext) use (
                &$connection
            ): HttpConnectionInterface
            {
                return $connection = $this->createConnection(
                    $requestContext
                );
            }
        );
        $manager = new CurlConnectionManager(
            new ConnectionManagerConfiguration(
                maxConcurrency: 1,
                idleTimeout: 0.000001
            ),
            $factory
        );

        $exchange = $manager->lease($context);
        $manager->release($exchange);
        self::assertInstanceOf(CurlConnection::class, $connection);
        $connectionId = \spl_object_id($connection);
        \usleep(1000);

        $manager->prune();

        $this->assertSame(0, $manager->idleCount());
        $this->assertSame(ConnectionState::CLOSED, $connection->state());
        $this->assertArrayNotHasKey(
            $connectionId,
            $this->connectionGenerations($manager)
        );
    }

    private function createManager(
        int $maxConcurrency,
        float $acquireTimeout = 1.0,
        ?ConnectionFactoryInterface $factory = null,
        ?EventLoopInterface $eventLoop = null
    ): CurlConnectionManager
    {
        $factory ??= new TestConnectionFactoryFixture(
            fn(RequestContext $context): HttpConnectionInterface =>
                $this->createConnection($context)
        );

        return new CurlConnectionManager(
            new ConnectionManagerConfiguration(
                maxConcurrency: $maxConcurrency,
                connectionAcquireTimeout: $acquireTimeout
            ),
            $factory,
            $eventLoop
        );
    }

    private function createRequestContext(): RequestContext
    {
        return new RequestContext('https', 'example.com', 443);
    }

    private function createConnection(
        RequestContext $context
    ): CurlConnection
    {
        return new CurlConnection(\curl_init(), $context);
    }

    /**
     * Read tracked connection generations from the manager.
     *
     * @return array<int, int>
     */
    private function connectionGenerations(
        CurlConnectionManager $manager
    ): array
    {
        $property = new \ReflectionProperty(
            CurlConnectionManager::class,
            'connectionGenerations'
        );

        return $property->getValue($manager);
    }
}
