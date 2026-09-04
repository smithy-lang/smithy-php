<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Dynamic;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Dynamic\{
    DynamicClient,
    DynamicClientResolver
};
use Smithy\Core\Model\SmithyModel;
use Smithy\Protocol\RpcV2\RpcV2CborProtocol;
use Smithy\Tests\Client\Dynamic\Utils\IdentifiedProtocolFixture;
use Smithy\Tests\Client\Fake\TestAuthSchemeFixture;
use Smithy\Tests\Utils\Http\MockHttpClient;

#[CoversClass(DynamicClientResolver::class)]
final class DynamicClientResolverTest extends TestCase
{
    public function testResolvesClientFromModelPath(): void
    {
        $client = DynamicClientResolver::resolve(
            model: __DIR__ . '/fixtures/dynamic-model.json',
            httpClient: new MockHttpClient(),
            endpoint: 'https://example.com'
        );

        self::assertInstanceOf(DynamicClient::class, $client);
        self::assertSame('example#Service', $client->getService()->getId());
        self::assertInstanceOf(
            RpcV2CborProtocol::class,
            $client->getConfig()->protocol
        );
    }

    public function testRejectsUnrelatedClientClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Dynamic client class must extend '
            . DynamicClient::class
        );

        DynamicClientResolver::resolve(
            model: SmithyModel::fromArray([
                'smithy' => '2.0',
                'shapes' => []
            ]),
            clientClass: \stdClass::class
        );
    }

    public function testRequiresEndpointResolution(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'A dynamic client requires an endpoint resolver'
        );

        DynamicClientResolver::resolve(
            model: __DIR__ . '/fixtures/dynamic-model.json',
            httpClient: new MockHttpClient()
        );
    }

    public function testRejectsExplicitUnmodeledProtocol(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Protocol example.protocols#unmodeled is not modeled by service '
            . 'example#Service.'
        );

        DynamicClientResolver::resolve(
            model: __DIR__ . '/fixtures/dynamic-model.json',
            protocolId: 'example.protocols#unmodeled',
            protocols: [
                'example.protocols#unmodeled' =>
                    new IdentifiedProtocolFixture(
                        'example.protocols#unmodeled'
                    )
            ],
            httpClient: new MockHttpClient(),
            endpoint: 'https://example.com'
        );
    }

    public function testRejectsMismatchedExplicitProtocolId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Protocol implementation example.protocols#custom does not '
            . 'match configured protocol smithy.protocols#rpcv2Cbor.'
        );

        DynamicClientResolver::resolve(
            model: __DIR__ . '/fixtures/dynamic-model.json',
            protocolId: RpcV2CborProtocol::ID,
            protocol: new IdentifiedProtocolFixture(
                'example.protocols#custom'
            )
        );
    }

    public function testValidatesDirectRuntimeRegistrations(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Authentication scheme IDs cannot be empty.'
        );

        DynamicClientResolver::resolve(
            model: __DIR__ . '/fixtures/dynamic-model.json',
            httpClient: new MockHttpClient(),
            endpoint: 'https://example.com',
            supportedAuthSchemes: [
                new TestAuthSchemeFixture('')
            ]
        );
    }
}
