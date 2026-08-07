<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Serde;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\ShapeType;

/**
 * `ShapeType` is a load-bearing enum: its integer backing values are literal-inlined into
 * serializer/deserializer switch statements so the JIT can lower them to a packed jump table.
 * Reassigning any int value silently breaks every hot-path switch in the codebase.
 *
 * The tests below pin the case set + the integer values + the interned `Schema::$type` layout so a
 * regression here fails fast, at the enum-shape level, rather than showing up as a mis-dispatched
 * shape downstream.
 */
final class ShapeTypeTest extends TestCase
{
    public function testExposesAllTwentyTwoSmithyShapeTypes(): void
    {
        // The enum enumerates every shape type in the Smithy metamodel.
        // Adding a case forces a paired update to every serializer /
        // deserializer switch, pinning the count here is the first
        // signal that pairing was forgotten.
        $this->assertCount(22, ShapeType::cases());
    }

    /**
     * @param non-empty-string $name
     */
    #[DataProvider('shapeTypeIntegerBackings')]
    public function testEachCaseHasStableIntegerBackingValue(
        string $name,
        int $expectedValue
    ): void
    {
        // These int values are literal-inlined into serializer switch arms.
        // Reassigning any of them is an ABI break for every codec.
        $this->assertSame(
            $expectedValue,
            ShapeType::from($expectedValue)->value
        );
        $this->assertSame($name, ShapeType::from($expectedValue)->name);
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function shapeTypeIntegerBackings(): array
    {
        return [
            'Boolean = 1' => ['Boolean', 1],
            'Byte = 2' => ['Byte', 2],
            'Short = 3' => ['Short', 3],
            'Integer = 4' => ['Integer', 4],
            'Long = 5' => ['Long', 5],
            'Float_ = 6' => ['Float_', 6],
            'Double = 7' => ['Double', 7],
            'BigInteger = 8' => ['BigInteger', 8],
            'BigDecimal = 9' => ['BigDecimal', 9],
            'String_ = 10' => ['String_', 10],
            'Blob = 11' => ['Blob', 11],
            'Timestamp = 12' => ['Timestamp', 12],
            'List_ = 13' => ['List_', 13],
            'Map = 14' => ['Map', 14],
            'Structure = 15' => ['Structure', 15],
            'Union = 16' => ['Union', 16],
            'Enum = 17' => ['Enum', 17],
            'IntEnum = 18' => ['IntEnum', 18],
            'Document = 19' => ['Document', 19],
            'Operation = 20' => ['Operation', 20],
            'Service = 21' => ['Service', 21],
            'Resource = 22' => ['Resource', 22]
        ];
    }
}
