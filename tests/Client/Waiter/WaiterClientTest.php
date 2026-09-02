<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Waiter;

use PHPUnit\Framework\TestCase;
use Smithy\Codec\Cbor\CborCodec;
use Smithy\Client\RequestOverrideConfig;
use Smithy\Client\Endpoint\{
    Endpoint,
    StaticEndpointResolver
};
use Smithy\Client\Waiter\{
    WaiterConfig,
    WaiterFailureException
};
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Http\Message\Response;
use Smithy\Protocol\RpcV2\RpcV2CborProtocol;
use Smithy\Tests\Client\Waiter\Generated\Waiter\Input\GetThingInput;
use Smithy\Tests\Client\Waiter\Generated\Waiter\Output\GetThingOutput;
use Smithy\Tests\Client\Waiter\Generated\Waiter\{
    WaiterClient,
    WaiterClientConfig
};
use Smithy\Tests\Utils\Http\MockHttpClient;
final class WaiterClientTest extends TestCase
{
    public function testGeneratedWaiterTraversesRequestPipeline(): void
    {
        $http = (new MockHttpClient())
            ->queueResponse(self::response(
                new GetThingOutput(status: 'ready')
            ));
        $client = self::createClient($http);

        $result = $client->waitUntilThingReady(
            new GetThingInput('thing-1'),
            5.0
        );

        self::assertInstanceOf(GetThingOutput::class, $result->output);
        self::assertSame('ready', $result->output->status);
        self::assertNull($result->error);
        self::assertCount(1, $http->sentRequests);
        self::assertSame(
            '/service/Waiter/operation/GetThing',
            $http->sentRequests[0]->getUri()->getPath()
        );
    }

    public function testGeneratedWaiterExtractsFailureMessage(): void
    {
        $http = (new MockHttpClient())
            ->queueResponse(self::response(new GetThingOutput(
                status: 'failed',
                failureReason: 'Capacity is exhausted.'
            )));
        $client = self::createClient($http);

        try {
            $client->waitUntilThingReady(
                new GetThingInput('thing-1'),
                5.0
            );
            self::fail('Expected the generated waiter to fail.');
        } catch (WaiterFailureException $error) {
            self::assertStringContainsString(
                'Capacity is exhausted.',
                $error->getMessage()
            );
            self::assertSame(1, $error->attempts);
        }
    }

    public function testGeneratedWaiterAppliesRequestOverrides(): void
    {
        $http = (new MockHttpClient())
            ->queueResponse(self::response(
                new GetThingOutput(status: 'ready')
            ));
        $client = self::createClient($http);
        $config = new WaiterConfig(
            requestOverrides: RequestOverrideConfig::fromArray([
                '@http' => ['headers' => ['x-waiter' => 'value']]
            ])
        );

        $client->waitUntilThingReady(
            new GetThingInput('thing-1'),
            5.0,
            $config
        );

        self::assertSame(
            'value',
            $http->sentRequests[0]->getHeaderLine('x-waiter')
        );
    }

    public function testGeneratedWaiterAppliesConfigOverrides(): void
    {
        $http = new MockHttpClient();
        $client = self::createClient($http);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Maximum waiter delay must be at least the minimum delay.'
        );

        $client->waitUntilThingReady(
            new GetThingInput('thing-1'),
            5.0,
            new WaiterConfig(minDelay: 3)
        );
    }

    private static function createClient(
        MockHttpClient $http
    ): WaiterClient
    {
        return new WaiterClient(new WaiterClientConfig(
            protocol: new RpcV2CborProtocol(),
            httpClient: $http,
            endpointResolver: new StaticEndpointResolver(
                new Endpoint('https://example.com')
            )
        ));
    }

    private static function response(
        SerializableStructInterface $output
    ): Response
    {
        $serializer = (new CborCodec())->createSerializer();
        $output->serialize($serializer, 0);

        return new Response(
            status: 200,
            headers: [
                'Content-Type' => 'application/cbor',
                'Smithy-Protocol' => 'rpc-v2-cbor'
            ],
            body: $serializer->flush()
        );
    }
}
