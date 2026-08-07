<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Document;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Serde\ShapeType;
use Smithy\Core\Shape\Document\BooleanDocument;
use Smithy\Core\Shape\Document\IncompatibleDocumentTypeException;

#[CoversClass(BooleanDocument::class)]
final class BooleanDocumentTest extends TestCase
{
    public function testTypeIsBoolean(): void
    {
        $this->assertSame(
            ShapeType::Boolean,
            (new BooleanDocument(true))->type()
        );
    }

    public function testAsBooleanReturnsStoredValue(): void
    {
        $this->assertTrue((new BooleanDocument(true))->asBoolean());
        $this->assertFalse((new BooleanDocument(false))->asBoolean());
    }

    public function testAsStringThrows(): void
    {
        $this->expectException(IncompatibleDocumentTypeException::class);
        (new BooleanDocument(true))->asString();
    }

    public function testAsIntegerThrows(): void
    {
        $this->expectException(IncompatibleDocumentTypeException::class);
        (new BooleanDocument(true))->asInteger();
    }

    public function testSerializeDispatchesToSerializeBoolean(): void
    {
        $schema = new Schema(id: 'com.example#Flag', type: ShapeType::Boolean);
        $doc = new BooleanDocument(true, $schema);
        $serializer = $this->createMock(ShapeSerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serializeBoolean')
            ->with($schema, true, 3);

        $doc->serialize($serializer, 3);
    }

    public function testSerializeWithoutSchemaFallsBackToPreludeSchema(): void
    {
        $doc = new BooleanDocument(false);
        $captured = null;
        $serializer = $this->createMock(ShapeSerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serializeBoolean')
            ->with(
                $this->callback(function (Schema $s) use (&$captured): bool {
                    $captured = $s;

                    return $s->id === 'smithy.api#Boolean';
                }),
                false,
                0
            );

        $doc->serialize($serializer, 0);
        $this->assertSame(ShapeType::Boolean->value, $captured->type);
    }
}
