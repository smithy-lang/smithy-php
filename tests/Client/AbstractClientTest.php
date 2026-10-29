<?php

declare(strict_types=1);

namespace Smithy\Tests\Client;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\{
    AbstractClient,
    ClientConfig,
    RequestOverrideConfig
};
use Smithy\Client\Endpoint\{
    Endpoint,
    StaticEndpointResolver
};
use Smithy\Client\Interceptor\{
    AbstractInterceptor,
    CallContext,
    HttpChecksumInterceptor,
    IdempotencyTokenInterceptor,
    InputContext,
    InterceptorChain,
    RequestCompressionInterceptor
};
use Smithy\Client\Plugin\{
    AbstractPlugin,
    HttpChecksumPlugin,
    IdempotencyTokenPlugin,
    PluginCollection,
    PluginInterface
};
use Smithy\Client\Protocol\ProtocolResolver;
use Smithy\Core\Serde\{
    Schema,
    SerializableStructInterface
};
use Smithy\Core\Shape\{
    OperationInterface,
    ShapeType
};
use Smithy\Tests\Client\Fake\{
    FakeInput,
    FakeOperation,
    FakeOutput,
    FakeProtocol,
    FakeService,
    TestClientFixture
};
use Smithy\Tests\Client\Waiter\Generated\Waiter\Operation\GetThingOperation;
use Smithy\Tests\Client\Plugin\Utils\{
    RecordingPluginFixture,
    RegisteringPluginFixture,
    SecondaryRecordingPluginFixture
};
use Smithy\Tests\Utils\Http\MockHttpClient;

#[CoversClass(AbstractClient::class)]
final class AbstractClientTest extends TestCase
{
    public function testReturnsConfiguredClientConfig(): void
    {
        $config = $this->config();
        $client = new TestClientFixture($config);

        self::assertSame(
            $config->getProtocol(),
            $client->getConfig()->getProtocol()
        );
        self::assertTrue(
            $client->getConfig()->interceptorChain->contains(
                IdempotencyTokenInterceptor::class
            )
        );
    }

    public function testResolvesModeledProtocolDuringConstruction(): void
    {
        $config = new ClientConfig(
            transport: new \Smithy\Http\Client\HttpClientTransport(
                new MockHttpClient()
            ),
            endpointResolver: new StaticEndpointResolver(
                new Endpoint('https://example.test')
            ),
            protocolResolver: new ProtocolResolver([
                FakeProtocol::ID => $protocol = new FakeProtocol()
            ])
        );

        $client = new TestClientFixture($config);

        self::assertSame($protocol, $client->getConfig()->getProtocol());
        self::assertSame(FakeService::instance(), $client->getConfig()->service);
    }

    public function testPluginPredicateCanRejectDefaultChild(): void
    {
        $config = $this->config()->withPluginPredicate(
            static fn(PluginInterface $plugin): bool =>
                !$plugin instanceof IdempotencyTokenPlugin
        );
        $client = new TestClientFixture($config);

        self::assertFalse(
            $client->getConfig()->interceptorChain->contains(
                IdempotencyTokenInterceptor::class
            )
        );
    }

    public function testRegistersModeledHttpBehavior(): void
    {
        $chain = (new TestClientFixture($this->config()))
            ->getConfig()
            ->interceptorChain;

        self::assertTrue(
            $chain->contains(RequestCompressionInterceptor::class)
        );
        self::assertTrue($chain->contains(HttpChecksumInterceptor::class));
    }

    public function testDerivedConfigCanAcceptRejectedModeledBehavior(): void
    {
        $first = new TestClientFixture(
            $this->config()->withPluginPredicate(
                static fn(PluginInterface $plugin): bool =>
                    !$plugin instanceof HttpChecksumPlugin
            )
        );
        self::assertFalse(
            $first->getConfig()->interceptorChain->contains(
                HttpChecksumInterceptor::class
            )
        );

        $second = new TestClientFixture(
            $first->getConfig()->withPluginPredicate(
                static fn(PluginInterface $plugin): bool => true
            )
        );

        self::assertTrue(
            $second->getConfig()->interceptorChain->contains(
                HttpChecksumInterceptor::class
            )
        );
    }

    public function testAppliesPluginsInOrder(): void
    {
        $calls = new \ArrayObject();
        $first = new RecordingPluginFixture('first', $calls);
        $second = new SecondaryRecordingPluginFixture('second', $calls);

        new TestClientFixture(
            $this->config(),
            new PluginCollection($first, $second)
        );

        self::assertSame(['first', 'second'], $calls->getArrayCopy());
    }

