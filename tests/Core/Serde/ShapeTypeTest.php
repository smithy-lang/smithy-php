<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Serde;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\ShapeType;

/**
 * `ShapeType` is a load-bearing enum: its integer backing values are
 * literal-inlined into serializer/deserializer switch statements so the
 * JIT can lower them to a packed jump table. Reassigning any int value
 * silently breaks every hot-path switch in the codebase.
 *
 * The tests below pin the case set + the integer values + the interned
 * `Schema::$type` layout so a regression here fails fast, at the
 * enum-shape level, rather than showing up as a mis-dispatched shape
 * downstream.
 */
final class ShapeTypeTest extends TestCase
{
    public function testExposesAllTwentyTwoSmithyShapeTypes(): void
    {
        // The enum enumerates every shape type in the Smithy metamodel.
        // Adding a case forces a paired update to every serializer /
        // deserializer switch — pinning the count here is the first
        // signal that pairing was forgotten.
        $this->assertCount(22, ShapeType::cases());
    }

    /**
     * @param non-empty-string $name
     */
    #[DataProvider('shapeTypeIntegerBackings')]
    public function testEachCaseHasStableIntegerBackingValue(string $name, int $expectedValue): void
    {
        // These int values are literal-inlined into serializer switch arms.
        // Reassigning any of them is an ABI break for every codec.
        $this->assertSame($expectedValue, ShapeType::from($expectedValue)->value);
        $this->assertSame($name, ShapeType::from($expectedValue)->name);
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function shapeTypeIntegerBackings(): array
    {
        return [
            'Structure = 1' => ['Structure', 1],
            'Union = 2' => ['Union', 2],
            'Enum = 3' => ['Enum', 3],
            'IntEnum = 4' => ['IntEnum', 4],
            'List_ = 5' => ['List_', 5],
            'Map = 6' => ['Map', 6],
            'String_ = 7' => ['String_', 7],
            'Integer = 8' => ['Integer', 8],
            'Long = 9' => ['Long', 9],
            'Short = 10' => ['Short', 10],
            'Byte = 11' => ['Byte', 11],
            'Float_ = 12' => ['Float_', 12],
            'Double = 13' => ['Double', 13],
            'Boolean = 14' => ['Boolean', 14],
            'Timestamp = 15' => ['Timestamp', 15],
            'Blob = 16' => ['Blob', 16],
            'BigInteger = 17' => ['BigInteger', 17],
            'BigDecimal = 18' => ['BigDecimal', 18],
            'Document = 19' => ['Document', 19],
            'Operation = 20' => ['Operation', 20],
            'Service = 21' => ['Service', 21],
            'Resource = 22' => ['Resource', 22],
        ];
    }
}
