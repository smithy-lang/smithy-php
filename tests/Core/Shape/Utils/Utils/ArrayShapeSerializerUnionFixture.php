<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Utils\Utils;

use Smithy\Core\Serde\{
    Schema,
    ShapeSerializerInterface
};
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\UnionInterface;
use Smithy\Core\Shape\Utils\ArrayShapeSerializer;

abstract class ArrayShapeSerializerUnionFixture implements UnionInterface
{
    private static Schema $SCHEMA;

    /**
     * Return the fixture schema.
     */
    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: 'test#ArrayShapeSerializerUnionFixture',
            type: ShapeType::Union,
            members: [
                new Schema(
                    id: 'smithy.api#String',
                    type: ShapeType::String_,
                    memberName: 'text',
                    memberIndex: 0
                ),
                new Schema(
                    id: 'test#ArrayShapeSerializerScalarFixture',
                    type: ShapeType::Structure,
                    memberName: 'scalar',
                    memberIndex: 1,
                    targetClass: ArrayShapeSerializerScalarFixture::class
                )
            ],
            targetClass: self::class
        );
    }

    /**
     * Create a text variant.
     */
    public static function text(string $value): self
    {
        return new ArrayShapeSerializerUnionTextFixture($value);
    }

    /**
     * Create a structure variant.
     */
    public static function scalar(
        ArrayShapeSerializerScalarFixture $value
    ): self
    {
        return new ArrayShapeSerializerUnionScalarFixture($value);
    }

    /**
     * Create an unknown variant.
     */
    public static function unknown(string $tag, mixed $value): self
    {
        return new ArrayShapeSerializerUnionUnknownFixture($tag, $value);
    }

    /**
     * Create a variant by tag.
     */
    public static function create(string $tag, mixed $value): self
    {
        return match ($tag) {
            'text' => self::text((string) $value),
            'scalar' => self::scalar(
                $value instanceof ArrayShapeSerializerScalarFixture
                    ? $value
                    : ArrayShapeSerializer::fromArray(
                        ArrayShapeSerializerScalarFixture::class,
                        $value
                    )
            ),
            default => self::unknown($tag, $value)
        };
    }

    /** @inheritDoc */
    public function getSchema(): Schema
    {
        return self::schema();
    }

    /** @inheritDoc */
    public function getMemberIndex(): int
    {
        return match ($this->tag()) {
            'text' => 0,
            'scalar' => 1,
            default => -1
        };
    }

    /** @inheritDoc */
    public function serialize(
        ShapeSerializerInterface $serializer,
        int $idx
    ): void {}
}
