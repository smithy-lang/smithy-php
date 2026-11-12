<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Protocol\Aws\RestXml\Support;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{
    RequestInterface,
    StreamInterface
};
use Smithy\Client\ClientInterface;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\{
    EnumInterface,
    OperationInterface
};
use Smithy\Core\Shape\Utils\ArrayShapeSerializer;
use Smithy\Http\Message\Response;
use Smithy\Tests\Protocol\ProtocolTestLoader;
use Smithy\Tests\Utils\Http\MockHttpClient;

/**
 * Run the official Rest XML fixtures through a client projection.
 */
abstract class RestXmlClientProtocolTestCase extends TestCase
{
    protected const MODEL_PATH = __DIR__
        . '/../../../../../tools/codegen-output/models/rest-xml.json';

    #[DataProvider('requestCases')]
    final public function testRequestSerialization(
        string $operationId,
        array $test
    ): void
    {
        $endpoint = isset($test['host'])
            ? 'https://' . $test['host']
            : 'https://example.amazonaws.com';
        $http = new MockHttpClient();
        $client = $this->createClient($http, $endpoint);
        $operation = $this->resolveOperation($client, $operationId);
        if ($this->shouldSkipOperation($operation)) {
            $this->markTestSkipped(
                'Dynamic-client streaming is outside the preview scope.'
            );
        }
        $request = self::captureRequest(
            $client,
            $http,
            $operation,
            $this->createInput(
                $operation,
                self::fixtureParams($test)
            )
        );

        $this->assertRequestMatches($request, $test);
    }

    /**
     * @return iterable<string, array{string, array}>
     */
    final public static function requestCases(): iterable
    {
        $loader = self::loader();
        foreach ($loader->getOperationIds() as $operationId) {
            foreach ($loader->getRequestTests($operationId) as $test) {
                yield $test['id'] => [$operationId, $test];
            }
        }
    }

    #[DataProvider('responseCases')]
    final public function testResponseDeserialization(
        string $operationId,
        array $test,
        array $inputParams
    ): void
    {
        $http = new MockHttpClient();
        $client = $this->createClient(
            $http,
            'https://example.amazonaws.com'
        );
        $operation = $this->resolveOperation($client, $operationId);
        if ($this->shouldSkipOperation($operation)) {
            $this->markTestSkipped(
                'Dynamic-client streaming is outside the preview scope.'
            );
        }
        $output = self::executeResponse(
            $client,
            $http,
            $operation,
            $this->createInput($operation, $inputParams),
            self::response(
                $test['code'],
                $test['headers'] ?? [],
                self::decodeBody($test['body'] ?? null)
            )
        );

        self::assertSame(
            $operation->getOutputSchema()->id,
            $output->getSchema()->id
        );
        $actual = $this->normalize(
            ArrayShapeSerializer::toArray($output)
        );
        foreach (self::fixtureParams($test) as $name => $expected) {
            self::assertEquals(
                $expected,
                $actual[$name] ?? null,
                "Response member '{$name}' mismatch"
            );
        }
    }

    /**
     * @return iterable<string, array{string, array, array}>
     */
    final public static function responseCases(): iterable
    {
        $loader = self::loader();
        foreach ($loader->getOperationIds() as $operationId) {
            $inputParams = self::defaultInputParams(
                $loader,
                $operationId
            );
            foreach ($loader->getResponseTests($operationId) as $test) {
                if (($test['code'] ?? 200) >= 300) {
                    continue;
                }

                yield $test['id'] => [
                    $operationId,
                    $test,
                    $inputParams
                ];
            }
        }
    }

    #[DataProvider('errorResponseCases')]
    final public function testErrorResponseDeserialization(
        string $operationId,
        array $test,
        array $inputParams
    ): void
    {
        $http = new MockHttpClient();
        $client = $this->createClient(
            $http,
            'https://example.amazonaws.com'
        );
        $operation = $this->resolveOperation($client, $operationId);
        if ($this->shouldSkipOperation($operation)) {
            $this->markTestSkipped(
                'Dynamic-client streaming is outside the preview scope.'
            );
        }
        $response = self::response(
            $test['code'],
            $test['headers'] ?? [],
            self::decodeBody($test['body'] ?? null)
        );

        try {
            self::executeResponse(
                $client,
                $http,
                $operation,
                $this->createInput($operation, $inputParams),
                $response
            );
        } catch (\Throwable $error) {
            $modeledError = $this->expectModeledError($error);
            self::assertSame(
                $test['errorShapeId'],
                $modeledError->getSchema()->id
            );
            self::assertEquals(
                $test['params'] ?? [],
                $this->normalize(
                    ArrayShapeSerializer::toArray($modeledError)
                ),
                'Error members mismatch'
            );

            return;
        }

        self::fail("Expected modeled error {$test['errorShapeId']}.");
    }

    /**
     * @return iterable<string, array{string, array, array}>
     */
    final public static function errorResponseCases(): iterable
    {
        $loader = self::loader();
        foreach ($loader->getOperationIds() as $operationId) {
            $inputParams = self::defaultInputParams(
                $loader,
                $operationId
            );
            foreach ($loader->getErrorResponseTests($operationId) as $test) {
                yield $test['id'] => [
                    $operationId,
                    $test,
                    $inputParams
                ];
            }
        }
    }

    abstract protected function createClient(
        MockHttpClient $http,
        string $endpoint
    ): ClientInterface;

    abstract protected function resolveOperation(
        ClientInterface $client,
        string $operationId
    ): OperationInterface;

