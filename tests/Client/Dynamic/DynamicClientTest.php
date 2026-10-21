<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Dynamic;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Smithy\Client\{
    ClientConfig,
    Dynamic\DynamicClient,
    Dynamic\DynamicService,
    RequestOverrideConfig
};
use Smithy\Client\Endpoint\{
    Endpoint,
    EndpointResolverParams,
    StaticEndpointResolver
};
use Smithy\Client\Interceptor\{
    AbstractInterceptor,
    InterceptorChain
};
use Smithy\Client\Pagination\Paginator;
use Smithy\Client\Plugin\{
    PluginCollection,
    RequestCompressionPlugin
};
use Smithy\Client\Waiter\{
    WaiterConfig,
    WaiterFailureException
};
use Smithy\Codec\Cbor\CborCodec;
use Smithy\Core\Exception\ApiException;
use Smithy\Core\Model\SmithyModel;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\Document\{
    AbstractDocument,
    BlobValue,
    StructDocument
};
use Smithy\Core\Shape\Exception\DocumentException;
use Smithy\Core\Shape\Exception\InvalidShapeValueException;
use Smithy\Http\Message\{Response, ResponseInterface};
use Smithy\Protocol\RpcV2\RpcV2CborProtocol;
use Smithy\Tests\Client\Dynamic\Utils\{
    AlwaysTrueFunctionFixture,
    DynamicRetryStrategyFixture,
    IdentifiedProtocolFixture,
    InputRecordingInterceptorFixture,
    TestDynamicClient
};
use Smithy\Tests\Client\Pagination\Generated\Pagination\Output\ListStringsOutput;
use Smithy\Tests\Client\Waiter\Generated\Waiter\Output\GetThingOutput;
use Smithy\Tests\Utils\Http\MockHttpClient;

#[CoversClass(DynamicClient::class)]
#[CoversClass(DynamicService::class)]
final class DynamicClientTest extends TestCase
{
    private const MIN_COMPRESSION_SIZE =
        RequestCompressionPlugin::CONFIG_REQUEST_MIN_COMPRESSION_SIZE_BYTES;

    public function testCreatesAndCallsWithResolvedDependencies(): void
    {
        $http = $this->httpClient();
        $client = DynamicClient::create(
            model: $this->model(),
            httpClient: $http,
            endpoint: 'https://example.com'
        );

        $output = $client->call('Echo', ['message' => 'hello']);

        self::assertSame(
            'hello',
            $this->memberValue($output, 'message')
        );
        self::assertInstanceOf(
            RpcV2CborProtocol::class,
            $client->getConfig()->protocol
        );
        self::assertSame(
            RpcV2CborProtocol::ID,
            $client->getConfig()->protocol->getId()
        );
        self::assertSame(
            'example#Sprockets',
            $client->getConfig()->service?->getId()
        );
        self::assertSame(
            '/service/Sprockets/operation/Echo',
            $http->getLastRequest()?->getUri()->getPath()
        );
    }

    public function testAppliesModeledRequestPlugins(): void
    {
        if (!\extension_loaded('zlib')) {
            self::markTestSkipped('The zlib extension is not available.');
        }

        $http = new MockHttpClient();
        $http->fallbackHandler(
            static function (
                RequestInterface $request
            ): ResponseInterface {
                $body = \gzdecode((string) $request->getBody());
                if ($body === false) {
                    throw new \RuntimeException(
                        'Failed to decode the compressed test request.'
                    );
                }

                return new Response(
                    headers: ['Smithy-Protocol' => 'rpc-v2-cbor'],
                    body: $body
                );
            }
        );
        $client = DynamicClient::create(
            model: $this->model(operationTraits: [
                'smithy.api#requestCompression' => [
                    'encodings' => ['gzip']
                ],
                'smithy.api#httpChecksumRequired' => []
            ]),
            httpClient: $http,
            endpoint: 'https://example.com',
            context: [
                self::MIN_COMPRESSION_SIZE => 0
            ]
        );

        $output = $client->call('Echo', ['message' => 'hello']);
        $request = $http->getLastRequest();

        self::assertSame(
            'hello',
            $this->memberValue($output, 'message')
        );
        self::assertNotNull($request);
        self::assertSame(
            'gzip',
            $request->getHeaderLine('Content-Encoding')
        );
        $body = (string) $request->getBody();
        self::assertSame(
            \base64_encode(\md5($body, true)),
            $request->getHeaderLine('Content-MD5')
        );
    }

    public function testExplicitPluginOverridesModeledDefault(): void
    {
        if (!\extension_loaded('zlib')) {
            self::markTestSkipped('The zlib extension is not available.');
        }

        $http = $this->httpClient();
        $client = DynamicClient::create(
            model: $this->model(operationTraits: [
                'smithy.api#requestCompression' => [
                    'encodings' => ['gzip']
                ]
            ]),
            httpClient: $http,
            endpoint: 'https://example.com',
            context: [
                self::MIN_COMPRESSION_SIZE => 0
            ],
            plugins: new PluginCollection(
                new RequestCompressionPlugin(disabled: true)
            )
        );

        $output = $client->call('Echo', ['message' => 'hello']);
        $request = $http->getLastRequest();

        self::assertSame(
            'hello',
            $this->memberValue($output, 'message')
        );
        self::assertNotNull($request);
        self::assertSame(
            '',
            $request->getHeaderLine('Content-Encoding')
        );
    }

