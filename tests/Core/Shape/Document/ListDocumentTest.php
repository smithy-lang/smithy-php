<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Document;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Serde\ShapeType;
use Smithy\Core\Shape\Document\AbstractDocument;
use Smithy\Core\Shape\Document\IncompatibleDocumentTypeException;
use Smithy\Core\Shape\Document\ListDocument;

#[CoversClass(ListDocument::class)]
final class ListDocumentTest extends TestCase
{
    public function testTypeIsList(): void
    {
        $this->assertSame(ShapeType::List_, (new ListDocument([]))->type());
    }

    public function testAsListReturnsStoredItems(): void
    {
        $items = [
            AbstractDocument::fromString('a'),
            null,
            AbstractDocument::fromInteger(1)
        ];
        $this->assertSame($items, (new ListDocument($items))->asList());
    }

    public function testAsMapThrows(): void
    {
        $this->expectException(IncompatibleDocumentTypeException::class);
        (new ListDocument([]))->asMap();
    }

    public function testSerializeDispatchesToSerializeList(): void
    {
        $items = [AbstractDocument::fromString('a')];
        $schema = new Schema(id: 'com.example#Names', type: ShapeType::List_);
        $serializer = $this->createMock(ShapeSerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serializeList')
            ->with($schema, $items, 0, ShapeType::Document);

        (new ListDocument($items, $schema))->serialize($serializer, 0);
    }

    public function testSerializeWithoutSchemaFallsBackToPreludeSchema(): void
    {
        $captured = null;
        $serializer = $this->createMock(ShapeSerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serializeList')
            ->with(
                $this->callback(function (Schema $s) use (&$captured): bool {
                    $captured = $s;

                    return $s->id === 'smithy.api#List';
                }),
                [],
                0,
                ShapeType::Document
            );

        (new ListDocument([]))->serialize($serializer, 0);
        $this->assertSame(ShapeType::List_->value, $captured->type);
    }
}
