<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Protocol\RpcV2\Json;

use PHPUnit\Framework\Attributes\DataProvider;
use Smithy\Client\ClientInterface;
use Smithy\Client\Auth\{
    NoAuthScheme,
    StaticAuthSchemeResolver
};
use Smithy\Client\Endpoint\{
    Endpoint,
    StaticEndpointResolver
};
use Smithy\Core\Exception\ApiException;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Core\Shape\Utils\ArrayShapeSerializer;
use Smithy\Http\Message\Response;
use Smithy\Protocol\Exception\ProtocolException;
use Smithy\ProtocolTests\RpcV2Json\{
    RpcV2JsonClient,
    RpcV2JsonClientConfig,
    RpcV2JsonService
};
use Smithy\ProtocolTests\RpcV2Json\Exception\{
    InvalidGreetingException,
    RpcV2JsonException
};
use Smithy\Tests\Protocol\RpcV2\Json\Support\RpcV2JsonClientProtocolTestCase;
use Smithy\Tests\Utils\Http\MockHttpClient;

/**
 * Run the official RPC v2 JSON client tests against generated code.
 */
final class GeneratedClientProtocolTest extends RpcV2JsonClientProtocolTestCase
{
    public function testAttachesGeneratedServiceMetadata(): void
    {
        $client = $this->createClient(
            new MockHttpClient(),
            'https://example.com'
        );

        self::assertSame(
            RpcV2JsonService::instance(),
            $client->getConfig()->service
        );
    }

    public function testGeneratedServiceExceptionCatchesModeledAndUnmodeledErrors(): void
    {
        $http = new MockHttpClient();
        $client = $this->createClient($http, 'https://example.com');
        $http->queueResponse(new Response(
            400,
            [
                'Smithy-Protocol' => 'rpc-v2-json',
                'Content-Type' => 'application/json'
            ],
            '{"__type":"smithy.protocoltests.rpcv2Json#InvalidGreeting",'
                . '"Message":"Hi"}'
        ));

        try {
            $client->greetingWithErrors();
            self::fail('Expected a modeled service exception.');
        } catch (RpcV2JsonException $error) {
            self::assertInstanceOf(InvalidGreetingException::class, $error);
        }

        $http->queueResponse(new Response(
            400,
            [
                'Smithy-Protocol' => 'rpc-v2-json',
                'Content-Length' => '0'
            ]
        ));

        try {
            $client->greetingWithErrors();
            self::fail('Expected an unmodeled service exception.');
        } catch (RpcV2JsonException $error) {
            self::assertSame(
                RpcV2JsonException::class,
                $error::class
            );
        }
    }

    public function testRejectsMissingProtocolHeaderOnSuccess(): void
    {
        $http = new MockHttpClient();
        $client = $this->createClient($http, 'https://example.com');
        $http->queueResponse(new Response(
            200,
            ['Content-Type' => 'application/json'],
            '{}'
        ));

        $this->expectException(ProtocolException::class);
        $this->expectExceptionMessage(
            'invalid Smithy-Protocol header for HTTP 200'
        );

        $client->emptyInputOutput();
    }

    public function testRejectsMismatchedProtocolHeaderOnError(): void
    {
        $http = new MockHttpClient();
        $client = $this->createClient($http, 'https://example.com');
        $http->queueResponse(new Response(
            400,
            [
                'Smithy-Protocol' => 'rpc-v2-cbor',
                'Content-Type' => 'application/json'
            ],
            '{"__type":"smithy.protocoltests.rpcv2Json#InvalidGreeting"}'
        ));

        $this->expectException(RpcV2JsonException::class);
        $this->expectExceptionMessage(
            'Malformed rpc-v2-json error response (HTTP 400).'
        );

        $client->greetingWithErrors();
    }

    public function testJsonBodyComparisonRejectsMissingNestedDefaults(): void
    {
        $this->expectException(
            \PHPUnit\Framework\ExpectationFailedException::class
        );

        $this->assertJsonBodiesEqual(
            '{"defaults":{"defaultString":"foo"}}',
            '{"defaults":{}}'
        );
    }

    #[DataProvider('jsonTypeSubstitutionCases')]
    public function testJsonBodyComparisonRejectsTypeSubstitutions(
        string $expected,
        string $actual
    ): void
    {
        $this->expectException(
            \PHPUnit\Framework\ExpectationFailedException::class
        );

        $this->assertJsonBodiesEqual($expected, $actual);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function jsonTypeSubstitutionCases(): iterable
    {
        yield 'number for string' => ['{"value":"42"}', '{"value":42}'];
        yield 'array for object' => ['{"value":{}}', '{"value":[]}'];
        yield 'null for missing value' => ['{"value":null}', '{}'];
        yield 'boolean for integer' => ['{"value":true}', '{"value":1}'];
    }

    public function testJsonBodyComparisonNormalizesEquivalentEncodings(): void
    {
        $this->assertJsonBodiesEqual(
            '{"a":1,"b":{"c":2}}',
            "{\n  \"b\": {\"c\": 2},\n  \"a\": 1\n}"
        );
    }

    protected function createClient(
        MockHttpClient $http,
        string $endpoint
    ): ClientInterface
    {
        return new RpcV2JsonClient(
            new RpcV2JsonClientConfig(
                transport: new \Smithy\Http\Client\HttpClientTransport($http),
                endpointResolver: new StaticEndpointResolver(
                    new Endpoint($endpoint)
                ),
                supportedAuthSchemes: [NoAuthScheme::instance()],
                authSchemeResolver: StaticAuthSchemeResolver::noAuth()
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
        $class = 'Smithy\\ProtocolTests\\RpcV2Json\\Operation\\'
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
        $inputClass = $operation->getInputClass();

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
        self::assertInstanceOf(SerializableStructInterface::class, $error);

        return $error;
    }
}
