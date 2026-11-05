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

abstract readonly class RpcV2CborNestedUnion implements UnionInterface
{
    public const SHAPE_ID = 'smithy.protocoltests.rpcv2Cbor#RpcV2CborNestedUnion';

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
        return new RpcV2CborNestedUnionStringValue($value);
    }

    /**
     * Create an unknown variant for forward-compatibility.
     */
    public static function unknown(string $tag, mixed $value): self
    {
        return new RpcV2CborNestedUnionUnknown($tag, $value);
    }

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        return Schemas::rpcV2CborNestedUnion();
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
            'stringValue' => new RpcV2CborNestedUnionStringValue($value),
            default => new RpcV2CborNestedUnionUnknown($tag, $value),
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
            0 => new RpcV2CborNestedUnionStringValue($value),
            default => throw new \InvalidArgumentException('Unknown RpcV2CborNestedUnion variant index: ' . $index),
        };
    }
}