    abstract protected function createInput(
        OperationInterface $operation,
        array $params
    ): SerializableStructInterface;

    abstract protected function expectModeledError(
        \Throwable $error
    ): SerializableStructInterface;

    protected function shouldSkipOperation(
        OperationInterface $operation
    ): bool
    {
        return false;
    }

    final protected function assertXmlBodiesEqual(
        string $expected,
        string $actual
    ): void
    {
        self::assertXmlStringEqualsXmlString($expected, $actual);
    }

    private function assertRequestMatches(
        RequestInterface $request,
        array $test
    ): void
    {
        self::assertSame($test['method'], $request->getMethod());
        self::assertSame(
            $test['uri'],
            $request->getUri()->getPath(),
            'URI path mismatch'
        );
        self::assertSame(
            self::queryPairs($test['queryParams'] ?? []),
            self::queryPairs($request->getUri()->getQuery()),
            'Query parameters mismatch'
        );
        foreach ($test['forbidQueryParams'] ?? [] as $name) {
            self::assertStringNotContainsString(
                \rawurlencode((string) $name) . '=',
                $request->getUri()->getQuery()
            );
        }

        foreach ($test['headers'] ?? [] as $name => $value) {
            self::assertSame(
                $value,
                $request->getHeaderLine((string) $name),
                "Header '{$name}' value mismatch"
            );
        }
        foreach ($test['forbidHeaders'] ?? [] as $name) {
            self::assertFalse($request->hasHeader((string) $name));
        }
        foreach ($test['requireHeaders'] ?? [] as $name) {
            self::assertTrue($request->hasHeader((string) $name));
        }

        if (!\array_key_exists('body', $test)) {
            return;
        }

        $expected = self::decodeBody($test['body']) ?? '';
        $actual = (string) $request->getBody();
        if ($expected === '' || $actual === '') {
            self::assertSame($expected, $actual, 'Body mismatch');

            return;
        }

        if (($test['bodyMediaType'] ?? null) === 'application/xml'
            || \str_starts_with(\ltrim($expected), '<')
        ) {
            $this->assertXmlBodiesEqual($expected, $actual);

            return;
        }

        self::assertSame($expected, $actual, 'Body mismatch');
    }

    private function normalize(mixed $value): mixed
    {
        if ($value instanceof StreamInterface) {
            return (string) $value;
        }
        if ($value instanceof \DateTimeInterface) {
            $timestamp = (float) $value->format('U.u');

            return $timestamp == (int) $timestamp
                ? (int) $timestamp
                : $timestamp;
        }
        if ($value instanceof EnumInterface) {
            return $value->getValue();
        }
        if (\is_float($value)) {
            return match (true) {
                \is_nan($value) => 'NaN',
                \is_infinite($value) && $value > 0 => 'Infinity',
                \is_infinite($value) => '-Infinity',
                default => $value
            };
        }
        if ($value instanceof SerializableStructInterface) {
            $value = ArrayShapeSerializer::toArray($value);
        } elseif (\is_object($value)
            && \method_exists($value, 'tag')
            && \method_exists($value, 'value')
        ) {
            return [
                $value->tag() => $this->normalize($value->value())
            ];
        }
        if (\is_array($value)) {
            foreach ($value as $key => $member) {
                $value[$key] = $this->normalize($member);
            }
        }

        return $value;
    }

    private static function captureRequest(
        ClientInterface $client,
        MockHttpClient $http,
        OperationInterface $operation,
        SerializableStructInterface $input
    ): RequestInterface
    {
        $sentinel = new \RuntimeException('Request captured.');
        $http->reset()->fallbackException($sentinel);

        try {
            $client->executeOperation($operation, $input);
        } catch (\RuntimeException $error) {
            if ($error !== $sentinel) {
                throw $error;
            }

            return $http->getLastRequest()
                ?? throw new \LogicException('No request was captured.');
        }

        throw new \LogicException('The request reached no HTTP transport.');
    }

    private static function executeResponse(
        ClientInterface $client,
        MockHttpClient $http,
        OperationInterface $operation,
        SerializableStructInterface $input,
        Response $response
    ): SerializableStructInterface
    {
        $http->reset()->queueResponse($response);

        return $client->executeOperation($operation, $input);
    }

    private static function response(
        int $status,
        array $headers,
        ?string $body
    ): Response
    {
        $response = new Response($status, $headers, $body);
        if (!$response->hasHeader('Content-Length')
            && $response->getBody()->getSize() !== null
        ) {
            $response = $response->withHeader(
                'Content-Length',
                (string) $response->getBody()->getSize()
            );
        }

        return $response;
    }

    private static function loader(): ProtocolTestLoader
    {
        static $loader;

        return $loader ??= new ProtocolTestLoader(self::MODEL_PATH);
    }

    private static function defaultInputParams(
        ProtocolTestLoader $loader,
        string $operationId
    ): array
    {
        $tests = $loader->getRequestTests($operationId);

        return isset($tests[0]) ? self::fixtureParams($tests[0]) : [];
    }

    private static function decodeBody(?string $body): ?string
    {
        return $body;
    }

    private static function fixtureParams(array $test): array
    {
        return $test['params'] ?? [];
    }

    /**
     * @param list<string>|string $query
     * @return list<string>
     */
    private static function queryPairs(array|string $query): array
    {
        $pairs = \is_array($query)
            ? $query
            : ($query === '' ? [] : \explode('&', $query));
        foreach ($pairs as &$pair) {
            $pair = (string) $pair;
        }
        unset($pair);
        \sort($pairs, \SORT_STRING);

        return $pairs;
    }
}
