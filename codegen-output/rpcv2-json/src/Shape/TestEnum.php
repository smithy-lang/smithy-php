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

final class TestEnum implements EnumInterface
{
    public const SHAPE_ID = 'smithy.protocoltests.rpcv2Json#TestEnum';

    private static array $known = [];

    public readonly string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function foo(): self
    {
        return self::$known['FOO'] ??= new self('FOO');
    }

    public static function bar(): self
    {
        return self::$known['BAR'] ??= new self('BAR');
    }

    public static function baz(): self
    {
        return self::$known['BAZ'] ??= new self('BAZ');
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
            'FOO' => self::$known['FOO'] ??= new self('FOO'),
            'BAR' => self::$known['BAR'] ??= new self('BAR'),
            'BAZ' => self::$known['BAZ'] ??= new self('BAZ'),
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
        return [self::foo(), self::bar(), self::baz()];
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
        return Schemas::testEnum();
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
