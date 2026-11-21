<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Dynamic;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Dynamic\{
    DynamicClient,
    DynamicClientFactory,
    DynamicClientRuntime
};
use Smithy\Client\Endpoint\Bytecode\StdFunctions;
use Smithy\Core\Model\SmithyModel;
use Smithy\Protocol\RpcV2\RpcV2CborProtocol;
use Smithy\Tests\Client\Dynamic\Utils\IdentifiedProtocolFixture;
use Smithy\Tests\Client\Fake\TestAuthSchemeFixture;
use Smithy\Tests\Utils\Http\MockHttpClient;

#[CoversClass(DynamicClientFactory::class)]
final class DynamicClientFactoryTest extends TestCase
{
    public function testResolvesClientFromModelPath(): void
    {
        $client = DynamicClientFactory::create(
            model: __DIR__ . '/fixtures/dynamic-model.json',
            runtime: self::runtime(),
            httpClient: new MockHttpClient(),
            endpoint: 'https://example.com'
        );

        self::assertInstanceOf(DynamicClient::class, $client);
        self::assertSame('example#Service', $client->getService()->getId());
        self::assertInstanceOf(
            RpcV2CborProtocol::class,
            $client->getConfig()->getProtocol()
        );
    }

    public function testRequiresEndpointResolution(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'A dynamic client requires an endpoint resolver'
        );

        DynamicClientFactory::create(
            model: __DIR__ . '/fixtures/dynamic-model.json',
            runtime: self::runtime(),
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

        DynamicClientFactory::create(
            model: __DIR__ . '/fixtures/dynamic-model.json',
            runtime: self::runtime(),
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

        DynamicClientFactory::create(
            model: __DIR__ . '/fixtures/dynamic-model.json',
            runtime: self::runtime(),
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

        DynamicClientFactory::create(
            model: __DIR__ . '/fixtures/dynamic-model.json',
            runtime: self::runtime(),
            httpClient: new MockHttpClient(),
            endpoint: 'https://example.com',
            supportedAuthSchemes: [
                new TestAuthSchemeFixture('')
            ]
        );
    }

    public function testCountsRegisteredAuthSchemeTraits(): void
    {
        $model = SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => [
                'example#Service' => [
                    'type' => 'service',
                    'version' => '2026-11-21',
                    'operations' => [['target' => 'example#Call']],
                    'traits' => [
                        'smithy.protocols#rpcv2Cbor' => [],
                        'vendor.auth#token' => [],
                        'vendor.auth#other' => []
                    ]
                ],
                'example#Call' => ['type' => 'operation']
            ]
        ]);

        $client = DynamicClientFactory::create(
            model: $model,
            runtime: self::runtime()->merge(new DynamicClientRuntime(
                authSchemes: [new TestAuthSchemeFixture('vendor.auth#other')]
            )),
            httpClient: new MockHttpClient(),
            endpoint: 'https://example.com',
            supportedAuthSchemes: [
                new TestAuthSchemeFixture('vendor.auth#token')
            ]
        );

        self::assertSame(
            ['vendor.auth#other', 'vendor.auth#token'],
            $client->getService()
                ->getOperation('Call')
                ?->getEffectiveAuthSchemes()
        );
    }

    private static function runtime(): DynamicClientRuntime
    {
        return new DynamicClientRuntime(
            protocols: [
                RpcV2CborProtocol::ID => new RpcV2CborProtocol()
            ],
            endpointFunctions: StdFunctions::all()
        );
    }
}
