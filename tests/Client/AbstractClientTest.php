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
    IdempotencyTokenInterceptor,
    InputContext,
    InterceptorChain
};
use Smithy\Client\Plugin\{
    AbstractPlugin,
    IdempotencyTokenPlugin,
    PluginCollection,
    PluginInterface
};
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Tests\Client\Fake\{
    FakeInput,
    FakeOperation,
    FakeOutput,
    FakeProtocol,
    TestClientFixture
};
use Smithy\Tests\Client\Plugin\Utils\{
    RecordingPluginFixture,
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

        self::assertSame($config->protocol, $client->getConfig()->protocol);
        self::assertTrue(
            $client->getConfig()->interceptorChain->contains(
                IdempotencyTokenInterceptor::class
            )
        );
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

    private function config(
        string $response = 'response',
        ?MockHttpClient $http = null,
        ?InterceptorChain $interceptors = null
    ): ClientConfig
    {
        return new ClientConfig(
            protocol: new FakeProtocol(),
            httpClient: $http ?? (new MockHttpClient())->willReturn($response),
            endpointResolver: new StaticEndpointResolver(
                new Endpoint('https://example.test')
            ),
            interceptors: $interceptors ?? new InterceptorChain()
        );
    }
}
