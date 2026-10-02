<?php

declare(strict_types=1);

namespace Smithy\Tests\Protocol\RpcV2\Cbor;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Smithy\Client\Auth\{
    NoAuthScheme,
    StaticAuthSchemeResolver
};
use Smithy\Client\Endpoint\{
    Endpoint,
    StaticEndpointResolver
};
use Smithy\Codec\Cbor\CborDeserializer;
use Smithy\Core\Exception\ApiException;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\Document\{
    AbstractDocument,
    BooleanDocument,
    FloatDocument,
    IntegerDocument,
    ListDocument,
    MapDocument,
    NullDocument,
    StringDocument
};
use Smithy\Core\Shape\{
    EnumInterface,
    OperationInterface
};
use Smithy\Core\Shape\Utils\ArrayShapeSerializer;
use Smithy\Http\Message\{
    Response,
    StringRequestStream
};
use Smithy\Protocol\RpcV2\RpcV2CborProtocol;
use Smithy\ProtocolTests\RpcV2Cbor\{
    RpcV2CborClient,
    RpcV2CborClientConfig
};
use Smithy\Tests\Protocol\ProtocolTestLoader;
use Smithy\Tests\Utils\Http\MockHttpClient;

/**
 * Run the official RPC v2 CBOR client tests against generated code.
 */
final class GeneratedClientProtocolTest extends TestCase
{
    private const MODEL_PATH = __DIR__
        . '/../../../../tools/codegen-output/models/rpcv2-cbor.json';

    private const SKIPPED_TESTS = [];

    #[DataProvider('requestCases')]
    public function testRequestSerialization(
        OperationInterface $operation,
        array $test
    ): void
    {
        self::skipUnsupportedTest($test);

        $endpoint = isset($test['host'])
            ? 'https://' . $test['host']
            : 'https://example.amazonaws.com';
        $http = new MockHttpClient();
        $inputClass = $operation->getInputClass();
        $input = self::createInput(
            $inputClass,
            $test['params'] ?? []
        );
        $request = self::captureRequest(
            self::createClient($http, $endpoint),
            $http,
            $operation,
            $input
        );

        $this->assertRequestMatches($request, $test);
    }

    /**
     * @return iterable<string, array{OperationInterface, array}>
     */
    public static function requestCases(): iterable
    {
        $loader = self::loader();
        foreach ($loader->getOperationIds() as $operationId) {
            $operation = self::resolveOperation($operationId);
            foreach ($loader->getRequestTests($operationId) as $test) {
                yield $test['id'] => [$operation, $test];
            }
        }
    }

    #[DataProvider('responseCases')]
    public function testResponseDeserialization(
        OperationInterface $operation,
        array $test,
        array $inputParams
    ): void
    {
        self::skipUnsupportedTest($test);

        $http = new MockHttpClient();
        $body = self::decodeBody($test['body'] ?? null);
        $response = self::response(
            $test['code'],
            $test['headers'] ?? [],
            $body
        );
        $output = self::executeResponse(
            self::createClient(
                $http,
                'https://example.amazonaws.com'
            ),
            $http,
            $operation,
            self::createInput(
                $operation->getInputClass(),
                $inputParams
            ),
            $response
        );
        $actual = $this->normalize(
            ArrayShapeSerializer::toArray($output)
        );
        self::assertSame(
            $operation->getOutputSchema()->id,
            $output->getSchema()->id
        );

        self::assertEquals(
            $test['params'] ?? [],
            $actual,
            'Response members mismatch'
        );
    }

    /**
     * @return iterable<string, array{OperationInterface, array, array}>
     */
    public static function responseCases(): iterable
    {
        $loader = self::loader();
        foreach ($loader->getOperationIds() as $operationId) {
            $operation = self::resolveOperation($operationId);
            $inputParams = self::defaultInputParams(
                $loader,
                $operationId
            );
            foreach ($loader->getResponseTests($operationId) as $test) {
                if (($test['code'] ?? 200) >= 300) {
                    continue;
                }

                yield $test['id'] => [
                    $operation,
                    $test,
                    $inputParams
                ];
            }
        }
    }

    #[DataProvider('errorResponseCases')]
    public function testErrorResponseDeserialization(
        OperationInterface $operation,
        array $test,
        array $inputParams
    ): void
    {
        $http = new MockHttpClient();
        $response = self::response(
            $test['code'],
            $test['headers'] ?? [],
            self::decodeBody($test['body'] ?? null)
        );

        try {
            self::executeResponse(
                self::createClient(
                    $http,
                    'https://example.amazonaws.com'
                ),
                $http,
                $operation,
                self::createInput(
                    $operation->getInputClass(),
                    $inputParams
                ),
                $response
            );
        } catch (\Throwable $error) {
            self::assertInstanceOf(ApiException::class, $error);
            self::assertInstanceOf(
                SerializableStructInterface::class,
                $error
            );
            self::assertSame(
                $test['errorShapeId'],
                $error->getSchema()->id
            );
            $actual = $this->normalize(
                ArrayShapeSerializer::toArray($error)
            );
            self::assertEquals(
                $test['params'] ?? [],
                $actual,
                'Error members mismatch'
            );

            return;
        }

        self::fail("Expected modeled error {$test['errorShapeId']}.");
    }

    /**
     * @return iterable<string, array{OperationInterface, array, array}>
     */
    public static function errorResponseCases(): iterable
    {
        $loader = self::loader();
        foreach ($loader->getOperationIds() as $operationId) {
            $operation = self::resolveOperation($operationId);
            $inputParams = self::defaultInputParams(
                $loader,
                $operationId
            );
            foreach ($loader->getErrorResponseTests($operationId) as $test) {
                yield $test['id'] => [
                    $operation,
                    $test,
                    $inputParams
                ];
            }
        }
    }

