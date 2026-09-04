<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Dynamic;

use PHPUnit\Framework\Attributes\{CoversClass, DataProvider};
use PHPUnit\Framework\TestCase;
use Smithy\Client\Dynamic\DynamicClientRuntime;
use Smithy\Core\Serde\SchemaTraitRegistry;
use Smithy\Protocol\RpcV2\RpcV2CborProtocol;
use Smithy\Tests\Client\Endpoint\Bytecode\Fixture\VariadicFunctionFixture;
use Smithy\Tests\Client\Dynamic\Utils\IdentifiedProtocolFixture;
use Smithy\Tests\Client\Fake\TestAuthSchemeFixture;

#[CoversClass(DynamicClientRuntime::class)]
final class DynamicClientRuntimeTest extends TestCase
{
    public function testProvidesDefaultRuntimeImplementations(): void
    {
        $runtime = DynamicClientRuntime::defaults();

        self::assertInstanceOf(
            RpcV2CborProtocol::class,
            $runtime->getProtocols()['smithy.protocols#rpcv2Cbor']
        );
        self::assertNotEmpty($runtime->getEndpointFunctions());
    }

    public function testReturnsFreshDefaultRuntimeImplementations(): void
    {
        $first = DynamicClientRuntime::defaults();
        $second = DynamicClientRuntime::defaults();

        self::assertNotSame($first, $second);
        self::assertNotSame(
            $first->getProtocols()['smithy.protocols#rpcv2Cbor'],
            $second->getProtocols()['smithy.protocols#rpcv2Cbor']
        );
    }

    public function testMergesRegisteredRuntimeImplementations(): void
    {
        $protocol = new IdentifiedProtocolFixture('example#protocol');
        $authScheme = new TestAuthSchemeFixture('example#auth');
        $function = new VariadicFunctionFixture();
        $traits = new SchemaTraitRegistry();
        $runtime = DynamicClientRuntime::defaults()->merge(
            new DynamicClientRuntime(
                protocols: ['example#protocol' => $protocol],
                authSchemes: [$authScheme],
                endpointFunctions: [
                    $function->getName() => $function
                ],
                endpointAuthSchemeIds: [
                    'custom' => 'example#auth'
                ],
                protocolPreference: ['example#protocol'],
                traitRegistry: $traits,
                errorDiscriminatorTraits: [
                    'example#errorCode' => 'code'
                ]
            )
        );

        self::assertSame(
            $protocol,
            $runtime->getProtocols()['example#protocol']
        );
        self::assertSame([$authScheme], $runtime->getAuthSchemes());
        self::assertSame(
            $function,
            $runtime->getEndpointFunctions()[$function->getName()]
        );
        self::assertSame(
            ['custom' => 'example#auth'],
            $runtime->getEndpointAuthSchemeIds()
        );
        self::assertSame(
            'example#protocol',
            $runtime->getProtocolPreference()[0]
        );
        self::assertSame($traits, $runtime->getTraitRegistry());
        self::assertSame(
            ['example#errorCode' => 'code'],
            $runtime->getErrorDiscriminatorTraits()
        );
    }

    /**
     * @param \Closure(): DynamicClientRuntime $create
     */
    #[DataProvider('invalidRegistrationProvider')]
    public function testRejectsInvalidRegistrations(
        string $message,
        \Closure $create
    ): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $create();
    }

    /**
     * @return iterable<string, array{
     *     string,
     *     \Closure(): DynamicClientRuntime
     * }>
     */
    public static function invalidRegistrationProvider(): iterable
    {
        yield 'protocol ID' => [
            'Protocol mappings must contain protocol shape IDs',
            static fn(): DynamicClientRuntime => new DynamicClientRuntime(
                protocols: ['' => new RpcV2CborProtocol()]
            )
        ];
        yield 'protocol ID mismatch' => [
            'mapped to matching ProtocolInterface instances',
            static fn(): DynamicClientRuntime => new DynamicClientRuntime(
                protocols: [
                    'example#other' =>
                        new IdentifiedProtocolFixture('example#protocol')
                ]
            )
        ];
        yield 'authentication scheme ID' => [
            'Authentication scheme IDs cannot be empty.',
            static fn(): DynamicClientRuntime => new DynamicClientRuntime(
                authSchemes: [new TestAuthSchemeFixture('')]
            )
        ];
        yield 'endpoint function name' => [
            'Endpoint function mappings must contain function names',
            static fn(): DynamicClientRuntime => new DynamicClientRuntime(
                endpointFunctions: [
                    '' => new VariadicFunctionFixture()
                ]
            )
        ];
        yield 'endpoint authentication alias' => [
            'Endpoint auth scheme mappings must contain names',
            static fn(): DynamicClientRuntime => new DynamicClientRuntime(
                endpointAuthSchemeIds: ['' => 'example#auth']
            )
        ];
        yield 'protocol preference' => [
            'Protocol preference entries must be protocol shape IDs.',
            static fn(): DynamicClientRuntime => new DynamicClientRuntime(
                protocolPreference: ['']
            )
        ];
        yield 'error discriminator trait' => [
            'Error discriminator trait mappings must contain',
            static fn(): DynamicClientRuntime => new DynamicClientRuntime(
                errorDiscriminatorTraits: ['' => 'code']
            )
        ];
    }
}