    public function testInvokesOperationsAsMethodsWithRequestOverrides(): void
    {
        $http = $this->httpClient();
        $client = DynamicClient::create(
            model: $this->model(),
            httpClient: $http,
            endpoint: 'https://example.com'
        );

        $output = $client->echo([
            'message' => 'method',
            '@http' => [
                'timeout' => 2.5,
                'read_timeout' => 1.5,
                'headers' => [
                    'x-test-request' => 'override'
                ]
            ]
        ]);

        self::assertSame('method', $this->memberValue($output, 'message'));
        self::assertSame(
            'override',
            $http->getLastRequest()?->getHeaderLine('x-test-request')
        );
        self::assertSame(2.5, $http->getSentOptions()[0]->timeout);
        self::assertSame(1.5, $http->getSentOptions()[0]->readTimeout);
    }

    public function testModeledOperationsTakePrecedenceOverConvenienceNames(): void
    {
        $http = $this->httpClient();
        $client = DynamicClient::create(
            model: $this->conventionCollisionModel(),
            httpClient: $http,
            endpoint: 'https://example.com'
        );

        $waitOutput = $client->waitUntilThingReady([
            'message' => 'wait operation'
        ]);
        $pageOutput = $client->listThingsPaginated([
            'message' => 'page operation'
        ]);

        self::assertInstanceOf(StructDocument::class, $waitOutput);
        self::assertInstanceOf(StructDocument::class, $pageOutput);
        self::assertSame(
            'wait operation',
            $this->memberValue($waitOutput, 'message')
        );
        self::assertSame(
            'page operation',
            $this->memberValue($pageOutput, 'message')
        );
        self::assertSame(
            [
                '/service/Collisions/operation/WaitUntilThingReady',
                '/service/Collisions/operation/ListThingsPaginated'
            ],
            \array_map(
                static fn(RequestInterface $request): string =>
                    $request->getUri()->getPath(),
                $http->sentRequests
            )
        );
    }

    public function testRejectsAmbiguousConvenienceMethodNames(): void
    {
        [$client, $http] = $this->client();

        try {
            $client->waitUntilEchoPaginated();
            self::fail('Expected ambiguous dynamic method to fail.');
        } catch (\BadMethodCallException $error) {
            self::assertSame(
                "Dynamic method 'waitUntilEchoPaginated' is ambiguous. "
                    . 'Use paginate() or waitUntil() explicitly.',
                $error->getMessage()
            );
        }

        self::assertSame(0, $http->sendCallCount);
    }

    public function testPaginatedMethodRejectsNonPaginatedOperation(): void
    {
        [$client, $http] = $this->client();

        try {
            $client->echoPaginated(['message' => 'hello']);
            self::fail('Expected non-paginated operation to fail.');
        } catch (\LogicException $error) {
            self::assertSame(
                'Operation example#Echo is not paginated.',
                $error->getMessage()
            );
        }

        self::assertSame(0, $http->sendCallCount);
    }

    public function testRejectsUnknownDynamicMethod(): void
    {
        [$client, $http] = $this->client();

        try {
            $client->missing();
            self::fail('Expected unknown dynamic method to fail.');
        } catch (\BadMethodCallException $error) {
            self::assertSame(
                'Unknown dynamic method: missing.',
                $error->getMessage()
            );
        }

        self::assertSame(0, $http->sendCallCount);
    }

    public function testDynamicPaginationUsesGeneratedStyleMethod(): void
    {
        $http = (new MockHttpClient())
            ->queueResponse(self::response(
                new ListStringsOutput(['one'])
            ));
        $client = DynamicClient::create(
            model: __DIR__ . '/../Pagination/fixtures/pagination-model.json',
            httpClient: $http,
            endpoint: 'https://example.com'
        );

        $pages = \iterator_to_array(
            $client->listStringsPaginated(input: [
                'pageSize' => 2,
                '@http' => [
                    'headers' => ['x-paginator' => 'dynamic-method']
                ]
            ]),
            false
        );

        self::assertContainsOnlyInstancesOf(StructDocument::class, $pages);
        self::assertCount(1, $pages);
        self::assertSame(
            'dynamic-method',
            $http->sentRequests[0]->getHeaderLine('x-paginator')
        );
    }

    public function testDynamicGeneratedStylePaginationAllowsOmittedInput(): void
    {
        $client = DynamicClient::create(
            model: __DIR__ . '/../Pagination/fixtures/pagination-model.json',
            httpClient: new MockHttpClient(),
            endpoint: 'https://example.com'
        );

        self::assertInstanceOf(
            Paginator::class,
            $client->listStringsPaginated()
        );
    }

