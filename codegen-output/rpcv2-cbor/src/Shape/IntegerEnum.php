<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RpcV2Cbor\Shape;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\EnumInterface;
use Smithy\ProtocolTests\RpcV2Cbor\Schemas;

final class IntegerEnum implements EnumInterface
{
    public const SHAPE_ID = 'smithy.protocoltests.shared#IntegerEnum';

    private static array $known = [];

    public readonly int $value;

    private function __construct(int $value)
    {
        $this->value = $value;
    }

    public static function a(): self
    {
        return self::$known[1] ??= new self(1);
    }

    public static function b(): self
    {
        return self::$known[2] ??= new self(2);
    }

    public static function c(): self
    {
        return self::$known[3] ??= new self(3);
    }

    /**
     * Create an instance from a int value.
     *
     * Known values are cached as singletons.
     * Unknown values create a fresh instance
     * for forward-compatibility with newer service versions.
     *
     * @param int $value
     * @return self
     */
    public static function fromValue(int $value): self
    {
        return match ($value) {
            1 => self::$known[1] ??= new self(1),
            2 => self::$known[2] ??= new self(2),
            3 => self::$known[3] ??= new self(3),
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
        return [self::a(), self::b(), self::c()];
    }

    public function getValue(): string|int
    {
        return $this->value;
    }

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        return Schemas::integerEnum();
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
        $serializer->serializeInteger(self::schema(), $this->value, $idx);
    }

}
