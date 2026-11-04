<?php

declare(strict_types=1);

namespace Smithy\Tests\Codegen;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\TypeRegistryInterface;
use Smithy\Core\Shape\Document\StructDocument;
use Smithy\ProtocolTests\RpcV2Cbor\RpcV2CborTypeRegistry;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\SimpleStructure;

#[CoversNothing]
final class GeneratedTypeRegistryTest extends TestCase
{
    public function testCreatesGeneratedTypeByDiscriminator(): void
    {
        $registry = new RpcV2CborTypeRegistry();
        $schema = SimpleStructure::schema();

        self::assertInstanceOf(TypeRegistryInterface::class, $registry);
        self::assertSame(
            SimpleStructure::class,
            $registry->classFor(SimpleStructure::SHAPE_ID)
        );

        $result = $registry->deserialize(
            StructDocument::fromMembers($schema, ['value'])
        );

        self::assertInstanceOf(SimpleStructure::class, $result);
        self::assertSame('value', $result->value);
    }
}