    public function testDynamicGeneratedStylePaginationAcceptsOverrides(): void
    {
        $http = (new MockHttpClient())
            ->queueResponse(self::response(
                new ListStringsOutput(['one'])
            ));
        $client = DynamicClient::create(
            model: __DIR__ . '/../Pagination/fixtures/pagination-model.json',
            httpClient: $http,
            endpoint: 'https://example.com'
        );

        \iterator_to_array(
            $client->listStringsPaginated(
                ['pageSize' => 2],
                ['@http' => [
                    'headers' => ['x-paginator' => 'dynamic-overrides']
                ]]
            ),
            false
        );

        self::assertSame(
            'dynamic-overrides',
            $http->sentRequests[0]->getHeaderLine('x-paginator')
        );
    }

    public function testWaitsUsingModeledRuntimeDefinitions(): void
    {
        $http = (new MockHttpClient())
            ->queueResponse(self::response(
                new GetThingOutput(status: 'ready')
            ));
        $client = self::waiterClient($http);

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

    public function testWaitsUsingGeneratedStyleMethod(): void
    {
        $http = (new MockHttpClient())
            ->queueResponse(self::response(
                new GetThingOutput(status: 'ready')
            ));
        $client = self::waiterClient($http);

        $result = $client->waitUntilThingReady(
            ['thingId' => 'thing-1'],
            5.0
        );

        self::assertInstanceOf(StructDocument::class, $result->output);
        self::assertCount(1, $http->sentRequests);
    }

    public function testGeneratedStyleWaiterAcceptsNamedArguments(): void
    {
        $http = (new MockHttpClient())
            ->queueResponse(self::response(
                new GetThingOutput(status: 'ready')
            ));
        $client = self::waiterClient($http);

        $result = $client->waitUntilThingReady(
            maxWaitTime: 5.0,
            input: ['thingId' => 'thing-1'],
            config: new WaiterConfig()
        );

        self::assertInstanceOf(StructDocument::class, $result->output);
        self::assertCount(1, $http->sentRequests);
    }

    public function testGeneratedStyleWaiterUsesNativeRequiredArguments(): void
    {
        $client = self::waiterClient(new MockHttpClient());

        $this->expectException(\ArgumentCountError::class);

        $client->waitUntilThingReady(['thingId' => 'thing-1']);
    }

    public function testGeneratedStyleWaiterUsesNativeDuplicateValidation(): void
    {
        $client = self::waiterClient(new MockHttpClient());

        $this->expectException(\Error::class);

        $client->__call('waitUntilThingReady', [
            ['thingId' => 'thing-1'],
            'input' => ['thingId' => 'thing-2'],
            'maxWaitTime' => 5.0
        ]);
    }

    public function testUsesModeledWaiterFailureMessage(): void
    {
        $http = (new MockHttpClient())
            ->queueResponse(self::response(new GetThingOutput(
                status: 'failed',
                failureReason: 'Capacity is exhausted.'
            )));
        $client = self::waiterClient($http);

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
        $client = self::waiterClient($http);

        $result = $client->waitUntil(
            'tHiNgReAdY',
            ['thingId' => 'thing-1'],
            5.0
        );

        self::assertInstanceOf(StructDocument::class, $result->output);
        self::assertCount(1, $http->sentRequests);
    }

    public function testAppliesInlineWaiterRequestOverrides(): void
    {
        $http = (new MockHttpClient())
            ->queueResponse(self::response(
                new GetThingOutput(status: 'ready')
            ));
        $client = self::waiterClient($http);

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
        $client = self::waiterClient($http);

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
        $client = self::waiterClient(new MockHttpClient());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Unknown dynamic waiter: Missing.'
        );

        $client->waitUntil('Missing', ['thingId' => 'thing-1'], 5.0);
    }

    public function testGeneratedStyleMethodRejectsUnknownWaiter(): void
    {
        $client = self::waiterClient(new MockHttpClient());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Unknown dynamic waiter: Missing.'
        );

        $client->waitUntilMissing(['thingId' => 'thing-1'], 5.0);
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

    public function testRejectsUnknownRequestOverridesBeforeTransmission(): void
    {
        [$client, $http] = $this->client();

        try {
            $client->Echo([
                'message' => 'hello',
                '@unknown' => true
            ]);
            self::fail('Expected unknown request override to fail.');
        } catch (\InvalidArgumentException $error) {
            self::assertSame(
                "Unknown request override '@unknown'.",
                $error->getMessage()
            );
        }

        self::assertSame(0, $http->sendCallCount);
    }

    public function testRejectsEmptyAuthPreferenceBeforeTransmission(): void
    {
        [$client, $http] = $this->client();

        try {
            $client->Echo([
                'message' => 'hello',
                '@auth' => []
            ]);
            self::fail('Expected an empty auth preference to fail.');
        } catch (\InvalidArgumentException $error) {
            self::assertSame(
                '@auth must be a non-empty list of auth scheme names.',
                $error->getMessage()
            );
        }

        self::assertSame(0, $http->sendCallCount);
    }

