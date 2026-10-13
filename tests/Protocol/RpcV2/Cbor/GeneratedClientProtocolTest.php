<?php

declare(strict_types=1);

namespace Smithy\Tests\Protocol\RpcV2\Cbor;

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
use Smithy\Protocol\RpcV2\RpcV2CborProtocol;
use Smithy\ProtocolTests\RpcV2Cbor\{
    RpcV2CborClient,
    RpcV2CborClientConfig,
    RpcV2CborService
};
use Smithy\Tests\Protocol\RpcV2\Cbor\Support\RpcV2CborClientProtocolTestCase;
use Smithy\Tests\Utils\Http\MockHttpClient;

/**
 * Run the official RPC v2 CBOR client tests against generated code.
 */
final class GeneratedClientProtocolTest extends RpcV2CborClientProtocolTestCase
{
    public function testAttachesGeneratedServiceMetadata(): void
    {
        $client = $this->createClient(
            new MockHttpClient(),
            'https://example.com'
        );

        self::assertSame(
            RpcV2CborService::instance(),
            $client->getConfig()->service
        );
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

    protected function createClient(
        MockHttpClient $http,
        string $endpoint
    ): ClientInterface
    {
        return new RpcV2CborClient(
            new RpcV2CborClientConfig(
                protocol: new RpcV2CborProtocol(),
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
