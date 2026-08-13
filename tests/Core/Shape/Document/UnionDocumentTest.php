<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Document;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Serde\ShapeType;
use Smithy\Core\Shape\Document\IncompatibleDocumentTypeException;
use Smithy\Core\Shape\Document\UnionDocument;
use Smithy\Core\Shape\UnionInterface;

#[CoversClass(UnionDocument::class)]
final class UnionDocumentTest extends TestCase
{
    public function testTypeIsUnion(): void
    {
        $doc = new UnionDocument(
            $this->createMock(UnionInterface::class),
            $this->schema()
        );
        $this->assertSame(ShapeType::Union, $doc->type());
    }

    public function testAsMapThrows(): void
    {
        $doc = new UnionDocument(
            $this->createMock(UnionInterface::class),
            $this->schema()
        );
        $this->expectException(IncompatibleDocumentTypeException::class);
        $doc->asMap();
    }

    public function testExplicitSchemaIsCarriedForward(): void
    {
        $schema = $this->schema();
        $doc = new UnionDocument(
            $this->createMock(UnionInterface::class),
            $schema
        );
        $this->assertSame($schema, $doc->schema());
    }

    public function testSerializeDispatchesToSerializeUnion(): void
    {
        $schema = $this->schema();
        $union = $this->createMock(UnionInterface::class);
        $doc = new UnionDocument($union, $schema);
        $serializer = $this->createMock(ShapeSerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serializeUnion')
            ->with($schema, $union, 2);

        $doc->serialize($serializer, 2);
    }

    private function schema(): Schema
    {
        return new Schema(id: 'com.example#Union', type: ShapeType::Union);
    }
}
