<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codec\Json\Fixture;

use Smithy\Core\Serde\{
    Schema,
    SerializableStructInterface,
    ShapeSerializerInterface
};
use Smithy\Core\Shape\{
    BigDecimal,
    ShapeType
};

/**
 * Model a struct with a big-number member followed by a nested struct without big numbers.
 */
final class JsonBigNumberStructFixture implements SerializableStructInterface
{
    public const SHAPE_ID = 'smithy.test#JsonBigNumberStruct';

    private static ?Schema $SCHEMA = null;

    /**
     * Create the fixture; a null `$inner` is omitted.
     */
    public function __construct(
        private readonly string $big,
        private readonly ?SampleStruct $inner = null
    ) {}

    /**
     * Return the fixture schema.
     */
    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: self::SHAPE_ID,
            type: ShapeType::Structure,
            targetClass: self::class,
            members: [
                new Schema(
                    id: self::SHAPE_ID . '$big',
                    type: ShapeType::BigDecimal,
                    memberName: 'big',
                    memberIndex: 0
                ),
                new Schema(
                    id: self::SHAPE_ID . '$inner',
                    type: ShapeType::Structure,
                    targetClass: SampleStruct::class,
                    memberName: 'inner',
                    memberIndex: 1
                )
            ]
        );
    }

    /** @inheritDoc */
    public function getSchema(): Schema
    {
        return self::schema();
    }

    /** @inheritDoc */
    public function serialize(ShapeSerializerInterface $serializer, int $idx): void
    {
        $serializer->serializeStruct(self::schema(), $this, $idx);
    }

    /** @inheritDoc */
    public function serializeMembers(ShapeSerializerInterface $serializer): void
    {
        $members = self::schema()->getMembersByIndex();
        $serializer->serializeBigDecimal($members[0], new BigDecimal($this->big), 0);
        if ($this->inner !== null) {
            $this->inner->serialize($serializer, 1);
        }
    }

    /** @inheritDoc */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->big,
            1 => $this->inner,
            default => null
        };
    }

    /** @inheritDoc */
    public function withMemberValue(Schema $member, mixed $value): static
    {
        throw new \LogicException('Not supported by this fixture.');
    }
}
