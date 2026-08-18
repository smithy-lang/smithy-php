<?php

declare(strict_types=1);

namespace Smithy\Tests\Protocol\Utils;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Serde\ShapeType;
use Smithy\Core\Shape\EnumInterface;

final class HttpBindingStringEnumFixture implements EnumInterface
{
    private static ?Schema $SCHEMA = null;

    /** @var array<string, self> */
    private static array $known = [];

    private function __construct(
        private readonly string $value
    ) {}

    /**
     * Get the known open value.
     */
    public static function open(): self
    {
        return self::$known['open'] ??= new self('open');
    }

    /**
     * Get the known closed value.
     */
    public static function closed(): self
    {
        return self::$known['closed'] ??= new self('closed');
    }

    /**
     * Rehydrate this enum from its wire value.
     */
    public static function fromValue(string $value): self
    {
        return match ($value) {
            'open' => self::open(),
            'closed' => self::closed(),
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
            id: 'smithy.test#HttpBindingStringEnum',
            type: ShapeType::Enum,
            targetClass: self::class
        );
    }

    /**
     * Serialize this enum.
     *
     * @return void
     */
    public function serialize(ShapeSerializerInterface $serializer, int $idx): void
    {
        $serializer->serializeString(self::schema(), $this->value, $idx);
    }
}
