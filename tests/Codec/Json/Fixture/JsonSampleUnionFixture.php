<?php

declare(strict_types=1);

namespace Smithy\Tests\Codec\Json\Fixture;

use Smithy\Core\Serde\{
    Schema,
    ShapeSerializerInterface,
    ShapeType
};
use Smithy\Core\Shape\UnionInterface;

/**
 * Provide a union fixture for JSON codec tests.
 */
final class JsonSampleUnionFixture implements UnionInterface
{
    private static ?Schema $SCHEMA = null;

    /**
     * Create a union fixture.
     */
    public function __construct(
        private readonly string $activeTag,
        private readonly mixed $activeValue
    ) {}

    /**
     * Return the fixture schema.
     */
    public static function schema(): Schema
    {
        if (self::$SCHEMA !== null) {
            return self::$SCHEMA;
        }

        self::$SCHEMA = new Schema(
            id: 'smithy.test#JsonSampleUnion',
            type: ShapeType::Union,
            targetClass: self::class,
            members: [
                new Schema(
                    id: 'smithy.test#JsonSampleUnion$value',
                    type: ShapeType::String_,
                    memberName: 'value',
                    memberIndex: 0
                ),
                new Schema(
                    id: 'smithy.test#JsonSampleUnion$count',
                    type: ShapeType::Integer,
                    memberName: 'count',
                    memberIndex: 1,
                    traits: [Schema::T_JSON_NAME => 'Count']
                ),
            ]
        );

        return self::$SCHEMA;
    }

    /** @inheritDoc */
    public function getSchema(): Schema
    {
        return self::schema();
    }

    /** @inheritDoc */
    public function serialize(ShapeSerializerInterface $serializer, int $idx): void
    {
        $serializer->serializeUnion(self::schema(), $this, $idx);
    }

    /**
     * Return the active tag.
     */
    public function tag(): string
    {
        return $this->activeTag;
    }

    /** @inheritDoc */
    public function getMemberIndex(): int
    {
        return match ($this->activeTag) {
            'value' => 0,
            'count' => 1,
            default => -1
        };
    }

    /**
     * Return the active value.
     */
    public function value(): mixed
    {
        return $this->activeValue;
    }

    /**
     * Create a known variant.
     */
    public static function create(string $tag, mixed $value): self
    {
        return new self($tag, $value);
    }

    /**
     * Create a variant by member index.
     */
    public static function createByIndex(int $index, mixed $value): self
    {
        return new self(
            match ($index) {
                0 => 'value',
                1 => 'count',
                default => throw new \InvalidArgumentException(
                    "Unknown union member index: {$index}"
                )
            },
            $value
        );
    }

    /**
     * Create an unknown variant.
     */
    public static function unknown(string $tag, mixed $value): self
    {
        return new self($tag, $value);
    }
}
