<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Document;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Serde\ShapeType;
use Smithy\Core\Shape\Document\DoubleDocument;

#[CoversClass(DoubleDocument::class)]
final class DoubleDocumentTest extends TestCase
{
    public function testTypeIsDouble(): void
    {
        $this->assertSame(ShapeType::Double, (new DoubleDocument(1.0))->type());
    }

    public function testAsFloatAndAsDoubleReturnStoredValue(): void
    {
        $doc = new DoubleDocument(2.71828);
        $this->assertSame(2.71828, $doc->asDouble());
        $this->assertSame(2.71828, $doc->asFloat());
    }

    public function testSerializeDispatchesToSerializeFloat(): void
    {
        // DoubleDocument inherits FloatDocument's serialize path; the schema
        // distinguishes single from double precision on the wire.
        $schema = new Schema(id: 'com.example#Ratio', type: ShapeType::Double);
        $serializer = $this->createMock(ShapeSerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serializeFloat')
            ->with($schema, 2.5, 0);

        (new DoubleDocument(2.5, $schema))->serialize($serializer, 0);
    }

    public function testSerializeWithoutSchemaFallsBackToDoublePreludeSchema(): void
    {
        $captured = null;
        $serializer = $this->createMock(ShapeSerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serializeFloat')
            ->with(
                $this->callback(function (Schema $s) use (&$captured): bool {
                    $captured = $s;

                    return $s->id === 'smithy.api#Double';
                }),
                2.5,
                0
            );

        (new DoubleDocument(2.5))->serialize($serializer, 0);
        $this->assertSame(ShapeType::Double->value, $captured->type);
    }
}
