<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Dynamic;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Dynamic\{
    DynamicClient,
    DynamicService
};
use Smithy\Client\RequestOverrideConfig;
use Smithy\Client\Waiter\{
    WaiterConfig,
    WaiterFailureException
};
use Smithy\Codec\Cbor\CborCodec;
use Smithy\Core\Model\SmithyModel;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\Document\StructDocument;
use Smithy\Http\Message\Response;
use Smithy\Tests\Client\Waiter\Generated\Waiter\Output\GetThingOutput;
use Smithy\Tests\Utils\Http\MockHttpClient;

#[CoversClass(DynamicClient::class)]
#[CoversClass(DynamicService::class)]
final class DynamicWaiterTest extends TestCase
{
    public function testWaitsUsingModeledRuntimeDefinitions(): void
    {
        $http = (new MockHttpClient())
            ->queueResponse(self::response(
                new GetThingOutput(status: 'ready')
            ));
        $client = self::createClient($http);

        $result = $client->waitUntil(
            'ThingReady',
            ['thingId' => 'thing-1'],
            5.0
        );

        self::assertInstanceOf(StructDocument::class, $result->output);
        self::assertSame(
            'ready',
            $result->output->getMemberValue(
                $result->output->getSchema()->getMember('status')
            )
        );
        self::assertNull($result->error);
        self::assertCount(1, $http->sentRequests);
    }

    public function testUsesModeledFailureMessage(): void
    {
        $http = (new MockHttpClient())
            ->queueResponse(self::response(new GetThingOutput(
                status: 'failed',
                failureReason: 'Capacity is exhausted.'
            )));
        $client = self::createClient($http);

        try {
            $client->waitUntil(
                'ThingReady',
                ['thingId' => 'thing-1'],
                5.0
            );
            self::fail('Expected the dynamic waiter to fail.');
        } catch (WaiterFailureException $error) {
            self::assertStringContainsString(
                'Capacity is exhausted.',
                $error->getMessage()
            );
            self::assertSame(1, $error->attempts);
        }
    }

    public function testResolvesWaiterNamesCaseInsensitively(): void
    {
        $http = (new MockHttpClient())
            ->queueResponse(self::response(
                new GetThingOutput(status: 'ready')
            ));
        $client = self::createClient($http);

        $result = $client->waitUntil(
            'tHiNgReAdY',
            ['thingId' => 'thing-1'],
            5.0
        );

        self::assertInstanceOf(StructDocument::class, $result->output);
        self::assertCount(1, $http->sentRequests);
    }

    public function testAppliesInlineRequestOverrides(): void
    {
        $http = (new MockHttpClient())
            ->queueResponse(self::response(
                new GetThingOutput(status: 'ready')
            ));
        $client = self::createClient($http);

        $client->waitUntil(
            'ThingReady',
            [
                'thingId' => 'thing-1',
                '@http' => [
                    'headers' => ['x-waiter' => 'value']
                ]
            ],
            5.0
        );

        self::assertSame(
            'value',
            $http->sentRequests[0]->getHeaderLine('x-waiter')
        );
    }

    public function testConfigOverridesReplaceMatchingInlineOverrides(): void
    {
        $http = (new MockHttpClient())
            ->queueResponse(self::response(
                new GetThingOutput(status: 'ready')
            ));
        $client = self::createClient($http);

        $client->waitUntil(
            'ThingReady',
            [
                'thingId' => 'thing-1',
                '@http' => [
                    'headers' => ['x-waiter' => 'inline']
                ]
            ],
            5.0,
            new WaiterConfig(
                requestOverrides: RequestOverrideConfig::fromArray([
                    '@http' => [
                        'headers' => ['x-waiter' => 'config']
                    ]
                ])
            )
        );

        self::assertSame(
            'config',
            $http->sentRequests[0]->getHeaderLine('x-waiter')
        );
    }

    public function testRejectsUnknownWaiter(): void
    {
        $client = self::createClient(new MockHttpClient());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Unknown dynamic waiter: Missing.'
        );

        $client->waitUntil('Missing', ['thingId' => 'thing-1'], 5.0);
    }

    public function testRejectsDuplicateServiceWaiterNames(): void
    {
        $client = DynamicClient::create(
            model: self::duplicateWaiterModel(),
            httpClient: new MockHttpClient(),
            endpoint: 'https://example.com'
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'duplicate waiter name THINGREADY'
        );

        $client->waitUntil('ThingReady', null, 5.0);
    }

    private static function createClient(
        MockHttpClient $http
    ): DynamicClient
    {
        return DynamicClient::create(
            model: SmithyModel::fromFile(
                __DIR__ . '/../Waiter/fixtures/waiter-model.json'
            ),
            httpClient: $http,
            endpoint: 'https://example.com'
        );
    }

    private static function duplicateWaiterModel(): SmithyModel
    {
        $waiter = static fn(string $name): array => [
            'type' => 'operation',
            'traits' => [
                'smithy.waiters#waitable' => [
                    $name => [
                        'acceptors' => [
                            [
                                'state' => 'success',
                                'matcher' => ['success' => true]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => [
                'example#Waiters' => [
                    'type' => 'service',
                    'version' => '2026-10-13',
                    'operations' => [
                        ['target' => 'example#First'],
                        ['target' => 'example#Second']
                    ],
                    'traits' => [
                        'smithy.protocols#rpcv2Cbor' => []
                    ]
                ],
                'example#First' => $waiter('ThingReady'),
                'example#Second' => $waiter('THINGREADY')
            ]
        ]);
    }

    private static function response(
        SerializableStructInterface $output
    ): Response
    {
        $serializer = (new CborCodec())->createSerializer();
        $output->serialize($serializer, 0);

        return new Response(
            headers: [
                'Content-Type' => 'application/cbor',
                'Smithy-Protocol' => 'rpc-v2-cbor'
            ],
            body: $serializer->flush()
        );
    }
}
