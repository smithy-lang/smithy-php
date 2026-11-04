<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Serde;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\ShapeFactory;
use Smithy\Core\Shape\Document\AbstractDocument;
use Smithy\Core\Shape\Document\StructDocument;
use Smithy\Core\Shape\Document\UnionDocument;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\{
    RpcV2CborNestedUnion,
    RpcV2CborUnion,
    SimpleStructure,
    TestEnum
};

#[CoversClass(ShapeFactory::class)]
final class ShapeFactoryTest extends TestCase
{
    public function testCreatesGeneratedStructure(): void
    {
        $schema = SimpleStructure::schema();

        $result = ShapeFactory::create(
            $schema,
            SimpleStructure::class,
            StructDocument::fromMembers($schema, ['value'])
        );

        self::assertInstanceOf(SimpleStructure::class, $result);
        self::assertSame('value', $result->value);
    }

    public function testCreatesGeneratedEnum(): void
    {
        $schema = TestEnum::schema();

        $result = ShapeFactory::create(
            $schema,
            TestEnum::class,
            AbstractDocument::fromString('BAR', $schema)
        );

        self::assertInstanceOf(TestEnum::class, $result);
        self::assertSame('BAR', $result->getValue());
    }

    public function testRecursivelyCreatesGeneratedUnionValues(): void
    {
        $nestedSchema = RpcV2CborNestedUnion::schema();
        $nested = UnionDocument::create(
            $nestedSchema,
            'stringValue',
            'nested'
        );
        $schema = RpcV2CborUnion::schema();

        $result = ShapeFactory::create(
            $schema,
            RpcV2CborUnion::class,
            UnionDocument::create($schema, 'unionValue', $nested)
        );

        self::assertInstanceOf(RpcV2CborUnion::class, $result);
        self::assertSame('unionValue', $result->tag());
        self::assertInstanceOf(
            RpcV2CborNestedUnion::class,
            $result->value()
        );
        self::assertSame('stringValue', $result->value()->tag());
        self::assertSame('nested', $result->value()->value());
    }
}
