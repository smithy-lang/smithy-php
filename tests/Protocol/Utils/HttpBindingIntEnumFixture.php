<?php

declare(strict_types=1);

namespace Smithy\Tests\Protocol\Utils;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Serde\ShapeType;
use Smithy\Core\Shape\EnumInterface;

final class HttpBindingIntEnumFixture implements EnumInterface
{
    private static ?Schema $SCHEMA = null;
    /** @var array<int, self> */
    private static array $known = [];

    private function __construct(
        private readonly int $value
    ) {}

    /**
     * Get the known seven value.
     */
    public static function seven(): self
    {
        return self::$known[7] ??= new self(7);
    }

    /**
     * Get the known nine value.
     */
    public static function nine(): self
    {
        return self::$known[9] ??= new self(9);
    }

    /**
     * Rehydrate this enum from its wire value.
     */
    public static function fromValue(int $value): self
    {
        return match ($value) {
            7 => self::seven(),
            9 => self::nine(),
            default => new self($value)
        };
    }

    /**
     * Get the wire value.
     */
    public function getValue(): string|int
    {
        return $this->value;
    }

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: 'smithy.test#HttpBindingIntEnum',
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
