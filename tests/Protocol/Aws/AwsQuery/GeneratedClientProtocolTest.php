<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Protocol\Aws\AwsQuery;

use Smithy\Client\Auth\{
    NoAuthScheme,
    StaticAuthSchemeResolver
};
use Smithy\Client\ClientInterface;
use Smithy\Client\Endpoint\StaticEndpointResolver;
use Smithy\Client\Plugin\{
    IdempotencyTokenPlugin,
    PluginCollection,
    RequestCompressionPlugin
};
use Smithy\Core\Endpoint\Endpoint;
use Smithy\Core\Exception\ApiException;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Core\Shape\Utils\ArrayShapeSerializer;
use Smithy\Http\Client\HttpClientTransport;
use Smithy\Http\Message\{
    HttpTransportRequest,
    Response
};
use Smithy\Protocol\Aws\{
    AwsProtocolException,
    AwsQueryProtocol
};
use Smithy\ProtocolTests\AwsQuery\{
    AwsQueryClient,
    AwsQueryClientConfig,
    AwsQueryService
};
use Smithy\ProtocolTests\AwsQuery\Exception\AwsQueryException;
use Smithy\ProtocolTests\AwsQuery\Operation\NoInputAndNoOutputOperation;
use Smithy\ProtocolTests\AwsQuery\Shape\Unit;
use Smithy\Tests\Protocol\Aws\AwsQuery\Support\AwsQueryClientProtocolTestCase;
use Smithy\Tests\Utils\Http\MockHttpClient;

/**
 * Run the official AWS Query client tests against generated code.
 */
final class GeneratedClientProtocolTest extends AwsQueryClientProtocolTestCase
{
    public function testReportsProtocolId(): void
    {
        self::assertSame(
            AwsQueryProtocol::ID,
            (new AwsQueryProtocol(AwsQueryService::version()))->getId()
        );
    }

    public function testFramesActionAndVersionParameters(): void
    {
        $request = HttpTransportRequest::expect(
            (new AwsQueryProtocol(
                AwsQueryService::version()
            ))->serializeRequest(
                NoInputAndNoOutputOperation::instance(),
                new Unit()
            )
        )->request;

        self::assertSame('POST', $request->getMethod());
        self::assertSame('/', $request->getUri()->getPath());
        self::assertSame(
            'application/x-www-form-urlencoded',
            $request->getHeaderLine('Content-Type')
        );
        self::assertSame(
            'Action=NoInputAndNoOutput&Version=2020-01-08',
            (string) $request->getBody()
        );
    }

    public function testAttachesGeneratedServiceMetadata(): void
    {
        $client = $this->createClient(
            new MockHttpClient(),
            'https://example.com'
        );

        self::assertSame(
            AwsQueryService::instance(),
            $client->getConfig()->service
        );
    }

    public function testCreatesUnmodeledAwsProtocolError(): void
    {
        $http = new MockHttpClient();
        $client = $this->createClient($http, 'https://example.com');
        $http->queueResponse(new Response(
            400,
            ['Content-Type' => 'application/xml'],
            <<<'XML'
<ErrorResponse>
    <Error>
        <Code>UnmodeledError</Code>
        <Message>Something went wrong</Message>
    </Error>
    <RequestId>request-id</RequestId>
</ErrorResponse>
XML
        ));

        try {
            $client->greetingWithErrors();
        } catch (AwsQueryException $error) {
            self::assertInstanceOf(AwsProtocolException::class, $error);
            self::assertSame('Something went wrong', $error->getMessage());
            self::assertSame('UnmodeledError', $error->getAwsErrorCode());
            self::assertSame('request-id', $error->getAwsRequestId());
            self::assertSame(400, $error->getHttpStatusCode());
            self::assertTrue($error->isClientFault());

            return;
        }

        self::fail('Expected an unmodeled AWS Query exception.');
    }

    protected function createClient(
        MockHttpClient $http,
        string $endpoint
    ): ClientInterface
    {
        return new AwsQueryClient(
            new AwsQueryClientConfig(
                transport: new HttpClientTransport($http),
                endpointResolver: new StaticEndpointResolver(
                    new Endpoint($endpoint)
                ),
                protocol: new AwsQueryProtocol(
                    AwsQueryService::version()
                ),
                supportedAuthSchemes: [NoAuthScheme::instance()],
                authSchemeResolver: StaticAuthSchemeResolver::noAuth(),
                context: [
                    IdempotencyTokenPlugin::CONFIG_IDEMPOTENCY_TOKEN_PROVIDER
                        => '00000000-0000-4000-8000-000000000000'
                ]
            ),
            new PluginCollection(
                new RequestCompressionPlugin()
            )
        );
    }

    protected function resolveOperation(
        ClientInterface $client,
        string $operationId
    ): OperationInterface
    {
        $separator = \strrpos($operationId, '#');
        $name = $separator === false
            ? $operationId
            : \substr($operationId, $separator + 1);
        $class = 'Smithy\\ProtocolTests\\AwsQuery\\Operation\\'
            . $name
            . 'Operation';

        if (!\is_a($class, OperationInterface::class, true)) {
            throw new \LogicException(
                "Generated operation class not found: {$class}"
            );
        }

        return $class::instance();
    }

    protected function createInput(
        OperationInterface $operation,
        array $params
    ): SerializableStructInterface
    {
        $inputClass = $operation::INPUT_CLASS;

        try {
            return ArrayShapeSerializer::fromArray($inputClass, $params);
        } catch (\ArgumentCountError|\TypeError $error) {
            if ($params !== []) {
                throw $error;
            }

            return new $inputClass();
        }
    }

    protected function expectModeledError(
        \Throwable $error
    ): SerializableStructInterface
    {
        self::assertInstanceOf(ApiException::class, $error);
        self::assertInstanceOf(
            SerializableStructInterface::class,
            $error
        );

        return $error;
    }
}
