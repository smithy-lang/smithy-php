<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Document;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Serde\ShapeType;
use Smithy\Core\Shape\Document\IncompatibleDocumentTypeException;
use Smithy\Core\Shape\Document\StructDocument;

#[CoversClass(StructDocument::class)]
final class StructDocumentTest extends TestCase
{
    public function testTypeIsStructure(): void
    {
        $doc = new StructDocument(
            $this->createMock(SerializableStructInterface::class),
            $this->schema()
        );
        $this->assertSame(ShapeType::Structure, $doc->type());
    }

    public function testAsListThrows(): void
    {
        $doc = new StructDocument(
            $this->createMock(SerializableStructInterface::class),
            $this->schema()
        );
        $this->expectException(IncompatibleDocumentTypeException::class);
        $doc->asList();
    }

    public function testExplicitSchemaIsCarriedForward(): void
    {
        $schema = $this->schema();
        $doc = new StructDocument(
            $this->createMock(SerializableStructInterface::class),
            $schema
        );
        $this->assertSame($schema, $doc->schema());
    }

    public function testSerializeDispatchesToSerializeStruct(): void
    {
        $schema = $this->schema();
        $struct = $this->createMock(SerializableStructInterface::class);
        $doc = new StructDocument($struct, $schema);
        $serializer = $this->createMock(ShapeSerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serializeStruct')
            ->with($schema, $struct, 4);

        $doc->serialize($serializer, 4);
    }

    private function schema(): Schema
    {
        return new Schema(id: 'com.example#Struct', type: ShapeType::Structure);
    }
}
