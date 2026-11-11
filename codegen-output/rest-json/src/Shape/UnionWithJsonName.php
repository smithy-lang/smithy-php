<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestJson\Shape;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\UnionInterface;
use Smithy\Core\Shape\Utils\ArrayShapeSerializer;
use Smithy\ProtocolTests\RestJson\Schemas;

abstract readonly class UnionWithJsonName implements UnionInterface
{
    public const SHAPE_ID = 'aws.protocoltests.restjson#UnionWithJsonName';

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

    public static function foo(string $value): self
    {
        return new UnionWithJsonNameFoo($value);
    }

    public static function bar(string $value): self
    {
        return new UnionWithJsonNameBar($value);
    }

    public static function baz(string $value): self
    {
        return new UnionWithJsonNameBaz($value);
    }

    /**
     * Create an unknown variant for forward-compatibility.
     */
    public static function unknown(string $tag, mixed $value): self
    {
        return new UnionWithJsonNameUnknown($tag, $value);
    }

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        return Schemas::unionWithJsonName();
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
            'foo' => new UnionWithJsonNameFoo($value),
            'bar' => new UnionWithJsonNameBar($value),
            'baz' => new UnionWithJsonNameBaz($value),
            default => new UnionWithJsonNameUnknown($tag, $value),
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
            0 => new UnionWithJsonNameFoo($value),
            1 => new UnionWithJsonNameBar($value),
            2 => new UnionWithJsonNameBaz($value),
            default => throw new \InvalidArgumentException('Unknown UnionWithJsonName variant index: ' . $index),
        };
    }
}
