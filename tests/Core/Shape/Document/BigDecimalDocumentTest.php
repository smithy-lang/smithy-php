<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Document;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Serde\ShapeType;
use Smithy\Core\Shape\BigDecimal;
use Smithy\Core\Shape\Document\BigDecimalDocument;
use Smithy\Core\Shape\Document\IncompatibleDocumentTypeException;

#[CoversClass(BigDecimalDocument::class)]
final class BigDecimalDocumentTest extends TestCase
{
    public function testTypeIsBigDecimal(): void
    {
        $this->assertSame(
            ShapeType::BigDecimal,
            (new BigDecimalDocument(new BigDecimal('1.0')))->type()
        );
    }

    public function testAsBigDecimalReturnsStoredValue(): void
    {
        $value = new BigDecimal('3.14159265358979323846');
        $this->assertSame(
            $value,
            (new BigDecimalDocument($value))->asBigDecimal()
        );
    }

    public function testAsFloatThrows(): void
    {
        $this->expectException(IncompatibleDocumentTypeException::class);
        (new BigDecimalDocument(new BigDecimal('1.0')))->asFloat();
    }

    public function testSerializeDispatchesToSerializeBigDecimal(): void
    {
        $schema = new Schema(
            id: 'com.example#Money',
            type: ShapeType::BigDecimal
        );
        $value = new BigDecimal('1.5');
        $serializer = $this->createMock(ShapeSerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serializeBigDecimal')
            ->with($schema, $value, 0);

        (new BigDecimalDocument($value, $schema))->serialize($serializer, 0);
    }

    public function testSerializeWithoutSchemaFallsBackToPreludeSchema(): void
    {
        $captured = null;
        $value = new BigDecimal('1.5');
        $serializer = $this->createMock(ShapeSerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serializeBigDecimal')
            ->with(
                $this->callback(function (Schema $s) use (&$captured): bool {
                    $captured = $s;

                    return $s->id === 'smithy.api#BigDecimal';
                }),
                $value,
                0
            );

        (new BigDecimalDocument($value))->serialize($serializer, 0);
        $this->assertSame(ShapeType::BigDecimal->value, $captured->type);
    }
}
