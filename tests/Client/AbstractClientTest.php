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
use Smithy\Client\Interceptor\IdempotencyTokenInterceptor;
use Smithy\Client\Plugin\PluginCollection;
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

    private function config(string $response = 'response'): ClientConfig
    {
        return new ClientConfig(
            protocol: new FakeProtocol(),
            httpClient: (new MockHttpClient())->willReturn($response),
            endpointResolver: new StaticEndpointResolver(
                new Endpoint('https://example.test')
            )
        );
    }
}
