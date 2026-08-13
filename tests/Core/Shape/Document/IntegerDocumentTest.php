<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Document;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Serde\ShapeType;
use Smithy\Core\Shape\Document\IncompatibleDocumentTypeException;
use Smithy\Core\Shape\Document\IntegerDocument;

#[CoversClass(IntegerDocument::class)]
final class IntegerDocumentTest extends TestCase
{
    public function testTypeIsInteger(): void
    {
        $this->assertSame(ShapeType::Integer, (new IntegerDocument(1))->type());
    }

    public function testAsIntegerReturnsStoredValue(): void
    {
        $this->assertSame(42, (new IntegerDocument(42))->asInteger());
    }

    public function testAsLongWidensToLong(): void
    {
        $this->assertSame(42, (new IntegerDocument(42))->asLong());
    }

    public function testAsShortAllowsInRangeValue(): void
    {
        $this->assertSame(100, (new IntegerDocument(100))->asShort());
    }

    public function testAsShortRejectsOutOfRangeValue(): void
    {
        $this->expectException(IncompatibleDocumentTypeException::class);
        (new IntegerDocument(40_000))->asShort();
    }

    public function testAsByteAllowsInRangeValue(): void
    {
        $this->assertSame(100, (new IntegerDocument(100))->asByte());
    }

    public function testAsByteRejectsOutOfRangeValue(): void
    {
        $this->expectException(IncompatibleDocumentTypeException::class);
        (new IntegerDocument(1000))->asByte();
    }

    public function testAsStringThrows(): void
    {
        $this->expectException(IncompatibleDocumentTypeException::class);
        (new IntegerDocument(1))->asString();
    }

    public function testSerializeDispatchesToSerializeInteger(): void
    {
        $schema = new Schema(id: 'com.example#Count', type: ShapeType::Integer);
        $serializer = $this->createMock(ShapeSerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serializeInteger')
            ->with($schema, 7, 1);

        (new IntegerDocument(7, $schema))->serialize($serializer, 1);
    }

    public function testSerializeWithoutSchemaFallsBackToPreludeSchema(): void
    {
        $captured = null;
        $serializer = $this->createMock(ShapeSerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serializeInteger')
            ->with(
                $this->callback(function (Schema $s) use (&$captured): bool {
                    $captured = $s;

                    return $s->id === 'smithy.api#Integer';
                }),
                5,
                0
            );

        (new IntegerDocument(5))->serialize($serializer, 0);
        $this->assertSame(ShapeType::Integer->value, $captured->type);
    }
}
