<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Document;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\Document\FloatDocument;
use Smithy\Core\Shape\Document\IncompatibleDocumentTypeException;

#[CoversClass(FloatDocument::class)]
final class FloatDocumentTest extends TestCase
{
    public function testTypeIsFloat(): void
    {
        $this->assertSame(ShapeType::Float_, (new FloatDocument(1.0))->type());
    }

    public function testAsFloatReturnsStoredValue(): void
    {
        $this->assertSame(3.14, (new FloatDocument(3.14))->asFloat());
    }

    public function testAsDoubleWidensToDouble(): void
    {
        $this->assertSame(3.14, (new FloatDocument(3.14))->asDouble());
    }

    public function testAsIntegerThrows(): void
    {
        $this->expectException(IncompatibleDocumentTypeException::class);
        (new FloatDocument(1.0))->asInteger();
    }

    public function testAsStringThrows(): void
    {
        $this->expectException(IncompatibleDocumentTypeException::class);
        (new FloatDocument(1.0))->asString();
    }

    public function testSerializeDispatchesToSerializeFloat(): void
    {
        $schema = new Schema(id: 'com.example#Rate', type: ShapeType::Float_);
        $serializer = $this->createMock(ShapeSerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serializeFloat')
            ->with($schema, 3.14, 0);

        (new FloatDocument(3.14, $schema))->serialize($serializer, 0);
    }

    public function testSerializeWithoutSchemaFallsBackToPreludeSchema(): void
    {
        $captured = null;
        $serializer = $this->createMock(ShapeSerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serializeFloat')
            ->with(
                $this->callback(function (Schema $s) use (&$captured): bool {
                    $captured = $s;

                    return $s->id === 'smithy.api#Float';
                }),
                1.5,
                0
            );

        (new FloatDocument(1.5))->serialize($serializer, 0);
        $this->assertSame(ShapeType::Float_->value, $captured->type);
    }
}
