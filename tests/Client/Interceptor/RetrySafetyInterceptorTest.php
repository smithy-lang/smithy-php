<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Interceptor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Async\{
    Async,
    EventLoopInterface
};
use Smithy\Client\ClientConfig;
use Smithy\Client\ClientPipeline;
use Smithy\Client\Endpoint\StaticEndpointResolver;
use Smithy\Client\Interceptor\{
    AbstractInterceptor,
    InterceptorChain,
    OutputContext,
    RetrySafetyInterceptor
};
use Smithy\Client\Plugin\{
    DefaultPlugin,
    RetrySafetyPlugin
};
use Smithy\Client\Retry\StandardRetryStrategy;
use Smithy\Core\Endpoint\Endpoint;
use Smithy\Core\Exception\{
    ApiException,
    ErrorFault
};
use Smithy\Core\Retry\RetrySafety;
use Smithy\Core\Serde\{
    Schema,
    SerializableStructInterface
};
use Smithy\Core\Shape\{
    OperationInterface,
    ShapeType
};
use Smithy\Tests\Async\Utils\RecordingEventLoopFixture;
use Smithy\Tests\Client\Fake\{
    FakeInput,
    FakeOperation,
    FakeOutput,
    FakeProtocol
};
use Smithy\Tests\Utils\Http\MockHttpClient;

#[CoversClass(RetrySafetyInterceptor::class)]
#[CoversClass(RetrySafetyPlugin::class)]
final class RetrySafetyInterceptorTest extends TestCase
{
    private EventLoopInterface $previousEventLoop;

    protected function setUp(): void
    {
        $this->previousEventLoop = Async::eventLoop();
        Async::configure(new RecordingEventLoopFixture());
    }

    protected function tearDown(): void
    {
        Async::configure($this->previousEventLoop);
    }

    /**
     * @return array<string, array{int|null}>
     */
    public static function retrySafeTraits(): array
    {
        return [
            'readonly' => [Schema::T_READ_ONLY],
            'idempotent' => [Schema::T_IDEMPOTENT],
        ];
    }

    #[DataProvider('retrySafeTraits')]
    public function testPromotesMaybeForRetrySafeOperation(int $trait): void
    {
        $error = new ApiException('ambiguous', ErrorFault::Other);

        $this->complete($this->operation([$trait => true]), $error);

        self::assertSame(RetrySafety::Yes, $error->getRetrySafety());
        self::assertSame(2, $this->refreshAttempts($error));
    }

    public function testLeavesMaybeForOtherOperations(): void
    {
        $error = new ApiException('ambiguous', ErrorFault::Other);

        $this->complete($this->operation([]), $error);

        self::assertSame(RetrySafety::Maybe, $error->getRetrySafety());
        self::assertSame(1, $this->refreshAttempts($error));
    }

    public function testNeverFlipsNo(): void
    {
        $error = new ApiException('client', ErrorFault::Client);

        $this->complete(
            $this->operation([Schema::T_READ_ONLY => true]),
            $error
        );

        self::assertSame(RetrySafety::No, $error->getRetrySafety());
        self::assertSame(1, $this->refreshAttempts($error));
    }

    public function testLeavesYesUnchanged(): void
    {
        $error = new ApiException('server', ErrorFault::Server);

        $this->complete(
            $this->operation([Schema::T_IDEMPOTENT => true]),
            $error
        );

        self::assertSame(RetrySafety::Yes, $error->getRetrySafety());
    }

    public function testPromotesWrappedError(): void
    {
        $inner = new ApiException('ambiguous', ErrorFault::Other);

        $this->complete(
            $this->operation([Schema::T_READ_ONLY => true]),
            new \RuntimeException('wrapper', 0, $inner)
        );

        self::assertSame(RetrySafety::Yes, $inner->getRetrySafety());
    }

    public function testIgnoresNonApiRetryInfo(): void
    {
        $error = new \RuntimeException('plain');

        $this->complete(
            $this->operation([Schema::T_READ_ONLY => true]),
            $error
        );

        self::assertSame(1, $this->refreshAttempts($error));
    }

    public function testAppliesOnlyToRetrySafeOperations(): void
    {
        $interceptor = new RetrySafetyInterceptor();

        self::assertTrue($interceptor->appliesTo(
            $this->operation([Schema::T_READ_ONLY => true])
        ));
        self::assertTrue($interceptor->appliesTo(
            $this->operation([Schema::T_IDEMPOTENT => true])
        ));
        self::assertFalse($interceptor->appliesTo(FakeOperation::instance()));
    }

    public function testSuccessIsPassedThrough(): void
    {
        $output = new FakeOutput('ok');
        $ctx = $this->context(
            $this->operation([Schema::T_READ_ONLY => true]),
            $output
        );

        self::assertSame(
            $output,
            (new RetrySafetyInterceptor())
                ->modifyBeforeAttemptCompletion($ctx, null)
        );
    }

