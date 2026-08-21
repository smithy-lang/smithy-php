<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Document;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\Document\IncompatibleDocumentTypeException;
use Smithy\Core\Shape\Document\TimestampDocument;

#[CoversClass(TimestampDocument::class)]
final class TimestampDocumentTest extends TestCase
{
    public function testTypeIsTimestamp(): void
    {
        $this->assertSame(
            ShapeType::Timestamp,
            (new TimestampDocument(0.0))->type()
        );
    }

    public function testAsTimestampReturnsStoredEpochSeconds(): void
    {
        $this->assertSame(
            1_700_000_000.5,
            (new TimestampDocument(1_700_000_000.5))->asTimestamp()
        );
    }

    public function testAsStringThrows(): void
    {
        $this->expectException(IncompatibleDocumentTypeException::class);
        (new TimestampDocument(0.0))->asString();
    }

    public function testAsIntegerThrows(): void
    {
        $this->expectException(IncompatibleDocumentTypeException::class);
        (new TimestampDocument(0.0))->asInteger();
    }

    public function testSerializeDispatchesToSerializeTimestamp(): void
    {
        $schema = new Schema(
            id: 'com.example#When',
            type: ShapeType::Timestamp
        );
        $serializer = $this->createMock(ShapeSerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serializeTimestamp')
            ->with($schema, 1_700_000_000.0, 0);

        (new TimestampDocument(1_700_000_000.0, $schema))
            ->serialize($serializer, 0);
    }

    public function testSerializeWithoutSchemaFallsBackToPreludeSchema(): void
    {
        $captured = null;
        $serializer = $this->createMock(ShapeSerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serializeTimestamp')
            ->with(
                $this->callback(function (Schema $s) use (&$captured): bool {
                    $captured = $s;

                    return $s->id === 'smithy.api#Timestamp';
                }),
                1.0,
                0
            );

        (new TimestampDocument(1.0))->serialize($serializer, 0);
        $this->assertSame(ShapeType::Timestamp->value, $captured->type);
    }
}
