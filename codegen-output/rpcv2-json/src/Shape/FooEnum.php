<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RpcV2Json\Shape;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\EnumInterface;
use Smithy\ProtocolTests\RpcV2Json\Schemas;

final class FooEnum implements EnumInterface
{
    public const SHAPE_ID = 'smithy.protocoltests.shared#FooEnum';

    /** @var array<array-key, self> */
    private static array $known = [];

    public readonly string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function foo(): self
    {
        return self::$known['Foo'] ??= new self('Foo');
    }

    public static function baz(): self
    {
        return self::$known['Baz'] ??= new self('Baz');
    }

    public static function bar(): self
    {
        return self::$known['Bar'] ??= new self('Bar');
    }

    public static function one(): self
    {
        return self::$known['1'] ??= new self('1');
    }

    public static function zero(): self
    {
        return self::$known['0'] ??= new self('0');
    }

    /**
     * Create an instance from a string value.
     *
     * Known values are cached as singletons.
     * Unknown values create a fresh instance
     * for forward-compatibility with newer service versions.
     *
     * @param string $value
     * @return self
     */
    public static function fromValue(string $value): self
    {
        return match ($value) {
            'Foo' => self::$known['Foo'] ??= new self('Foo'),
            'Baz' => self::$known['Baz'] ??= new self('Baz'),
            'Bar' => self::$known['Bar'] ??= new self('Bar'),
            '1' => self::$known['1'] ??= new self('1'),
            '0' => self::$known['0'] ??= new self('0'),
            default => new self($value),
        };
    }

    /**
     * Returns all known enum values.
     *
     * @return list<self>
     */
    public static function cases(): array
    {
        return [self::foo(), self::baz(), self::bar(), self::one(), self::zero()];
    }

    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        return Schemas::fooEnum();
    }

    /**
     * Get the shape schema for this instance.
     */
    public function getSchema(): Schema
    {
        return self::schema();
    }

    /**
     * Serialize the shape to the desired wire format.
     *
     * @return void
     */
    public function serialize(ShapeSerializerInterface $serializer, int $idx): void
    {
        $serializer->serializeString(self::schema(), $this->value, $idx);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