    public function testRequestRetryOverrideCapsAttempts(): void
    {
        $http = $this->httpClient();
        $http->queueException(new \RuntimeException('temporary'));
        $strategy = new DynamicRetryStrategyFixture();
        $client = DynamicClient::create(
            model: $this->model(),
            httpClient: $http,
            endpoint: 'https://example.com',
            retryStrategy: $strategy
        );

        try {
            $client->Echo([
                'message' => 'hello',
                '@retries' => 0
            ]);
            self::fail('Expected the first attempt to fail.');
        } catch (\RuntimeException $error) {
            self::assertSame('temporary', $error->getMessage());
        }

        self::assertSame(1, $http->sendCallCount);
        self::assertSame(0, $strategy->refreshCalls);
    }

    public function testCreateUsesLateStaticClientClass(): void
    {
        $http = $this->httpClient();
        $client = TestDynamicClient::create(
            model: $this->model(),
            httpClient: $http,
            endpoint: 'https://example.com'
        );

        self::assertInstanceOf(TestDynamicClient::class, $client);
        self::assertSame(
            'hello',
            $this->memberValue(
                $client->call('Echo', ['message' => 'hello']),
                'message'
            )
        );
    }

    public function testInvokesDefaultedOperationWithoutInput(): void
    {
        $client = DynamicClient::create(
            model: $this->model(),
            httpClient: $this->httpClient(),
            endpoint: 'https://example.com'
        );

        $output = $client->call('DefaultedEcho');

        self::assertSame(
            'default',
            $this->memberValue($output, 'message')
        );
        self::assertNull($this->memberValue($output, 'note'));
    }

    public function testCreatesFromModelPath(): void
    {
        $client = DynamicClient::create(
            model: __DIR__
                . '/fixtures/dynamic-model.json',
            httpClient: $this->httpClient(),
            endpoint: 'https://example.com'
        );

        self::assertSame(
            'example#Service',
            $client->getService()->getId()
        );
        self::assertInstanceOf(
            RpcV2CborProtocol::class,
            $client->getConfig()->protocol
        );
        self::assertSame(
            RpcV2CborProtocol::ID,
            $client->getConfig()->protocol->getId()
        );
    }

    public function testSelectsInjectedProtocolMapping(): void
    {
        $protocol = new IdentifiedProtocolFixture(
            'example.protocols#custom'
        );
        $client = TestDynamicClient::create(
            model: $this->model('example.protocols#custom'),
            protocols: [
                'example.protocols#custom' => $protocol
            ],
            httpClient: $this->httpClient(),
            endpoint: 'https://example.com'
        );

        self::assertSame($protocol, $client->getConfig()->protocol);
        self::assertSame(
            'example#Sprockets',
            $client->getOperation('Echo')?->getServiceId()
        );
    }

    public function testSubclassRegistersDefaultRuntime(): void
    {
        $client = TestDynamicClient::create(
            model: $this->model('example.protocols#custom'),
            httpClient: $this->httpClient(),
            endpoint: 'https://example.com'
        );

        self::assertInstanceOf(
            IdentifiedProtocolFixture::class,
            $client->getConfig()->protocol
        );
        self::assertSame(
            'example.protocols#custom',
            $client->getConfig()->protocol->getId()
        );
    }

    public function testUsesRegisteredRuntimeImplementations(): void
    {
        $endpointFunction = new AlwaysTrueFunctionFixture();
        $client = TestDynamicClient::create(
            model: $this->model(
                endpointBdd: $this->endpointBdd(
                    [
                        'authSchemes' => [
                            ['name' => 'custom']
                        ]
                    ],
                    [
                        [
                            'fn' => $endpointFunction->getName(),
                            'argv' => []
                        ]
                    ]
                ),
                messageTraits: ['example#upper' => 'registered'],
                additionalServiceTraits: [
                    'example.protocols#custom' => []
                ]
            ),
            httpClient: $this->httpClient(),
            endpointParameters: ['Host' => 'example.com']
        );

        $output = $client->call('Echo', ['message' => 'hello']);
        $operation = $client->getOperation('Echo');
        self::assertNotNull($operation);
        self::assertSame(
            'example#Sprockets',
            $operation->getServiceId()
        );
        $message = $operation->getInputSchema()->getMember('message');
        self::assertNotNull($message);

        self::assertInstanceOf(
            IdentifiedProtocolFixture::class,
            $client->getConfig()->protocol
        );
        self::assertContains(
            'example.auth#custom',
            \array_map(
                static fn($scheme): string => $scheme->getSchemeId(),
                $client->getConfig()->supportedAuthSchemes
            )
        );
        self::assertSame(
            'REGISTERED',
            $message->getTraitById('example#upper')
        );
        self::assertSame('hello', $this->memberValue($output, 'message'));
    }

    public function testRejectsInvalidProtocolMappings(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'mapped to matching ProtocolInterface instances'
        );

