<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Document\Fixture;

use Smithy\Core\Serde\{
    Schema,
    SerializableStructInterface,
    ShapeSerializerInterface
};
use Smithy\Core\Shape\ShapeType;

/**
 * Provide a structure fixture whose timestamp members carry `timestampFormat` traits.
 */
final class StampedFixture implements SerializableStructInterface
{
    use \Smithy\Core\Shape\WithMemberValueTrait;

    public const SHAPE_ID = 'smithy.test#Stamped';

    private static ?Schema $SCHEMA = null;

    public function __construct(
        public readonly int|float|null $http = null,
        public readonly int|float|null $epoch = null,
        public readonly int|float|null $plain = null,
        public readonly ?WidgetFixture $child = null
    ) {}

    /**
     * Return the fixture schema.
     */
    public static function schema(): Schema
    {
        if (self::$SCHEMA !== null) {
            return self::$SCHEMA;
        }

        return self::$SCHEMA = new Schema(
            id: self::SHAPE_ID,
            type: ShapeType::Structure,
            targetClass: self::class,
            members: [
                new Schema(
                    id: self::SHAPE_ID . '$http',
                    type: ShapeType::Timestamp,
                    memberName: 'http',
                    memberIndex: 0,
                    traits: [Schema::T_TIMESTAMP_FORMAT => 'http-date']
                ),
                new Schema(
                    id: self::SHAPE_ID . '$epoch',
                    type: ShapeType::Timestamp,
                    memberName: 'epoch',
                    memberIndex: 1,
                    traits: [Schema::T_TIMESTAMP_FORMAT => 'epoch-seconds']
                ),
                new Schema(
                    id: self::SHAPE_ID . '$plain',
                    type: ShapeType::Timestamp,
                    memberName: 'plain',
                    memberIndex: 2
                ),
                new Schema(
                    id: self::SHAPE_ID . '$child',
                    type: ShapeType::Structure,
                    memberName: 'child',
                    memberIndex: 3,
                    target: WidgetFixture::schema(),
                    targetClass: WidgetFixture::class
                )
            ]
        );
    }

    /**
     * Return the fixture schema for this instance.
     */
    public function getSchema(): Schema
    {
        return self::schema();
    }

    /**
     * Serialize the fixture as a structure member.
     */
    public function serialize(ShapeSerializerInterface $serializer, int $idx): void
    {
        $serializer->serializeStruct(self::schema(), $this, $idx);
    }

    /**
     * Serialize the fixture's members.
     */
    public function serializeMembers(ShapeSerializerInterface $serializer): void
    {
        $members = self::schema()->getMembersByIndex();
        if ($this->http !== null) {
            $serializer->serializeTimestamp($members[0], $this->http, 0);
        }
        if ($this->epoch !== null) {
            $serializer->serializeTimestamp($members[1], $this->epoch, 1);
        }
        if ($this->plain !== null) {
            $serializer->serializeTimestamp($members[2], $this->plain, 2);
        }
        if ($this->child !== null) {
            $serializer->serializeStruct($members[3], $this->child, 3);
        }
    }

    /**
     * Return the value of one member.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->http,
            1 => $this->epoch,
            2 => $this->plain,
            3 => $this->child,
            default => null
        };
    }

    /**
     * Rehydrate the fixture from positional members.
     *
     * @param array<int, mixed> $members
     */
    public static function fromMembers(array $members): static
    {
        return new self(...$members);
    }
}
