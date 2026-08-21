<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Serde;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\PreludeSchemas;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;

/**
 * The prelude schema table is a static singleton cache that is eagerly initialized on file load.
 * Every member-schema target that resolves to a `smithy.api#*` primitive short-circuits through
 * {@see PreludeSchemas::get()} instead of allocating a fresh Schema, so both the identity and the
 * `(shapeId, ShapeType)` pairing of each singleton are load-bearing for codec correctness. Renaming
 * a case, dropping one, or drifting the ShapeType mapping silently corrupts every serialized
 * primitive.
 *
 * The tests here pin: (1) that the eager init on file load actually populated the singletons,
 * (2) that each `get()` returns the exact same Schema instance every time (identity, not equality,
 * that's what the hot-path relies on), (3) that the shape-id → ShapeType mapping is stable, and
 * (4) the `isPreludeShape()` predicate's positive/negative behaviors.
 */
final class PreludeSchemasTest extends TestCase
{
    public function testFileLoadEagerlyInitializesEverySingleton(): void
    {
        // The file-load `PreludeSchemas::init()` call at the bottom of the
        // source file means these public statics are guaranteed populated
        // by the time any test, or any consumer, runs. If the eager
        // init line is ever removed, the next assertion catches it before
        // a downstream NPE does.
        $this->assertInstanceOf(Schema::class, PreludeSchemas::$STRING);
        $this->assertInstanceOf(Schema::class, PreludeSchemas::$INTEGER);
        $this->assertInstanceOf(Schema::class, PreludeSchemas::$LONG);
        $this->assertInstanceOf(Schema::class, PreludeSchemas::$SHORT);
        $this->assertInstanceOf(Schema::class, PreludeSchemas::$BYTE);
        $this->assertInstanceOf(Schema::class, PreludeSchemas::$FLOAT);
        $this->assertInstanceOf(Schema::class, PreludeSchemas::$DOUBLE);
        $this->assertInstanceOf(Schema::class, PreludeSchemas::$BOOLEAN);
        $this->assertInstanceOf(Schema::class, PreludeSchemas::$BLOB);
        $this->assertInstanceOf(Schema::class, PreludeSchemas::$TIMESTAMP);
        $this->assertInstanceOf(Schema::class, PreludeSchemas::$BIG_INTEGER);
        $this->assertInstanceOf(Schema::class, PreludeSchemas::$BIG_DECIMAL);
        $this->assertInstanceOf(Schema::class, PreludeSchemas::$DOCUMENT);
    }

    public function testInitIsIdempotent(): void
    {
        // Calling init() explicitly after eager init should be a no-op
        // that returns the same instances, the hot path assumes prelude
        // Schema identity is stable for the lifetime of the process.
        $before = PreludeSchemas::$STRING;
        PreludeSchemas::init();
        $this->assertSame($before, PreludeSchemas::$STRING);
    }

    /**
     * @param non-empty-string $shapeId
     */
    #[DataProvider('preludeShapeMappings')]
    public function testGetReturnsExpectedShapeIdAndType(
        string $shapeId,
        ShapeType $type
    ): void
    {
        $schema = PreludeSchemas::get($shapeId);

        $this->assertNotNull(
            $schema,
            "Expected a prelude schema for {$shapeId}"
        );
        $this->assertSame($shapeId, $schema->id);
        // Schema stores the int-encoded discriminant, unwrap for readability.
        $this->assertSame($type->value, $schema->type);
    }

    /**
     * @param non-empty-string $shapeId
     */
    #[DataProvider('preludeShapeMappings')]
    public function testGetReturnsSingletonInstance(string $shapeId, ShapeType $type): void
    {
        // Identity across calls, codec hot paths compare Schemas by
        // reference. Two separately-allocated Schemas with matching
        // contents would still miss any object-identity cache.
        $this->assertSame(
            PreludeSchemas::get($shapeId),
            PreludeSchemas::get($shapeId)
        );
    }

    public function testGetReturnsNullForUnknownPreludeShape(): void
    {
        $this->assertNull(PreludeSchemas::get('smithy.api#NotAThing'));
    }

    public function testGetReturnsNullForNonPreludeNamespace(): void
    {
        // A well-formed but non-prelude shape id must not resolve,
        // otherwise a service model shape that happens to share a name
        // with a prelude type would be silently replaced by the
        // singleton, corrupting its members.
        $this->assertNull(PreludeSchemas::get('com.example#String'));
    }

    /**
     * @param non-empty-string $shapeId
     */
    #[DataProvider('preludeShapeMappings')]
    public function testIsPreludeShapeReturnsTrueForKnownPreludeShapes(
        string $shapeId,
        ShapeType $type
    ): void
    {
        $this->assertTrue(PreludeSchemas::isPreludeShape($shapeId));
    }

    public function testIsPreludeShapeReturnsFalseForUnknownPreludeName(): void
    {
        // The namespace check succeeds but the lookup misses, this is
        // the branch that guards against silently promoting a not-yet-
        // implemented prelude type into "yes it's fine, use null".
        $this->assertFalse(PreludeSchemas::isPreludeShape('smithy.api#Unit'));
    }

    public function testIsPreludeShapeReturnsFalseForForeignNamespace(): void
    {
        $this->assertFalse(
            PreludeSchemas::isPreludeShape('com.example#String')
        );
    }

    /**
     * @return array<string, array{string, ShapeType}>
     */
    public static function preludeShapeMappings(): array
    {
        return [
            'String' => ['smithy.api#String', ShapeType::String_],
            'Integer' => ['smithy.api#Integer', ShapeType::Integer],
            'Long' => ['smithy.api#Long', ShapeType::Long],
            'Short' => ['smithy.api#Short', ShapeType::Short],
            'Byte' => ['smithy.api#Byte', ShapeType::Byte],
            'Float' => ['smithy.api#Float', ShapeType::Float_],
            'Double' => ['smithy.api#Double', ShapeType::Double],
            'Boolean' => ['smithy.api#Boolean', ShapeType::Boolean],
            'Blob' => ['smithy.api#Blob', ShapeType::Blob],
            'Timestamp' => ['smithy.api#Timestamp', ShapeType::Timestamp],
            'BigInteger' => ['smithy.api#BigInteger', ShapeType::BigInteger],
            'BigDecimal' => ['smithy.api#BigDecimal', ShapeType::BigDecimal],
            'Document' => ['smithy.api#Document', ShapeType::Document]
        ];
    }
}
