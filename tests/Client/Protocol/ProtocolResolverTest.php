<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Protocol;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Protocol\ProtocolResolver;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\{
    OperationInterface,
    ServiceInterface,
    ShapeType
};
use Smithy\Protocol\RpcV2\RpcV2CborProtocol;
use Smithy\Tests\Client\Dynamic\Utils\IdentifiedProtocolFixture;

#[CoversClass(ProtocolResolver::class)]
final class ProtocolResolverTest extends TestCase
{
    public function testDefaultsResolveRpcV2Cbor(): void
    {
        $protocol = ProtocolResolver::defaults()->resolveProtocol(
            new ProtocolServiceFixture([
                RpcV2CborProtocol::ID => true
            ])
        );

        self::assertInstanceOf(RpcV2CborProtocol::class, $protocol);
    }

    public function testResolvesInPreferenceOrder(): void
    {
        $first = new IdentifiedProtocolFixture('example#first');
        $second = new IdentifiedProtocolFixture('example#second');
        $resolver = new ProtocolResolver(
            protocols: [
                $first->getId() => $first,
                $second->getId() => $second
            ],
            preference: [$second->getId()]
        );

        self::assertSame(
            $second,
            $resolver->resolveProtocol(
                new ProtocolServiceFixture([
                    $first->getId() => true,
                    $second->getId() => true
                ])
            )
        );
    }

    public function testExplicitProtocolOverridesPreference(): void
    {
        $first = new IdentifiedProtocolFixture('example#first');
        $second = new IdentifiedProtocolFixture('example#second');
        $service = new ProtocolServiceFixture([
            $first->getId() => true,
            $second->getId() => true
        ]);

        self::assertSame(
            $first,
            (new ProtocolResolver([
                $second->getId() => $second
            ]))->resolveProtocol($service, protocol: $first)
        );
    }

    public function testExplicitIdSelectsRegisteredProtocol(): void
    {
        $protocol = new IdentifiedProtocolFixture('example#protocol');
        $resolver = new ProtocolResolver([
            $protocol->getId() => $protocol
        ]);

        self::assertSame(
            $protocol,
            $resolver->resolveProtocol(
                new ProtocolServiceFixture([
                    $protocol->getId() => true
                ]),
                protocolId: $protocol->getId()
            )
        );
    }

    public function testRejectsMismatchedExplicitProtocolId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Protocol implementation example#actual does not match '
            . 'configured protocol example#requested.'
        );

        (new ProtocolResolver())->resolveProtocol(
            new ProtocolServiceFixture(['example#actual' => true]),
            protocolId: 'example#requested',
            protocol: new IdentifiedProtocolFixture('example#actual')
        );
    }

    public function testRejectsExplicitUnmodeledProtocol(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Protocol example#unmodeled is not modeled by service '
            . 'example#Service.'
        );

        (new ProtocolResolver())->resolveProtocol(
            new ProtocolServiceFixture([]),
            protocol: new IdentifiedProtocolFixture('example#unmodeled')
        );
    }

    public function testRejectsMissingImplementation(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'No runtime implementation is available for protocol '
            . 'example#missing.'
        );

        (new ProtocolResolver())->resolveProtocol(
            new ProtocolServiceFixture(['example#missing' => true]),
            protocolId: 'example#missing'
        );
    }

    public function testListsApplicableProtocolsInSelectionOrder(): void
    {
        $first = new IdentifiedProtocolFixture('example#first');
        $second = new IdentifiedProtocolFixture('example#second');
        $resolver = new ProtocolResolver(
            protocols: [
                $first->getId() => $first,
                $second->getId() => $second
            ],
            preference: [$second->getId()]
        );

        self::assertSame(
            [
                $second->getId() => $second,
                $first->getId() => $first
            ],
            $resolver->getApplicableProtocols(
                new ProtocolServiceFixture([
                    $first->getId() => true,
                    $second->getId() => true
                ])
            )
        );
    }

    public function testMergeUsesNewRegistrationsAndPreferenceFirst(): void
    {
        $first = new IdentifiedProtocolFixture('example#first');
        $replacement = new IdentifiedProtocolFixture('example#first');
        $second = new IdentifiedProtocolFixture('example#second');
        $resolver = (new ProtocolResolver([
            $first->getId() => $first
        ]))->merge(new ProtocolResolver(
            protocols: [
                $replacement->getId() => $replacement,
                $second->getId() => $second
            ],
            preference: [$second->getId()]
        ));

        self::assertSame(
            $second,
            $resolver->resolveProtocol(
                new ProtocolServiceFixture([
                    $first->getId() => true,
                    $second->getId() => true
                ])
            )
        );
        self::assertSame(
            $replacement,
            $resolver->getProtocols()[$first->getId()]
        );
    }
}

final readonly class ProtocolServiceFixture implements ServiceInterface
{
    /** @param array<string, mixed> $traits */
    public function __construct(private array $traits) {}

    public function getName(): string
    {
        return 'Service';
    }

    public function getId(): string
    {
        return 'example#Service';
    }

    public function getVersion(): string
    {
        return '1';
    }

    public function getSchema(): Schema
    {
        return new Schema(
            id: $this->getId(),
            type: ShapeType::Service,
            traitsById: $this->traits
        );
    }

    public function getOperations(): array
    {
        return [];
    }

    public function getOperation(string $name): ?OperationInterface
    {
        return null;
    }
}
