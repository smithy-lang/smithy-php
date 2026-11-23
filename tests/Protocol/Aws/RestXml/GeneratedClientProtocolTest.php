<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Protocol\Aws\RestXml;

use Smithy\Auth\NoAuthScheme;
use Smithy\Client\Auth\StaticAuthSchemeResolver;
use Smithy\Client\ClientInterface;
use Smithy\Client\Endpoint\StaticEndpointResolver;
use Smithy\Client\Plugin\IdempotencyTokenPlugin;
use Smithy\Core\Context\Context;
use Smithy\Core\Endpoint\Endpoint;
use Smithy\Core\Exception\ApiException;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Core\Shape\Utils\ArrayShapeSerializer;
use Smithy\Http\Client\HttpClientTransport;
use Smithy\Http\Message\Response;
use Smithy\Protocol\Aws\{
    AwsProtocolException,
    RestXmlProtocol
};
use Smithy\ProtocolTests\RestXml\{
    RestXmlClient,
    RestXmlClientConfig,
    RestXmlService
};
use Smithy\ProtocolTests\RestXml\Exception\RestXmlException;
use Smithy\Tests\Protocol\Aws\RestXml\Support\RestXmlClientProtocolTestCase;
use Smithy\Tests\Utils\Http\MockHttpClient;

/**
 * Run the official Rest XML client tests against generated code.
 */
final class GeneratedClientProtocolTest extends RestXmlClientProtocolTestCase
{
    public function testReportsProtocolId(): void
    {
        self::assertSame(
            RestXmlProtocol::ID,
            (new RestXmlProtocol())->getId()
        );
    }

    public function testAttachesGeneratedServiceMetadata(): void
    {
        $client = $this->createClient(
            new MockHttpClient(),
            'https://example.com'
        );

        self::assertSame(
            RestXmlService::instance(),
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
        } catch (RestXmlException $error) {
            self::assertInstanceOf(AwsProtocolException::class, $error);
            self::assertSame('Something went wrong', $error->getMessage());
            self::assertSame('UnmodeledError', $error->getAwsErrorCode());
            self::assertSame('request-id', $error->getAwsRequestId());
            self::assertSame(400, $error->getHttpStatusCode());
            self::assertTrue($error->isClientFault());

            return;
        }

        self::fail('Expected an unmodeled Rest XML exception.');
    }

    protected function createClient(
        MockHttpClient $http,
        string $endpoint
    ): ClientInterface
    {
        return new RestXmlClient(
            new RestXmlClientConfig(
                transport: new HttpClientTransport($http),
                endpointResolver: new StaticEndpointResolver(
                    new Endpoint($endpoint)
                ),
                protocol: new RestXmlProtocol(),
                supportedAuthSchemes: [NoAuthScheme::instance()],
                authSchemeResolver: StaticAuthSchemeResolver::noAuth(),
                context: (new Context())->with(
                    IdempotencyTokenPlugin::tokenProviderKey(),
                    '00000000-0000-4000-8000-000000000000'
                )
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
        $class = 'Smithy\\ProtocolTests\\RestXml\\Operation\\'
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
