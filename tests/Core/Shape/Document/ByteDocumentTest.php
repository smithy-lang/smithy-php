<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Document;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\Document\ByteDocument;
use Smithy\Core\Shape\Document\IncompatibleDocumentTypeException;

#[CoversClass(ByteDocument::class)]
final class ByteDocumentTest extends TestCase
{
    public function testTypeIsByte(): void
    {
        $this->assertSame(ShapeType::Byte, (new ByteDocument(0))->type());
    }

    public function testAcceptsMinBoundary(): void
    {
        $this->assertSame(-128, (new ByteDocument(-128))->asByte());
    }

    public function testAcceptsMaxBoundary(): void
    {
        $this->assertSame(127, (new ByteDocument(127))->asByte());
    }

    public function testRejectsBelowMin(): void
    {
        $this->expectException(IncompatibleDocumentTypeException::class);
        new ByteDocument(-129);
    }

    public function testRejectsAboveMax(): void
    {
        $this->expectException(IncompatibleDocumentTypeException::class);
        new ByteDocument(128);
    }

    public function testInheritsIntegerAccessors(): void
    {
        $doc = new ByteDocument(50);
        $this->assertSame(50, $doc->asInteger());
        $this->assertSame(50, $doc->asShort());
        $this->assertSame(50, $doc->asLong());
    }

    public function testSerializeDispatchesToSerializeInteger(): void
    {
        // ByteDocument reuses IntegerDocument's serialize path.
        $schema = new Schema(id: 'com.example#Age', type: ShapeType::Byte);
        $serializer = $this->createMock(ShapeSerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serializeInteger')
            ->with($schema, 7, 0);

        (new ByteDocument(7, $schema))->serialize($serializer, 0);
    }
}
