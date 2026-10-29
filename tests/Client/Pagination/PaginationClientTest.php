<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Pagination;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Smithy\Codec\Cbor\CborCodec;
use Smithy\Client\{
    Dynamic\DynamicClient,
    RequestOverrideConfig
};
use Smithy\Client\Endpoint\Endpoint;
use Smithy\Client\Endpoint\StaticEndpointResolver;
use Smithy\Client\Pagination\PageSizePaginatedRequestInterface;
use Smithy\Client\Pagination\PaginatedRequestInterface;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\Document\StructDocument;
use Smithy\Http\Message\Response;
use Smithy\Tests\Client\Pagination\Generated\Pagination\Input\{
    ListMapsInput,
    ListStringsInput,
    ListWithoutPageSizeInput
};
use Smithy\Tests\Client\Pagination\Generated\Pagination\Output\{
    ListMapsOutput,
    ListStringsOutput,
    ListWithoutPageSizeOutput
};
use Smithy\Tests\Client\Pagination\Generated\Pagination\{
    PaginationClient,
    PaginationClientConfig
};
use Smithy\Tests\Utils\Http\MockHttpClient;

final class PaginationClientTest extends TestCase
{
    public function testPaginationMembersArePrivateWritable(): void
    {
        foreach (
            [
                [ListStringsInput::class, 'nextToken'],
                [ListStringsInput::class, 'pageSize'],
                [ListMapsInput::class, 'exclusiveStartKey'],
                [ListMapsInput::class, 'limit'],
                [ListWithoutPageSizeInput::class, 'cursor']
            ] as [$class, $property]
        ) {
            $reflection = new \ReflectionProperty($class, $property);

            self::assertTrue($reflection->isPrivate());
            self::assertFalse($reflection->isReadOnly());
        }
    }

    public function testStringTokensTraverseFullRequestPipeline(): void
    {
        $http = (new MockHttpClient())
            ->queueResponse(self::response(
                new ListStringsOutput(['one'], 'next')
            ))
            ->queueResponse(self::response(
                new ListStringsOutput(['two'])
            ));
        $client = self::createClient($http);

        $pages = \iterator_to_array(
            $client->listStringsPaginated(
                new ListStringsInput(pageSize: 2)
            ),
            false
        );

        self::assertSame([['one'], ['two']], \array_column($pages, 'items'));
        self::assertCount(2, $http->sentRequests);
        self::assertPipelineRequest(
            $http->sentRequests[0],
            '/service/Pagination/operation/ListStrings'
        );
        self::assertPipelineRequest(
            $http->sentRequests[1],
            '/service/Pagination/operation/ListStrings'
        );

        $firstInput = self::decodeRequest(
            $http->sentRequests[0],
            ListStringsInput::class
        );
        $secondInput = self::decodeRequest(
            $http->sentRequests[1],
            ListStringsInput::class
        );

        self::assertNull($firstInput->getPaginationToken());
        self::assertSame('next', $secondInput->getPaginationToken());
        self::assertSame(2, $firstInput->getPaginationPageSize());
        self::assertSame(2, $secondInput->getPaginationPageSize());
    }

    public function testDynamicPaginationTraversesFullRequestPipeline(): void
    {
        $http = (new MockHttpClient())
            ->queueResponse(self::response(
                new ListStringsOutput(['one', 'two'], 'next')
            ))
            ->queueResponse(self::response(
                new ListStringsOutput(['three'])
            ));
        $client = DynamicClient::create(
            model: __DIR__ . '/fixtures/pagination-model.json',
            httpClient: $http,
            endpoint: 'https://example.com'
        );

        $pages = \iterator_to_array(
            $client
                ->paginate('ListStrings', [
                    'pageSize' => 2
                ], [
                    '@http' => [
                        'headers' => ['x-paginator' => 'dynamic']
                    ]
                ])
                ->withMaxItems(3),
            false
        );

        self::assertContainsOnlyInstancesOf(StructDocument::class, $pages);
        self::assertSame(
            [['one', 'two'], ['three']],
            \array_map(
                static function (StructDocument $page): array {
                    $member = $page->getSchema()->getMember('items');
                    self::assertNotNull($member);

                    return $page->getMemberValue($member);
                },
                $pages
            )
        );
        self::assertCount(2, $http->sentRequests);
        foreach ($http->sentRequests as $request) {
            self::assertSame(
                'dynamic',
                $request->getHeaderLine('x-paginator')
            );
        }

        $firstInput = self::decodeRequest(
            $http->sentRequests[0],
            ListStringsInput::class
        );
        $secondInput = self::decodeRequest(
            $http->sentRequests[1],
            ListStringsInput::class
        );

        self::assertNull($firstInput->getPaginationToken());
        self::assertSame('next', $secondInput->getPaginationToken());
        self::assertSame(2, $firstInput->getPaginationPageSize());
        self::assertSame(1, $secondInput->getPaginationPageSize());
    }

