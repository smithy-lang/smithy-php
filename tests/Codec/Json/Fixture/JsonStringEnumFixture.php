<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codec\Json\Fixture;

use Smithy\Core\Serde\{
    Schema,
    ShapeSerializerInterface
};
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\EnumInterface;

final class JsonStringEnumFixture implements EnumInterface
{
    private static ?Schema $SCHEMA = null;
    /** @var array<string, self> */
    private static array $known = [];

    private function __construct(
        private readonly string $value
    ) {}

    /**
     * Return the `open` value.
     */
    public static function open(): self
    {
        return self::$known['open'] ??= new self('open');
    }

    /**
     * Return the `closed` value.
     */
    public static function closed(): self
    {
        return self::$known['closed'] ??= new self('closed');
    }

    /**
     * Rehydrate this enum.
     */
    public static function fromValue(string $value): self
    {
        return match ($value) {
            'open' => self::open(),
            'closed' => self::closed(),
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
            id: 'smithy.test#JsonStringEnum',
            type: ShapeType::Enum,
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
        $serializer->serializeString(self::schema(), $this->value, $idx);
    }
}
