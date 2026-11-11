<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestXml\Shape;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\UnionInterface;
use Smithy\Core\Shape\Utils\ArrayShapeSerializer;
use Smithy\ProtocolTests\RestXml\Schemas;

abstract readonly class XmlUnionShape implements UnionInterface
{
    public const SHAPE_ID = 'aws.protocoltests.restxml#XmlUnionShape';

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
        return new XmlUnionShapeStringValue($value);
    }

    public static function booleanValue(bool $value): self
    {
        return new XmlUnionShapeBooleanValue($value);
    }

    public static function byteValue(int $value): self
    {
        return new XmlUnionShapeByteValue($value);
    }

    public static function shortValue(int $value): self
    {
        return new XmlUnionShapeShortValue($value);
    }

    public static function integerValue(int $value): self
    {
        return new XmlUnionShapeIntegerValue($value);
    }

    public static function longValue(int $value): self
    {
        return new XmlUnionShapeLongValue($value);
    }

    public static function floatValue(float $value): self
    {
        return new XmlUnionShapeFloatValue($value);
    }

    public static function doubleValue(float $value): self
    {
        return new XmlUnionShapeDoubleValue($value);
    }

    public static function unionValue(XmlUnionShape $value): self
    {
        return new XmlUnionShapeUnionValue($value);
    }

    public static function structValue(XmlNestedUnionStruct $value): self
    {
        return new XmlUnionShapeStructValue($value);
    }

    /**
     * Create an unknown variant for forward-compatibility.
     */
    public static function unknown(string $tag, mixed $value): self
    {
        return new XmlUnionShapeUnknown($tag, $value);
    }

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        return Schemas::xmlUnionShape();
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
            'stringValue' => new XmlUnionShapeStringValue($value),
            'booleanValue' => new XmlUnionShapeBooleanValue((bool) $value),
            'byteValue' => new XmlUnionShapeByteValue((int) $value),
            'shortValue' => new XmlUnionShapeShortValue((int) $value),
            'integerValue' => new XmlUnionShapeIntegerValue((int) $value),
            'longValue' => new XmlUnionShapeLongValue((int) $value),
            'floatValue' => new XmlUnionShapeFloatValue((float) $value),
            'doubleValue' => new XmlUnionShapeDoubleValue((float) $value),
            'unionValue' => new XmlUnionShapeUnionValue($value instanceof XmlUnionShape ? $value : ArrayShapeSerializer::fromArray(XmlUnionShape::class, $value)),
            'structValue' => new XmlUnionShapeStructValue($value instanceof XmlNestedUnionStruct ? $value : ArrayShapeSerializer::fromArray(XmlNestedUnionStruct::class, $value)),
            default => new XmlUnionShapeUnknown($tag, $value),
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
            0 => new XmlUnionShapeStringValue($value),
            1 => new XmlUnionShapeBooleanValue((bool) $value),
            2 => new XmlUnionShapeByteValue((int) $value),
            3 => new XmlUnionShapeShortValue((int) $value),
            4 => new XmlUnionShapeIntegerValue((int) $value),
            5 => new XmlUnionShapeLongValue((int) $value),
            6 => new XmlUnionShapeFloatValue((float) $value),
            7 => new XmlUnionShapeDoubleValue((float) $value),
            8 => new XmlUnionShapeUnionValue($value instanceof XmlUnionShape ? $value : ArrayShapeSerializer::fromArray(XmlUnionShape::class, $value)),
            9 => new XmlUnionShapeStructValue($value instanceof XmlNestedUnionStruct ? $value : ArrayShapeSerializer::fromArray(XmlNestedUnionStruct::class, $value)),
            default => throw new \InvalidArgumentException('Unknown XmlUnionShape variant index: ' . $index),
        };
    }
}
