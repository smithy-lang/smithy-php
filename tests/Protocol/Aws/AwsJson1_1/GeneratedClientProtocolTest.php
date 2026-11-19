<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Protocol\Aws\AwsJson1_1;

use Smithy\Auth\NoAuthScheme;
use Smithy\Client\Auth\StaticAuthSchemeResolver;
use Smithy\Client\ClientInterface;
use Smithy\Client\Retry\NoRetryStrategy;
use Smithy\Client\Endpoint\StaticEndpointResolver;
use Smithy\Client\Plugin\{
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
    AwsJson1_1Protocol,
    AwsProtocolException
};
use Smithy\ProtocolTests\AwsJson1_1\{
    AwsJson1_1Client,
    AwsJson1_1ClientConfig,
    AwsJson1_1Service
};
use Smithy\ProtocolTests\AwsJson1_1\Exception\AwsJson1_1Exception;
use Smithy\ProtocolTests\AwsJson1_1\Operation\EmptyOperationOperation;
use Smithy\ProtocolTests\AwsJson1_1\Shape\Unit;
use Smithy\Tests\Protocol\Aws\AwsJson1_1\Support\AwsJson1_1ClientProtocolTestCase;
use Smithy\Tests\Utils\Http\MockHttpClient;

/**
 * Run the official AWS JSON 1.1 client tests against generated code.
 */
final class GeneratedClientProtocolTest extends AwsJson1_1ClientProtocolTestCase
{
    public function testReportsProtocolId(): void
    {
        self::assertSame(
            AwsJson1_1Protocol::ID,
            (new AwsJson1_1Protocol())->getId()
        );
    }

    public function testUsesConfiguredTargetPrefix(): void
    {
        $request = HttpTransportRequest::expect(
            (new AwsJson1_1Protocol('CustomService'))->serializeRequest(
                EmptyOperationOperation::instance(),
                new Unit()
            )
        )->request;

        self::assertSame(
            'CustomService.EmptyOperation',
            $request->getHeaderLine('X-Amz-Target')
        );
    }

    public function testAttachesGeneratedServiceMetadata(): void
    {
        $client = $this->createClient(
            new MockHttpClient(),
            'https://example.com'
        );

        self::assertSame(
            AwsJson1_1Service::instance(),
            $client->getConfig()->service
        );
    }

    public function testCreatesUnmodeledAwsProtocolError(): void
    {
        $http = new MockHttpClient();
        $client = $this->createClient($http, 'https://example.com');
        $http->queueResponse(new Response(
            400,
            [
                'Content-Type' => 'application/json',
                'x-amzn-errortype' => 'UnmodeledError',
                'x-amzn-requestid' => 'request-id'
            ],
            '{"message":"Something went wrong"}'
        ));

        try {
            $client->greetingWithErrors();
        } catch (AwsJson1_1Exception $error) {
            self::assertInstanceOf(AwsProtocolException::class, $error);
            self::assertSame('Something went wrong', $error->getMessage());
            self::assertSame('UnmodeledError', $error->getAwsErrorCode());
            self::assertSame('request-id', $error->getAwsRequestId());
            self::assertSame(400, $error->getHttpStatusCode());
            self::assertTrue($error->isClientFault());

            return;
        }

        self::fail('Expected an unmodeled AWS JSON 1.1 exception.');
    }

    protected function createClient(
        MockHttpClient $http,
        string $endpoint
    ): ClientInterface
    {
        return new AwsJson1_1Client(
            new AwsJson1_1ClientConfig(
                transport: new HttpClientTransport($http),
                endpointResolver: new StaticEndpointResolver(
                    new Endpoint($endpoint)
                ),
                protocol: new AwsJson1_1Protocol(),
                supportedAuthSchemes: [NoAuthScheme::instance()],
                retryStrategy: NoRetryStrategy::instance(),
                authSchemeResolver: StaticAuthSchemeResolver::noAuth()
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
        $class = 'Smithy\\ProtocolTests\\AwsJson1_1\\Operation\\'
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