        DynamicClient::create(
            model: $this->model(),
            protocols: [
                'example.protocols#invalid' => new \stdClass()
            ],
            httpClient: $this->httpClient(),
            endpoint: 'https://example.com'
        );
    }

    public function testCreateRequiresEndpointResolution(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'requires an endpoint resolver or static endpoint when the '
            . 'service has no modeled endpoint BDD'
        );

        DynamicClient::create(
            model: $this->model(),
            httpClient: $this->httpClient()
        );
    }

    public function testIgnoresUnsupportedModeledEndpointRuleSet(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'requires an endpoint resolver or static endpoint when the '
            . 'service has no modeled endpoint BDD'
        );

        DynamicClient::create(
            model: $this->model(endpointRuleSet: ['version' => '1.0']),
            httpClient: $this->httpClient()
        );
    }

    public function testResolvesModeledEndpointBddFromInputContext(): void
    {
        $http = $this->httpClient();
        $client = DynamicClient::create(
            model: $this->model(
                endpointBdd: $this->endpointBdd(),
                messageTraits: [
                    'smithy.rules#contextParam' => ['name' => 'Host']
                ]
            ),
            httpClient: $http
        );

        $client->call('Echo', ['message' => 'input.example']);

        self::assertSame(
            'input.example',
            $http->getLastRequest()?->getUri()->getHost()
        );
    }

    public function testMapsModeledEndpointAuthSchemeIds(): void
    {
        $client = DynamicClient::create(
            model: $this->model(
                endpointBdd: $this->endpointBdd([
                    'authSchemes' => [
                        [
                            'name' => 'custom',
                            'signingName' => 'widgets'
                        ]
                    ]
                ])
            ),
            httpClient: $this->httpClient(),
            endpointAuthSchemeIds: [
                'custom' => 'example.auth#custom'
            ],
            endpointParameters: ['Host' => 'example.com']
        );
        $operation = $client->getOperation('Echo');
        self::assertNotNull($operation);
        $input = $operation->createInputFromValue(['message' => 'hello']);

        $endpoint = $client->getConfig()->endpointResolver->resolveEndpoint(
            new EndpointResolverParams(
                $operation,
                $input,
                $client->getConfig()
            )
        );

        self::assertCount(1, $endpoint->authSchemes);
        self::assertSame(
            'example.auth#custom',
            $endpoint->authSchemes[0]->schemeId
        );
        self::assertSame(
            ['signingName' => 'widgets'],
            $endpoint->authSchemes[0]->properties
        );
    }

    public function testResolvesModeledEndpointBddFromOperationPath(): void
    {
        $http = $this->httpClient();
        $client = DynamicClient::create(
            model: $this->model(
                endpointBdd: $this->endpointBdd(),
                operationTraits: [
                    'smithy.rules#operationContextParams' => [
                        'Host' => ['path' => 'message']
                    ]
                ]
            ),
            httpClient: $http
        );

        $client->call('Echo', ['message' => 'path.example']);

        self::assertSame(
            'path.example',
            $http->getLastRequest()?->getUri()->getHost()
        );
    }

    public function testEndpointParametersOverrideOperationContext(): void
    {
        $http = $this->httpClient();
        $client = DynamicClient::create(
            model: $this->model(
                endpointBdd: $this->endpointBdd(),
                messageTraits: [
                    'smithy.rules#contextParam' => ['name' => 'Host']
                ],
                operationTraits: [
                    'smithy.rules#staticContextParams' => [
                        'Host' => ['value' => 'static.example']
                    ]
                ]
            ),
            httpClient: $http,
            endpointParameters: ['Host' => 'configured.example']
        );

        $client->call('Echo', ['message' => 'input.example']);

        self::assertSame(
            'configured.example',
            $http->getLastRequest()?->getUri()->getHost()
        );
    }

    public function testStaticOperationContextOverridesInputContext(): void
    {
        $http = $this->httpClient();
        $client = DynamicClient::create(
            model: $this->model(
                endpointBdd: $this->endpointBdd(),
                messageTraits: [
                    'smithy.rules#contextParam' => ['name' => 'Host']
                ],
                operationTraits: [
                    'smithy.rules#staticContextParams' => [
                        'Host' => ['value' => 'static.example']
                    ]
                ]
            ),
            httpClient: $http
        );

        $client->call('Echo', ['message' => 'input.example']);

        self::assertSame(
            'static.example',
            $http->getLastRequest()?->getUri()->getHost()
        );
    }

    public function testCallsOperationsThroughTheSharedPipeline(): void
    {
        [$client, $http] = $this->client();

        $output = $client->call('Echo', ['message' => 'hello']);

        self::assertSame(
            'hello',
            $this->memberValue($output, 'message')
        );
        self::assertSame(
            '/service/Sprockets/operation/Echo',
            $http->getLastRequest()?->getUri()->getPath()
        );
        self::assertSame(
            $client->getOperation('Echo'),
            $client->getOperation('Echo')
        );
    }

    public function testAcceptsDocumentInputAndExposesItToInterceptors(): void
    {
        $observer = new InputRecordingInterceptorFixture();
        [$client] = $this->client([$observer]);
        $input = AbstractDocument::fromValue(['message' => 'document']);
        self::assertNotNull($input);

        $output = $client->call('Echo', $input);

        self::assertSame(
            'document',
            $this->memberValue($output, 'message')
        );
        self::assertNotNull($observer->input);
        self::assertSame(
            'document',
            $this->memberValue($observer->input, 'message')
        );
    }

    public function testSupportsBinaryInputAndRejectsGenericStrings(): void
    {
        [$client, $http] = $this->client();

        $output = $client->call(
            'EchoBlob',
            ['data' => new BlobValue("\x00\x01")]
        );
        self::assertSame(
            "\x00\x01",
            $this->memberValue($output, 'data')
        );
        self::assertSame(1, $http->sendCallCount);

        try {
            $client->call('EchoBlob', ['data' => 'AAE=']);
            self::fail('Expected generic string blob input to fail.');
        } catch (InvalidShapeValueException $error) {
            self::assertStringContainsString(
                'Expected BlobValue at $.data',
                $error->getMessage()
            );
        }
        self::assertSame(1, $http->sendCallCount);
    }

    public function testCallsOperationsWithoutInput(): void
    {
        [$client] = $this->client();

        $output = $client->call('Ping');

        self::assertSame('smithy.api#Unit', $output->getSchema()->id);
        self::assertSame([], $output->asMap());
    }

    public function testCreatesStructuresByShapeId(): void
    {
        [$client] = $this->client();

        $document = $client->createStruct(
            'example#Message',
            ['message' => 'created']
        );

        self::assertSame(
            'created',
            $this->memberValue($document, 'message')
        );
    }

    public function testRejectsUnknownOperationsBeforeTransmission(): void
    {
        [$client, $http] = $this->client();

        try {
            $client->call('Missing');
            self::fail('Expected unknown operation lookup to fail.');
        } catch (\InvalidArgumentException $error) {
            self::assertSame(
                "Operation 'Missing' not found in service "
                    . "'example#Sprockets'.",
                $error->getMessage()
            );
        }
        self::assertSame(0, $http->sendCallCount);
    }

    #[DataProvider('unsupportedStreamingOperations')]
    public function testRejectsPreviewExcludedStreamingBeforeTransmission(
        string $operationName,
        string $expectedMessage
    ): void
    {
        [$client, $http] = $this->client();

        try {
            $client->call($operationName);
            self::fail('Expected dynamic streaming to be rejected.');
        } catch (\LogicException $error) {
            self::assertSame($expectedMessage, $error->getMessage());
        }

        self::assertSame(0, $http->sendCallCount);
    }

    /**
     * Provide streaming blob and event-stream operations.
     *
     * @return iterable<string, array{string, string}>
     */
    public static function unsupportedStreamingOperations(): iterable
    {
        yield 'streaming blob input' => [
            'UploadStream',
            'Dynamic operation example#UploadStream is outside the preview '
                . "scope because it has input streaming member 'data'. "
                . 'Dynamic streaming blobs and event streams are not '
                . 'supported.'
        ];
        yield 'event stream output' => [
            'Subscribe',
            'Dynamic operation example#Subscribe is outside the preview '
                . "scope because it has output streaming member 'events'. "
                . 'Dynamic streaming blobs and event streams are not '
                . 'supported.'
        ];
        yield 'bidirectional event stream' => [
            'Converse',
            'Dynamic operation example#Converse is outside the preview scope '
                . "because it has input streaming member 'events' and output "
                . "streaming member 'events'. Dynamic streaming blobs and "
                . 'event streams are not supported.'
        ];
    }

    #[DataProvider('modeledErrorCases')]
    public function testThrowsDocumentExceptionsForModeledErrors(
        string $discriminator,
        string $shapeId,
        string $memberName,
        string $memberValue
    ): void
    {
        [$client, $http] = $this->client();
        $http->fallbackResponse(new Response(
            status: 400,
            headers: ['Smithy-Protocol' => 'rpc-v2-cbor'],
            body: $this->errorBody([
                '__type' => $discriminator,
                'message' => 'modeled failure',
                $memberName => $memberValue
            ])
        ));

        try {
            $client->call('Echo', ['message' => 'hello']);
            self::fail('Expected a modeled document exception.');
        } catch (DocumentException $error) {
            self::assertSame($shapeId, $error->getSchema()->id);
            self::assertSame('modeled failure', $error->getMessage());
            self::assertTrue($error->isClientFault());
            $member = $error->getSchema()->getMember($memberName);
            self::assertNotNull($member);
            self::assertSame(
                $memberValue,
                $error->getMemberValue($member)
            );
        }
    }

    public function testThrowsApiExceptionForUnmodeledErrors(): void
    {
        [$client, $http] = $this->client();
        $http->fallbackResponse(new Response(
            status: 500,
            headers: ['Smithy-Protocol' => 'rpc-v2-cbor'],
            body: $this->errorBody([
                '__type' => 'UnknownFailure',
                'message' => 'unmodeled failure'
            ])
        ));

        try {
            $client->call('Echo', ['message' => 'hello']);
            self::fail('Expected an unmodeled API exception.');
        } catch (DocumentException) {
            self::fail('Expected the generic exception fallback.');
        } catch (ApiException $error) {
            self::assertSame('unmodeled failure', $error->getMessage());
            self::assertTrue($error->isServerFault());
        }
    }

    /**
     * Provide operation and service error discriminator cases.
     *
     * @return iterable<string, array{string, string, string, string}>
     */
    public static function modeledErrorCases(): iterable
    {
        yield 'absolute operation error' => [
            'example#InvalidMessage',
            'example#InvalidMessage',
            'id',
            '1'
        ];
        yield 'relative operation error' => [
            'InvalidMessage',
            'example#InvalidMessage',
            'id',
            '2'
        ];
        yield 'relative service error' => [
            'ServiceFailure',
            'example#ServiceFailure',
            'why',
            'IDK'
        ];
    }

    /**
     * @param list<AbstractInterceptor> $interceptors Client interceptors.
     *
     * @return array{DynamicClient, MockHttpClient}
     */
    private function client(array $interceptors = []): array
    {
        $http = $this->httpClient();
        $config = new ClientConfig(
            protocol: new RpcV2CborProtocol(),
            transport: new \Smithy\Http\Client\HttpClientTransport(
                $http
            ),
            endpointResolver: new StaticEndpointResolver(
                new Endpoint('https://example.com')
            ),
            interceptors: new InterceptorChain(...$interceptors)
        );

        return [
            new DynamicClient(
                $this->model(),
                $config,
                protocolId: 'smithy.protocols#rpcv2Cbor'
            ),
            $http
        ];
    }

    private function httpClient(): MockHttpClient
    {
        $http = new MockHttpClient();
        $http->fallbackHandler(
            static fn(RequestInterface $request): ResponseInterface =>
                new Response(
                    headers: ['Smithy-Protocol' => 'rpc-v2-cbor'],
                    body: (string) $request->getBody()
                )
        );

        return $http;
    }

    private function memberValue(
        StructDocument $document,
        string $name
    ): mixed
    {
        return $document->getMemberValue(
            $document->getSchema()->getMember($name)
        );
    }

    /**
     * Encode a definite CBOR map containing string keys and values.
     *
     * @param array<string, string> $members Error envelope members.
     */
    private function errorBody(array $members): string
    {
        $body = \chr(0xA0 | \count($members));
        foreach ($members as $key => $value) {
            $body .= $this->cborText($key) . $this->cborText($value);
        }

        return $body;
    }

    /**
     * Encode a short CBOR text value.
     */
    private function cborText(string $value): string
    {
        $length = \strlen($value);
        if ($length < 24) {
            return \chr(0x60 | $length) . $value;
        }
        if ($length < 256) {
            return "\x78" . \chr($length) . $value;
        }

        throw new \InvalidArgumentException(
            'Test CBOR text values must be shorter than 256 bytes.'
        );
    }

    private function model(
        string $protocolId = 'smithy.protocols#rpcv2Cbor',
        ?array $endpointBdd = null,
        ?array $endpointRuleSet = null,
        array $messageTraits = [],
        array $operationTraits = [],
        array $additionalServiceTraits = []
    ): SmithyModel
    {
        $serviceTraits = [
            $protocolId => [],
            ...$additionalServiceTraits
        ];
        if ($endpointBdd !== null) {
            $serviceTraits['smithy.rules#endpointBdd'] = $endpointBdd;
        }
        if ($endpointRuleSet !== null) {
            $serviceTraits['smithy.rules#endpointRuleSet'] = $endpointRuleSet;
        }

        return SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => [
                'example#Sprockets' => [
                    'type' => 'service',
                    'version' => '2026-08-20',
                    'operations' => [
                        ['target' => 'example#Echo'],
                        ['target' => 'example#EchoBlob'],
                        ['target' => 'example#DefaultedEcho'],
                        ['target' => 'example#Ping'],
                        ['target' => 'example#UploadStream'],
                        ['target' => 'example#Subscribe'],
                        ['target' => 'example#Converse']
                    ],
                    'errors' => [
                        ['target' => 'example#ServiceFailure']
                    ],
                    'traits' => $serviceTraits
                ],
                'example#Echo' => [
                    'type' => 'operation',
                    'input' => ['target' => 'example#Message'],
                    'output' => ['target' => 'example#Message'],
                    'errors' => [
                        ['target' => 'example#InvalidMessage']
                    ],
                    'traits' => $operationTraits
                ],
                'example#EchoBlob' => [
                    'type' => 'operation',
                    'input' => ['target' => 'example#BlobMessage'],
                    'output' => ['target' => 'example#BlobMessage']
                ],
                'example#DefaultedEcho' => [
                    'type' => 'operation',
                    'input' => ['target' => 'example#OptionalMessage'],
                    'output' => ['target' => 'example#OptionalMessage']
                ],
                'example#Ping' => ['type' => 'operation'],
                'example#UploadStream' => [
                    'type' => 'operation',
                    'input' => ['target' => 'example#StreamInput']
                ],
                'example#Subscribe' => [
                    'type' => 'operation',
                    'output' => ['target' => 'example#EventStreamOutput']
                ],
                'example#Converse' => [
                    'type' => 'operation',
                    'input' => ['target' => 'example#EventStreamInput'],
                    'output' => ['target' => 'example#EventStreamOutput']
                ],
                'example#Message' => [
                    'type' => 'structure',
                    'members' => [
                        'message' => [
                            'target' => 'smithy.api#String',
                            'traits' => [
                                'smithy.api#required' => [],
                                ...$messageTraits
                            ]
                        ]
                    ]
                ],
                'example#BlobMessage' => [
                    'type' => 'structure',
                    'members' => [
                        'data' => [
                            'target' => 'smithy.api#Blob',
                            'traits' => ['smithy.api#required' => []]
                        ]
                    ]
                ],
                'example#OptionalMessage' => [
                    'type' => 'structure',
                    'members' => [
                        'message' => [
                            'target' => 'smithy.api#String',
                            'traits' => [
                                'smithy.api#default' => 'default'
                            ]
                        ],
                        'note' => ['target' => 'smithy.api#String']
                    ]
                ],
                'example#StreamInput' => [
                    'type' => 'structure',
                    'members' => [
                        'data' => ['target' => 'example#StreamingBlob']
                    ]
                ],
                'example#EventStreamInput' => [
                    'type' => 'structure',
                    'members' => [
                        'events' => ['target' => 'example#EventStream']
                    ]
                ],
                'example#EventStreamOutput' => [
                    'type' => 'structure',
                    'members' => [
                        'events' => ['target' => 'example#EventStream']
                    ]
                ],
                'example#StreamingBlob' => [
                    'type' => 'blob',
                    'traits' => ['smithy.api#streaming' => []]
                ],
                'example#EventStream' => [
                    'type' => 'union',
                    'members' => [
                        'message' => ['target' => 'smithy.api#String']
                    ],
                    'traits' => ['smithy.api#streaming' => []]
                ],
                'example#InvalidMessage' => [
                    'type' => 'structure',
                    'members' => [
                        'id' => ['target' => 'smithy.api#String']
                    ],
                    'traits' => [
                        'smithy.api#error' => 'client'
                    ]
                ],
                'example#ServiceFailure' => [
                    'type' => 'structure',
                    'members' => [
                        'why' => ['target' => 'smithy.api#String']
                    ],
                    'traits' => [
                        'smithy.api#error' => 'server'
                    ]
                ]
            ]
        ]);
    }

    private static function waiterClient(
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

    private function conventionCollisionModel(): SmithyModel
    {
        return SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => [
                'example#Collisions' => [
                    'type' => 'service',
                    'version' => '2026-10-19',
                    'operations' => [
                        ['target' => 'example#WaitUntilThingReady'],
                        ['target' => 'example#ListThingsPaginated']
                    ],
                    'traits' => [
                        'smithy.protocols#rpcv2Cbor' => []
                    ]
                ],
                'example#WaitUntilThingReady' => [
                    'type' => 'operation',
                    'input' => ['target' => 'example#CollisionInput'],
                    'output' => ['target' => 'example#CollisionOutput']
                ],
                'example#ListThingsPaginated' => [
                    'type' => 'operation',
                    'input' => ['target' => 'example#CollisionInput'],
                    'output' => ['target' => 'example#CollisionOutput']
                ],
                'example#CollisionInput' => [
                    'type' => 'structure',
                    'members' => [
                        'message' => ['target' => 'smithy.api#String']
                    ],
                    'traits' => ['smithy.api#input' => []]
                ],
                'example#CollisionOutput' => [
                    'type' => 'structure',
                    'members' => [
                        'message' => ['target' => 'smithy.api#String']
                    ],
                    'traits' => ['smithy.api#output' => []]
                ]
            ]
        ]);
    }

    /**
     * Create a direct-result endpoint BDD parameterized by host.
     *
     * @return array<string, mixed>
     */
    private function endpointBdd(
        array $properties = [],
        array $conditions = []
    ): array
    {
        return [
            'version' => '1.1',
            'parameters' => [
                'Host' => [
                    'type' => 'String',
                    'required' => true
                ]
            ],
            'conditions' => $conditions,
            'results' => [
                [
                    'type' => 'endpoint',
                    'endpoint' => [
                        'url' => 'https://{Host}',
                        'properties' => $properties
                    ]
                ]
            ],
            'nodes' => '',
            'nodeCount' => 0,
            'root' => 100_000_001
        ];
    }
}
