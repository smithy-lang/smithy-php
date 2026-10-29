<?php

declare(strict_types=1);

namespace Smithy\CodegenOutput\TrimmedPetStore\Shape;

use Smithy\CodegenOutput\TrimmedPetStore\Schemas;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\UnionInterface;
use Smithy\Core\Shape\Utils\ArrayShapeSerializer;

abstract readonly class UnitUnion implements UnionInterface
{
    public const SHAPE_ID = 'smithy.example#UnitUnion';

    abstract public function getMemberIndex(): int;

    abstract public function tag(): string;

    abstract public function value(): mixed;

    /**
     * Serialize the shape to the desired wire format.
     *
     * @return void
     */
    public function serialize(ShapeSerializerInterface $serializer, int $idx): void
    {
        $serializer->serializeUnion(self::schema(), $this, $idx);
    }

    public static function none(Unit $value): self
    {
        return new UnitUnionNone($value);
    }

    /**
     * Create an unknown variant for forward-compatibility.
     */
    public static function unknown(string $tag, mixed $value): self
    {
        return new UnitUnionUnknown($tag, $value);
    }

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        return Schemas::unitUnion();
    }

    /**
     * Get the shape schema for this instance.
     */
    public function getSchema(): Schema
    {
        return self::schema();
    }


    /**
     * Create a union variant from a tag and value.
     *
     * @param string $tag The variant name
     * @param mixed $value The variant value
     * @return self
     */
    public static function create(string $tag, mixed $value): self
    {
        return match ($tag) {
            'none' => new UnitUnionNone($value instanceof Unit ? $value : ArrayShapeSerializer::fromArray(Unit::class, $value)),
            default => new UnitUnionUnknown($tag, $value),
        };
    }

    /**
     * Create a known union variant from its schema member index.
     *
     * @param int $index The variant member index
     * @param mixed $value The variant value
     * @return self
     */
    public static function createByIndex(int $index, mixed $value): self
    {
        return match ($index) {
            0 => new UnitUnionNone($value instanceof Unit ? $value : ArrayShapeSerializer::fromArray(Unit::class, $value)),
            default => throw new \InvalidArgumentException('Unknown UnitUnion variant index: ' . $index),
        };
    }
}
