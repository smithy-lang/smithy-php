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
use Smithy\Core\Shape\Document\MapDocument;

#[CoversClass(MapDocument::class)]
final class MapDocumentTest extends TestCase
{
    public function testTypeIsMap(): void
    {
        $this->assertSame(ShapeType::Map, (new MapDocument([]))->type());
    }

    public function testAsMapReturnsStoredEntries(): void
    {
        $entries = [
            'a' => AbstractDocument::ofString('x'),
            'b' => AbstractDocument::ofInteger(1)
        ];
        $this->assertSame($entries, (new MapDocument($entries))->asMap());
    }

    public function testAsListThrows(): void
    {
        $this->expectException(IncompatibleDocumentTypeException::class);
        (new MapDocument([]))->asList();
    }

    public function testGetMemberReturnsMatchingEntry(): void
    {
        $entry = AbstractDocument::ofString('x');
        $doc = new MapDocument(['a' => $entry]);
        $this->assertSame($entry, $doc->getMember('a'));
    }

    public function testGetMemberReturnsNullForMissingKey(): void
    {
        $this->assertNull((new MapDocument([]))->getMember('missing'));
    }

    public function testSerializeDispatchesToSerializeMap(): void
    {
        $entries = ['a' => AbstractDocument::ofString('x')];
        $schema = new Schema(
            id: 'com.example#Attributes',
            type: ShapeType::Map
        );
        $serializer = $this->createMock(ShapeSerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serializeMap')
            ->with($schema, $entries, 0);

        (new MapDocument($entries, $schema))->serialize($serializer, 0);
    }

    public function testSerializeWithoutSchemaFallsBackToPreludeSchema(): void
    {
        $captured = null;
        $serializer = $this->createMock(ShapeSerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serializeMap')
            ->with(
                $this->callback(function (Schema $s) use (&$captured): bool {
                    $captured = $s;

                    return $s->id === 'smithy.api#Map';
                }),
                [],
                0
            );

        (new MapDocument([]))->serialize($serializer, 0);
        $this->assertSame(ShapeType::Map->value, $captured->type);
    }
}
