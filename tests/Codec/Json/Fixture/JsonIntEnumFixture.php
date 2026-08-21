<?php

declare(strict_types=1);

namespace Smithy\Tests\Codec\Json\Fixture;

use Smithy\Core\Serde\{
    Schema,
    ShapeSerializerInterface
};
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\EnumInterface;

final class JsonIntEnumFixture implements EnumInterface
{
    private static ?Schema $SCHEMA = null;
    /** @var array<int, self> */
    private static array $known = [];

    private function __construct(
        private readonly int $value
    ) {}

    /**
     * Return the `7` value.
     */
    public static function seven(): self
    {
        return self::$known[7] ??= new self(7);
    }

    /**
     * Return the `9` value.
     */
    public static function nine(): self
    {
        return self::$known[9] ??= new self(9);
    }

    /**
     * Rehydrate this enum.
     */
    public static function fromValue(int $value): self
    {
        return match ($value) {
            7 => self::seven(),
            9 => self::nine(),
            default => new self($value),
        };
    }

    /**
     * Return the backing value.
     */
    public function getValue(): string|int
    {
        return $this->value;
    }

    /**
     * Return the fixture schema.
     */
    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: 'smithy.test#JsonIntEnum',
            type: ShapeType::IntEnum,
            targetClass: self::class
        );
    }

    /**
     * Serialize this enum.
     *
     * @return void
     */
    public function getSchema(): Schema
    {
        return self::schema();
    }

    public function serialize(ShapeSerializerInterface $serializer, int $idx): void
    {
        $serializer->serializeInteger(self::schema(), $this->value, $idx);
    }
}
