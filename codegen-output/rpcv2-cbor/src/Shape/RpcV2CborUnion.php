<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RpcV2Cbor\Shape;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\UnionInterface;
use Smithy\Core\Shape\Utils\ArrayShapeSerializer;
use Smithy\ProtocolTests\RpcV2Cbor\Schemas;

abstract readonly class RpcV2CborUnion implements UnionInterface
{
    public const SHAPE_ID = 'smithy.protocoltests.rpcv2Cbor#RpcV2CborUnion';

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

    public static function stringValue(string $value): self
    {
        return new RpcV2CborUnionStringValue($value);
    }

    public static function unionValue(RpcV2CborNestedUnion $value): self
    {
        return new RpcV2CborUnionUnionValue($value);
    }

    /**
     * Create an unknown variant for forward-compatibility.
     */
    public static function unknown(string $tag, mixed $value): self
    {
        return new RpcV2CborUnionUnknown($tag, $value);
    }

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        return Schemas::rpcV2CborUnion();
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
            'stringValue' => new RpcV2CborUnionStringValue($value),
            'unionValue' => new RpcV2CborUnionUnionValue($value instanceof RpcV2CborNestedUnion ? $value : ArrayShapeSerializer::fromArray(RpcV2CborNestedUnion::class, $value)),
            default => new RpcV2CborUnionUnknown($tag, $value),
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
            0 => new RpcV2CborUnionStringValue($value),
            1 => new RpcV2CborUnionUnionValue($value instanceof RpcV2CborNestedUnion ? $value : ArrayShapeSerializer::fromArray(RpcV2CborNestedUnion::class, $value)),
            default => throw new \InvalidArgumentException('Unknown RpcV2CborUnion variant index: ' . $index),
        };
    }
}