    public function testCborBodyComparisonRejectsMissingNestedDefaults(): void
    {
        $expected = \hex2bin(
            'a16864656661756c7473a16d64656661756c74537472696e67'
                . '63666f6f'
        );
        $actual = \hex2bin('a16864656661756c7473a0');
        self::assertIsString($expected);
        self::assertIsString($actual);

        $this->expectException(
            \PHPUnit\Framework\ExpectationFailedException::class
        );

        $this->assertCborBodiesEqual($expected, $actual);
    }

    #[DataProvider('cborTypeSubstitutionCases')]
    public function testCborBodyComparisonRejectsTypeSubstitutions(
        string $expectedHex,
        string $actualHex
    ): void
    {
        $expected = \hex2bin($expectedHex);
        $actual = \hex2bin($actualHex);
        self::assertIsString($expected);
        self::assertIsString($actual);

        $this->expectException(
            \PHPUnit\Framework\ExpectationFailedException::class
        );

        $this->assertCborBodiesEqual($expected, $actual);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function cborTypeSubstitutionCases(): iterable
    {
        yield 'byte string for text string' => ['4161', '6161'];
        yield 'map for array' => ['a0', '80'];
        yield 'integer for float' => ['01', 'f93c00'];
        yield 'tagged for untagged' => ['c101', '01'];
    }

    public function testCborBodyComparisonNormalizesEquivalentEncodings(): void
    {
        $expected = \hex2bin('a36161016162f93c006174c101');
        $actual = \hex2bin(
            'a36174c1fa3f8000006162fa3f80000061611801'
        );
        self::assertIsString($expected);
        self::assertIsString($actual);

        $this->assertCborBodiesEqual($expected, $actual);
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
        self::assertSame('', $request->getUri()->getQuery());

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
            self::assertSame($expected, $actual, 'CBOR body mismatch');

            return;
        }

        $this->assertCborBodiesEqual($expected, $actual);
    }

    private function assertCborBodiesEqual(
        string $expected,
        string $actual
    ): void
    {
        self::assertSame(
            $this->decodeCbor($expected),
            $this->decodeCbor($actual),
            'CBOR body mismatch'
        );
    }

    private function decodeCbor(string $body): mixed
    {
        $deserializer = new CborDeserializer(
            new StringRequestStream($body)
        );

        return $deserializer->decodeComparableValue();
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
        if ($value instanceof AbstractDocument) {
            return $this->unwrapDocument($value);
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

    private function unwrapDocument(AbstractDocument $document): mixed
    {
        return match (true) {
            $document instanceof MapDocument => \array_map(
                fn(?AbstractDocument $value): mixed =>
                    $value === null ? null : $this->unwrapDocument($value),
                $document->entries
            ),
            $document instanceof ListDocument => \array_map(
                fn(?AbstractDocument $value): mixed =>
                    $value === null ? null : $this->unwrapDocument($value),
                $document->items
            ),
            $document instanceof StringDocument => $document->value,
            $document instanceof BooleanDocument => $document->value,
            $document instanceof IntegerDocument => $document->value,
            $document instanceof FloatDocument => $document->value,
            $document instanceof NullDocument => null,
            default => $document
        };
    }

    private static function createClient(
        MockHttpClient $http,
        string $endpoint
    ): RpcV2CborClient
    {
        return new RpcV2CborClient(
            new RpcV2CborClientConfig(
                protocol: new RpcV2CborProtocol(),
                httpClient: $http,
                endpointResolver: new StaticEndpointResolver(
                    new Endpoint($endpoint)
                ),
                supportedAuthSchemes: [NoAuthScheme::instance()],
                authSchemeResolver: StaticAuthSchemeResolver::noAuth()
            )
        );
    }

    private static function captureRequest(
        RpcV2CborClient $client,
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
        RpcV2CborClient $client,
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

    /**
     * @param class-string<SerializableStructInterface> $inputClass
     */
    private static function createInput(
        string $inputClass,
        array $params
    ): SerializableStructInterface
    {
        try {
            return ArrayShapeSerializer::fromArray($inputClass, $params);
        } catch (\ArgumentCountError|\TypeError $error) {
            if ($params !== []) {
                throw $error;
            }

            return new $inputClass();
        }
    }

    private static function loader(): ProtocolTestLoader
    {
        static $loader;

        return $loader ??= new ProtocolTestLoader(self::MODEL_PATH);
    }

    private static function resolveOperation(
        string $operationId
    ): OperationInterface
    {
        $separator = \strrpos($operationId, '#');
        $name = $separator === false
            ? $operationId
            : \substr($operationId, $separator + 1);
        $class = 'Smithy\\ProtocolTests\\RpcV2Cbor\\Operation\\'
            . $name
            . 'Operation';

        if (!\is_a($class, OperationInterface::class, true)) {
            throw new \LogicException(
                "Generated operation class not found: {$class}"
            );
        }

        return $class::instance();
    }

    private static function defaultInputParams(
        ProtocolTestLoader $loader,
        string $operationId
    ): array
    {
        $tests = $loader->getRequestTests($operationId);

        return $tests[0]['params'] ?? [];
    }

    private static function decodeBody(?string $body): ?string
    {
        return $body === null || $body === ''
            ? $body
            : \base64_decode($body, true);
    }

    private static function skipUnsupportedTest(array $test): void
    {
        $reason = self::SKIPPED_TESTS[$test['id']] ?? null;
        if ($reason !== null) {
            self::markTestSkipped($reason);
        }
    }
}