    public function testAppliesPluginRegisteredDuringConstruction(): void
    {
        $calls = new \ArrayObject();
        $child = new RecordingPluginFixture('child', $calls);
        $parent = new RegisteringPluginFixture('parent', $calls, $child);
        $sibling = new SecondaryRecordingPluginFixture('sibling', $calls);

        new TestClientFixture(
            $this->config(),
            new PluginCollection($parent, $sibling)
        );

        self::assertSame(
            ['parent', 'sibling', 'child'],
            $calls->getArrayCopy()
        );
    }

    public function testDefersPluginsUntilServiceIsAttached(): void
    {
        $service = FakeService::instance();
        $observed = new \ArrayObject();
        $plugin = new class($observed) extends AbstractPlugin {
            public function __construct(
                private readonly \ArrayObject $observed
            ) {}

            public function configure(
                ClientConfig $config
            ): ClientConfig {
                $this->observed['service'] = $config->service;

                return $config;
            }
        };
        $config = $this->config()->withPlugin($plugin);

        self::assertArrayNotHasKey('service', $observed);

        $client = new TestClientFixture(
            $config->withService($service)
        );

        self::assertSame($service, $observed['service']);
        self::assertSame($service, $client->getConfig()->service);
    }

    public function testExecutesOperationsThroughPipeline(): void
    {
        $client = new TestClientFixture($this->config('result'));

        $output = $client->executeOperation(
            FakeOperation::instance(),
            new FakeInput('request')
        );

        self::assertInstanceOf(FakeOutput::class, $output);
        self::assertSame('result', $output->value);
    }

    public function testCallInterceptorCanShortCircuitPipeline(): void
    {
        $http = (new MockHttpClient())->willReturn('transport');
        $interceptor = new class extends AbstractInterceptor {
            public ?\Smithy\Client\ClientInterface $client = null;

            public function interceptCalls(): bool
            {
                return true;
            }

            public function interceptCall(
                InputContext $ctx,
                callable $next
            ): SerializableStructInterface
            {
                $this->client = $ctx->client;

                return new FakeOutput('short-circuited');
            }
        };
        $client = new TestClientFixture(
            $this->config(http: $http, interceptors: new InterceptorChain(
                $interceptor
            ))
        );

        $output = $client->executeOperation(
            FakeOperation::instance(),
            new FakeInput('request')
        );

        self::assertSame('short-circuited', $output->value);
        self::assertSame($client, $interceptor->client);
        self::assertSame(0, $http->sendCallCount);
    }

    public function testCallInterceptorCanSubstituteInput(): void
    {
        $http = (new MockHttpClient())->willReturn('transport');
        $interceptor = new class extends AbstractInterceptor {
            public function interceptCalls(): bool
            {
                return true;
            }

            public function interceptCall(
                InputContext $ctx,
                callable $next
            ): SerializableStructInterface
            {
                return $next(
                    $ctx->withInput(new FakeInput('substituted'))
                );
            }
        };
        $client = new TestClientFixture(
            $this->config(http: $http, interceptors: new InterceptorChain(
                $interceptor
            ))
        );

        $output = $client->executeOperation(
            FakeOperation::instance(),
            new FakeInput('original')
        );

        self::assertSame('transport', $output->value);
        self::assertSame(
            'input:substituted',
            (string) $http->sentRequests[0]->getBody()
        );
    }

    public function testRequestPluginAppliesOncePerCall(): void
    {
        $calls = new \ArrayObject();
        $plugin = new RecordingPluginFixture('request', $calls);
        $http = (new MockHttpClient())->fallbackResponse('result');
        $client = new TestClientFixture($this->config(http: $http));
        $overrides = RequestOverrideConfig::fromArray([])
            ->withPlugin($plugin);

        $client->executeOperation(
            FakeOperation::instance(),
            new FakeInput('first'),
            $overrides
        );
        $client->executeOperation(
            FakeOperation::instance(),
            new FakeInput('second'),
            $overrides
        );

        self::assertSame(
            ['request', 'request'],
            $calls->getArrayCopy()
        );
    }

    public function testConstructionPluginIsNotReappliedForRequest(): void
    {
        $calls = new \ArrayObject();
        $construction = new RecordingPluginFixture(
            'construction',
            $calls
        );
        $request = new RecordingPluginFixture('request', $calls);
        $client = new TestClientFixture(
            $this->config(),
            new PluginCollection($construction)
        );

        $client->executeOperation(
            FakeOperation::instance(),
            new FakeInput(),
            RequestOverrideConfig::fromArray([])->withPlugin($request)
        );

        self::assertSame(['construction'], $calls->getArrayCopy());
    }