    public function testDefaultPluginRegistersInterceptor(): void
    {
        $config = $this->config([]);
        $config = $config->withPluginCollection(
            new \Smithy\Client\Plugin\PluginCollection(
                DefaultPlugin::instance()
            )
        )->resolvePlugins();

        self::assertTrue($config->interceptorChain->contains(
            RetrySafetyInterceptor::class
        ));
    }

    public function testRetriesMaybeFailureOfReadOnlyOperation(): void
    {
        $http = $this->failingHttp();
        $pipeline = $this->pipeline($http, new RetrySafetyInterceptor());

        $output = $pipeline->execute(
            $this->operation([Schema::T_READ_ONLY => true]),
            new FakeInput()
        );

        self::assertSame(2, $http->sendCallCount);
        self::assertSame('server-body', $output->value);
    }

    public function testDoesNotRetryMaybeFailureOfOtherOperation(): void
    {
        $http = $this->failingHttp();
        $pipeline = $this->pipeline($http, new RetrySafetyInterceptor());

        try {
            $pipeline->execute($this->operation([]), new FakeInput());
            self::fail('Expected the failure to surface.');
        } catch (ApiException $e) {
            self::assertSame(RetrySafety::Maybe, $e->getRetrySafety());
        }

        self::assertSame(1, $http->sendCallCount);
    }

    public function testWithoutInterceptorMaybeIsNotRetried(): void
    {
        $http = $this->failingHttp();
        $pipeline = $this->pipeline($http, null);

        try {
            $pipeline->execute(
                $this->operation([Schema::T_READ_ONLY => true]),
                new FakeInput()
            );
            self::fail('Expected the failure to surface.');
        } catch (ApiException) {
        }

        self::assertSame(1, $http->sendCallCount);
    }

    /**
     * Run the interceptor, then the retry strategy, as the pipeline does.
     *
     * @param array<int, mixed> $traits
     */
    private function complete(
        OperationInterface $operation,
        \Throwable $error
    ): void
    {
        try {
            (new RetrySafetyInterceptor())->modifyBeforeAttemptCompletion(
                $this->context($operation, null),
                $error
            );
            self::fail('Expected the error to be rethrown.');
        } catch (\Throwable $e) {
            self::assertSame($error, $e);
        }
    }

    /**
     * Return the attempt number after asking the strategy to refresh.
     */
    private function refreshAttempts(\Throwable $error): int
    {
        $strategy = new StandardRetryStrategy(baseDelay: 0.0);
        $state = $strategy->begin('s');
        try {
            $strategy->refresh($state, $error);
        } catch (\Smithy\Client\Retry\TokenAcquisitionFailedException) {
        }

        return $state->attempt;
    }

    /**
     * @param array<int, mixed> $traits
     */
    private function operation(array $traits): OperationInterface
    {
        $operation = $this->createStub(OperationInterface::class);
        $operation->method('getName')->willReturn('Fake');
        $operation->method('getEffectiveAuthSchemes')->willReturn([
            'smithy.api#noAuth'
        ]);
        $operation->method('getSchema')->willReturn(new Schema(
            id: 'smithy.tests#Fake',
            type: ShapeType::Operation,
            traits: $traits
        ));

        return $operation;
    }

    private function context(
        OperationInterface $operation,
        ?SerializableStructInterface $output
    ): OutputContext
    {
        return new OutputContext(
            $operation,
            new FakeInput(),
            $this->config([]),
            null,
            null,
            $output
        );
    }

    /**
     * @param list<AbstractInterceptor> $interceptors
     */
    private function config(array $interceptors): ClientConfig
    {
        return new ClientConfig(
            protocol: new FakeProtocol(),
            transport: new \Smithy\Http\Client\HttpClientTransport(
                new MockHttpClient()
            ),
            endpointResolver: new StaticEndpointResolver(
                new Endpoint('https://example.test')
            ),
            interceptors: new InterceptorChain(...$interceptors),
            retryStrategy: new StandardRetryStrategy(baseDelay: 0.0)
        );
    }

    /**
     * Build a pipeline whose transport first fails with a Maybe error.
     */
    private function pipeline(
        MockHttpClient $http,
        ?RetrySafetyInterceptor $interceptor
    ): ClientPipeline
    {
        $config = new ClientConfig(
            protocol: new FakeProtocol(),
            transport: new \Smithy\Http\Client\HttpClientTransport($http),
            endpointResolver: new StaticEndpointResolver(
                new Endpoint('https://example.test')
            ),
            interceptors: new InterceptorChain(
                ...($interceptor === null ? [] : [$interceptor])
            ),
            retryStrategy: new StandardRetryStrategy(baseDelay: 0.0)
        );

        return new ClientPipeline($config);
    }

    private function failingHttp(): MockHttpClient
    {
        return (new MockHttpClient())
            ->queueException(new ApiException('ambiguous', ErrorFault::Other))
            ->queueResponse('server-body');
    }
}
