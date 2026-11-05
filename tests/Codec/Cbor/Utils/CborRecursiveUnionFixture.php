<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codec\Cbor\Utils;

use Smithy\Core\Serde\{
    Schema,
    ShapeSerializerInterface
};
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\UnionInterface;

/**
 * Model a generated recursive union with map, list, and blob variants.
 */
final class CborRecursiveUnionFixture implements UnionInterface
{
    public const SHAPE_ID = 'smithy.test#CborRecursiveUnion';

    private static ?Schema $SCHEMA = null;

    /**
     * Create a recursive union fixture.
     */
    public function __construct(
        private readonly string $activeTag,
        private readonly mixed $activeValue
    ) {}

    /**
     * Return the fixture schema.
     */
    public static function schema(): Schema
    {
        if (self::$SCHEMA !== null) {
            return self::$SCHEMA;
        }

        self::$SCHEMA = new Schema(
            id: self::SHAPE_ID,
            type: ShapeType::Union,
            targetClass: self::class,
            members: [
                new Schema(
                    id: 'smithy.test#CborRecursiveUnionMap',
                    type: ShapeType::Map,
                    memberName: 'M',
                    memberIndex: 0,
                    mapKey: new Schema(
                        id: 'smithy.api#String',
                        type: ShapeType::String_
                    ),
                    mapValue: new Schema(
                        id: self::SHAPE_ID,
                        type: ShapeType::Union,
                        targetClass: self::class
                    )
                ),
                new Schema(
                    id: 'smithy.test#CborRecursiveUnionList',
                    type: ShapeType::List_,
                    memberName: 'L',
                    memberIndex: 1,
                    listMember: new Schema(
                        id: self::SHAPE_ID,
                        type: ShapeType::Union,
                        targetClass: self::class
                    )
                ),
                new Schema(
                    id: 'smithy.api#Blob',
                    type: ShapeType::Blob,
                    memberName: 'B',
                    memberIndex: 2
                )
            ]
        );

        return self::$SCHEMA;
    }

    /** @inheritDoc */
    public function getSchema(): Schema
    {
        return self::schema();
    }

    /** @inheritDoc */
    public function serialize(ShapeSerializerInterface $serializer, int $idx): void
    {
        $serializer->serializeUnion(self::schema(), $this, $idx);
    }

    /** @inheritDoc */
    public function getMemberIndex(): int
    {
        return match ($this->activeTag) {
            'M' => 0,
            'L' => 1,
            'B' => 2,
            default => -1
        };
    }

    /** @inheritDoc */
    public function tag(): string
    {
        return $this->activeTag;
    }

    /** @inheritDoc */
    public function value(): mixed
    {
        return $this->activeValue;
    }
}
