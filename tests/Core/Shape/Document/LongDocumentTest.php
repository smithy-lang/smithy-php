<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Document;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\Document\LongDocument;

#[CoversClass(LongDocument::class)]
final class LongDocumentTest extends TestCase
{
    public function testTypeIsLong(): void
    {
        $this->assertSame(ShapeType::Long, (new LongDocument(0))->type());
    }

    public function testCarriesValuesLargerThan32BitInt(): void
    {
        // 2^31 exceeds the signed 32-bit range that Integer promises.
        $doc = new LongDocument(2_147_483_648);
        $this->assertSame(2_147_483_648, $doc->asLong());
    }

    public function testSerializeDispatchesToSerializeInteger(): void
    {
        // LongDocument inherits IntegerDocument's serialize path; the
        // wire distinction between Integer and Long lives in the schema.
        $schema = new Schema(id: 'com.example#Id', type: ShapeType::Long);
        $serializer = $this->createMock(ShapeSerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serializeInteger')
            ->with($schema, 42, 0);

        (new LongDocument(42, $schema))->serialize($serializer, 0);
    }

    public function testSerializeWithoutSchemaFallsBackToLongPreludeSchema(): void
    {
        $captured = null;
        $serializer = $this->createMock(ShapeSerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serializeInteger')
            ->with(
                $this->callback(function (Schema $s) use (&$captured): bool {
                    $captured = $s;

                    return $s->id === 'smithy.api#Long';
                }),
                5,
                0
            );

        (new LongDocument(5))->serialize($serializer, 0);
        $this->assertSame(ShapeType::Long->value, $captured->type);
    }
}
