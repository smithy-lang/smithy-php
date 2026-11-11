<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\AwsJson1_0\Shape;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\UnionInterface;
use Smithy\Core\Shape\Utils\ArrayShapeSerializer;
use Smithy\ProtocolTests\AwsJson1_0\Schemas;

/**
 * A union with a representative set of types for members.
 */
abstract readonly class MyUnion implements UnionInterface
{
    public const SHAPE_ID = 'aws.protocoltests.json10#MyUnion';

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
        return new MyUnionStringValue($value);
    }

    public static function booleanValue(bool $value): self
    {
        return new MyUnionBooleanValue($value);
    }

    public static function numberValue(int $value): self
    {
        return new MyUnionNumberValue($value);
    }

    public static function blobValue(string $value): self
    {
        return new MyUnionBlobValue($value);
    }

    public static function timestampValue(float $value): self
    {
        return new MyUnionTimestampValue($value);
    }

    public static function enumValue(FooEnum $value): self
    {
        return new MyUnionEnumValue($value);
    }

    public static function intEnumValue(IntegerEnum $value): self
    {
        return new MyUnionIntEnumValue($value);
    }

    public static function listValue(array $value): self
    {
        return new MyUnionListValue($value);
    }

    public static function mapValue(array $value): self
    {
        return new MyUnionMapValue($value);
    }

    public static function structureValue(GreetingStruct $value): self
    {
        return new MyUnionStructureValue($value);
    }

    /**
     * Create an unknown variant for forward-compatibility.
     */
    public static function unknown(string $tag, mixed $value): self
    {
        return new MyUnionUnknown($tag, $value);
    }

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        return Schemas::myUnion();
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
            'stringValue' => new MyUnionStringValue($value),
            'booleanValue' => new MyUnionBooleanValue((bool) $value),
            'numberValue' => new MyUnionNumberValue((int) $value),
            'blobValue' => new MyUnionBlobValue($value),
            'timestampValue' => new MyUnionTimestampValue($value),
            'enumValue' => new MyUnionEnumValue($value instanceof FooEnum ? $value : FooEnum::fromValue($value)),
            'intEnumValue' => new MyUnionIntEnumValue($value instanceof IntegerEnum ? $value : IntegerEnum::fromValue($value)),
            'listValue' => new MyUnionListValue($value),
            'mapValue' => new MyUnionMapValue($value),
            'structureValue' => new MyUnionStructureValue($value instanceof GreetingStruct ? $value : ArrayShapeSerializer::fromArray(GreetingStruct::class, $value)),
            default => new MyUnionUnknown($tag, $value),
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
            0 => new MyUnionStringValue($value),
            1 => new MyUnionBooleanValue((bool) $value),
            2 => new MyUnionNumberValue((int) $value),
            3 => new MyUnionBlobValue($value),
            4 => new MyUnionTimestampValue($value),
            5 => new MyUnionEnumValue($value instanceof FooEnum ? $value : FooEnum::fromValue($value)),
            6 => new MyUnionIntEnumValue($value instanceof IntegerEnum ? $value : IntegerEnum::fromValue($value)),
            7 => new MyUnionListValue($value),
            8 => new MyUnionMapValue($value),
            9 => new MyUnionStructureValue($value instanceof GreetingStruct ? $value : ArrayShapeSerializer::fromArray(GreetingStruct::class, $value)),
            default => throw new \InvalidArgumentException('Unknown MyUnion variant index: ' . $index),
        };
    }
}
