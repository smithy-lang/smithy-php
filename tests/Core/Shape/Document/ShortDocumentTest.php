<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Document;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Serde\ShapeType;
use Smithy\Core\Shape\Document\IncompatibleDocumentTypeException;
use Smithy\Core\Shape\Document\ShortDocument;

#[CoversClass(ShortDocument::class)]
final class ShortDocumentTest extends TestCase
{
    public function testTypeIsShort(): void
    {
        $this->assertSame(ShapeType::Short, (new ShortDocument(0))->type());
    }

    public function testAcceptsMinBoundary(): void
    {
        $this->assertSame(-32768, (new ShortDocument(-32768))->asShort());
    }

    public function testAcceptsMaxBoundary(): void
    {
        $this->assertSame(32767, (new ShortDocument(32767))->asShort());
    }

    public function testRejectsBelowMin(): void
    {
        $this->expectException(IncompatibleDocumentTypeException::class);
        new ShortDocument(-32769);
    }

    public function testRejectsAboveMax(): void
    {
        $this->expectException(IncompatibleDocumentTypeException::class);
        new ShortDocument(32768);
    }

    public function testSerializeDispatchesToSerializeInteger(): void
    {
        $schema = new Schema(id: 'com.example#Level', type: ShapeType::Short);
        $serializer = $this->createMock(ShapeSerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serializeInteger')
            ->with($schema, 100, 0);

        (new ShortDocument(100, $schema))->serialize($serializer, 0);
    }
}