    public function testMapTokensTraverseFullRequestPipeline(): void
    {
        $initialToken = ['partition' => 'start'];
        $nextToken = ['partition' => 'next'];
        $http = (new MockHttpClient())
            ->queueResponse(self::response(
                new ListMapsOutput(['one'], $nextToken)
            ))
            ->queueResponse(self::response(
                new ListMapsOutput(['two'], [])
            ));
        $client = self::createClient($http);

        $pages = \iterator_to_array(
            $client->listMapsPaginated(
                new ListMapsInput($initialToken, 1)
            ),
            false
        );

        self::assertSame([['one'], ['two']], \array_column($pages, 'items'));
        self::assertCount(2, $http->sentRequests);

        $firstInput = self::decodeRequest(
            $http->sentRequests[0],
            ListMapsInput::class
        );
        $secondInput = self::decodeRequest(
            $http->sentRequests[1],
            ListMapsInput::class
        );

        self::assertSame($initialToken, $firstInput->getPaginationToken());
        self::assertSame($nextToken, $secondInput->getPaginationToken());
        self::assertSame(1, $firstInput->getPaginationPageSize());
        self::assertSame(1, $secondInput->getPaginationPageSize());
    }

    public function testRequestOverridesApplyToEveryPage(): void
    {
        $http = (new MockHttpClient())
            ->queueResponse(self::response(
                new ListStringsOutput(['one'], 'next')
            ))
            ->queueResponse(self::response(
                new ListStringsOutput(['two'])
            ));
        $client = self::createClient($http);
        $overrides = RequestOverrideConfig::fromArray([
            '@http' => ['headers' => ['x-paginator' => 'value']]
        ]);

        \iterator_to_array(
            $client
                ->listStringsPaginated(new ListStringsInput())
                ->withRequestOverrides($overrides),
            false
        );

        self::assertCount(2, $http->sentRequests);
        foreach ($http->sentRequests as $request) {
            self::assertSame(
                'value',
                $request->getHeaderLine('x-paginator')
            );
        }
    }

    public function testPaginationWithoutPageSizeTraversesPipeline(): void
    {
        $http = (new MockHttpClient())
            ->queueResponse(self::response(
                new ListWithoutPageSizeOutput(['one'], 'next')
            ))
            ->queueResponse(self::response(
                new ListWithoutPageSizeOutput(['two'])
            ));
        $client = self::createClient($http);
        $input = new ListWithoutPageSizeInput('start');

        self::assertNotInstanceOf(
            PageSizePaginatedRequestInterface::class,
            $input
        );

        $pages = \iterator_to_array(
            $client->listWithoutPageSizePaginated($input),
            false
        );

        self::assertSame([['one'], ['two']], \array_column($pages, 'items'));
        self::assertCount(2, $http->sentRequests);

        $firstInput = self::decodeRequest(
            $http->sentRequests[0],
            ListWithoutPageSizeInput::class
        );
        $secondInput = self::decodeRequest(
            $http->sentRequests[1],
            ListWithoutPageSizeInput::class
        );

        self::assertSame('start', $firstInput->getPaginationToken());
        self::assertSame('next', $secondInput->getPaginationToken());
    }

    private static function createClient(
        MockHttpClient $http
    ): PaginationClient
    {
        return new PaginationClient(new PaginationClientConfig(
            transport: new \Smithy\Http\Client\HttpClientTransport($http),
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

    private static function assertPipelineRequest(
        RequestInterface $request,
        string $path
    ): void
    {
        self::assertSame('POST', $request->getMethod());
        self::assertSame($path, $request->getUri()->getPath());
        self::assertSame(
            'rpc-v2-cbor',
            $request->getHeaderLine('Smithy-Protocol')
        );
        self::assertSame(
            'application/cbor',
            $request->getHeaderLine('Content-Type')
        );
    }

    /**
     * Deserialize a generated paginator request.
     *
     * @template TInput of PaginatedRequestInterface
     *
     * @param class-string<TInput> $inputClass
     *
     * @return TInput
     */
    private static function decodeRequest(
        RequestInterface $request,
        string $inputClass
    ): PaginatedRequestInterface
    {
        $schema = $inputClass::schema();
        $members = \array_fill(0, $schema->getMemberCount(), null);
        (new CborCodec())
            ->createDeserializer($request->getBody())
            ->deserialize($schema, $members);

        return $inputClass::fromMembers($members);
    }
}