    public function testRequestPluginCanSwitchProtocolForOneCall(): void
    {
        $http = (new MockHttpClient())->fallbackResponse('result');
        $alternate = new FakeProtocol(requestPrefix: 'alternate:');
        $plugin = new class($alternate) extends AbstractPlugin {
            public function __construct(
                private readonly FakeProtocol $protocol
            ) {}

            public function configure(
                ClientConfig $config
            ): ClientConfig {
                return $config->withProtocol($this->protocol);
            }
        };
        $client = new TestClientFixture($this->config(http: $http));

        $client->executeOperation(
            FakeOperation::instance(),
            new FakeInput('request'),
            RequestOverrideConfig::fromArray([])->withPlugin($plugin)
        );
        $client->executeOperation(
            FakeOperation::instance(),
            new FakeInput('base')
        );

        self::assertSame(
            'alternate:request',
            (string) $http->sentRequests[0]->getBody()
        );
        self::assertSame(
            'input:base',
            (string) $http->sentRequests[1]->getBody()
        );
    }

    public function testModifyBeforeCallCanSwitchProtocol(): void
    {
        $http = (new MockHttpClient())->willReturn('result');
        $alternate = new FakeProtocol(requestPrefix: 'modified:');
        $interceptor = new class($alternate) extends AbstractInterceptor {
            public function __construct(
                private readonly FakeProtocol $protocol
            ) {}

            public function modifyBeforeCall(
                CallContext $ctx
            ): ClientConfig {
                return $ctx->config->withProtocol($this->protocol);
            }
        };
        $client = new TestClientFixture(
            $this->config(
                http: $http,
                interceptors: new InterceptorChain($interceptor)
            )
        );

        $client->executeOperation(
            FakeOperation::instance(),
            new FakeInput('request')
        );

        self::assertSame(
            'modified:request',
            (string) $http->sentRequests[0]->getBody()
        );
    }

    public function testModifyBeforeCallCanInstallCallWrapper(): void
    {
        $wrapper = new class extends AbstractInterceptor {
            public int $modifyCalls = 0;

            public function modifyBeforeCall(
                CallContext $ctx
            ): ClientConfig {
                ++$this->modifyCalls;

                return $ctx->config;
            }

            public function interceptCalls(): bool
            {
                return true;
            }

            public function interceptCall(
                InputContext $ctx,
                callable $next
            ): SerializableStructInterface {
                return new FakeOutput('wrapped');
            }
        };
        $installer = new class($wrapper) extends AbstractInterceptor {
            public function __construct(
                private readonly AbstractInterceptor $wrapper
            ) {}

            public function modifyBeforeCall(
                CallContext $ctx
            ): ClientConfig {
                return $ctx->config->withInterceptor($this->wrapper);
            }
        };
        $client = new TestClientFixture(
            $this->config(
                interceptors: new InterceptorChain($installer)
            )
        );

        $output = $client->executeOperation(
            FakeOperation::instance(),
            new FakeInput()
        );

        self::assertSame('wrapped', $output->value);
        self::assertSame(0, $wrapper->modifyCalls);
    }

    public function testCreateWaiterRejectsOperationWithoutWaiters(): void
    {
        $client = new TestClientFixture($this->config());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Operation smithy.tests#Fake has no modeled waiters.'
        );

        $client->createWaiterForTest(
            FakeOperation::instance(),
            'Missing'
        );
    }

    public function testCreateWaiterRejectsUnknownWaiterName(): void
    {
        $client = new TestClientFixture($this->config());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'does not define waiter Missing'
        );

        $client->createWaiterForTest(
            GetThingOperation::instance(),
            'Missing'
        );
    }

    public function testCreateWaiterRejectsOutputMatcherWithoutOutput(): void
    {
        $operation = $this->createMock(OperationInterface::class);
        $operation->method('getId')->willReturn('example#NoOutput');
        $operation->method('getSchema')->willReturn(new Schema(
            id: 'example#NoOutput',
            type: ShapeType::Operation,
            traits: [
                Schema::T_WAITABLE => [
                    'Ready' => [
                        'acceptors' => [[
                            'state' => 'success',
                            'matcher' => [
                                'output' => [
                                    'path' => 'status',
                                    'expected' => 'ready',
                                    'comparator' => 'stringEquals'
                                ]
                            ]
                        ]]
                    ]
                ]
            ]
        ));
        $operation->method('getOutputSchema')->willReturn(new Schema(
            id: 'smithy.api#Unit',
            type: ShapeType::Structure
        ));
        $client = new TestClientFixture($this->config());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'output matchers require an operation output'
        );

        $client->createWaiterForTest($operation, 'Ready');
    }

    private function config(
        string $response = 'response',
        ?MockHttpClient $http = null,
        ?InterceptorChain $interceptors = null
    ): ClientConfig
    {
        return new ClientConfig(
            protocol: new FakeProtocol(),
            transport: new \Smithy\Http\Client\HttpClientTransport(
                $http ?? (new MockHttpClient())->willReturn($response)
            ),
            endpointResolver: new StaticEndpointResolver(
                new Endpoint('https://example.test')
            ),
            interceptors: $interceptors ?? new InterceptorChain()
        );
    }
}
