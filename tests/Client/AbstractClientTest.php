<?php

declare(strict_types=1);

namespace Smithy\Tests\Client;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\{
    AbstractClient,
    ClientConfig
};
use Smithy\Client\Endpoint\{
    Endpoint,
    StaticEndpointResolver
};
use Smithy\Client\Interceptor\{
    AbstractInterceptor,
    IdempotencyTokenInterceptor,
    InputContext,
    InterceptorChain
};
use Smithy\Client\Plugin\PluginCollection;
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
