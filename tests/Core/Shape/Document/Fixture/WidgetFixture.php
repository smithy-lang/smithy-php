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
 * Provide a structure fixture with string, blob, timestamp and list members.
 */
final class WidgetFixture implements SerializableStructInterface
{
    use \Smithy\Core\Shape\WithMemberValueTrait;

    public const SHAPE_ID = 'smithy.test#Widget';

    private static ?Schema $SCHEMA = null;

    /**
     * @param list<string>|null $tags
     */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $data = null,
        public readonly int|float|null $created = null,
        public readonly ?int $count = null,
        public readonly ?array $tags = null
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
                    id: self::SHAPE_ID . '$name',
                    type: ShapeType::String_,
                    memberName: 'name',
                    memberIndex: 0,
                    traits: [Schema::T_JSON_NAME => 'Name']
                ),
                new Schema(
                    id: self::SHAPE_ID . '$data',
                    type: ShapeType::Blob,
                    memberName: 'data',
                    memberIndex: 1
                ),
                new Schema(
                    id: self::SHAPE_ID . '$created',
                    type: ShapeType::Timestamp,
                    memberName: 'created',
                    memberIndex: 2
                ),
                new Schema(
                    id: self::SHAPE_ID . '$count',
                    type: ShapeType::Integer,
                    memberName: 'count',
                    memberIndex: 3
                ),
                new Schema(
                    id: self::SHAPE_ID . '$tags',
                    type: ShapeType::List_,
                    memberName: 'tags',
                    memberIndex: 4,
                    listMember: new Schema(
                        id: 'smithy.test#Tags$member',
                        type: ShapeType::String_,
                        memberName: 'member',
                        memberIndex: 0
                    )
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
        if ($this->name !== null) {
            $serializer->serializeString($members[0], $this->name, 0);
        }
        if ($this->data !== null) {
            $serializer->serializeBlob($members[1], $this->data, 1);
        }
        if ($this->created !== null) {
            $serializer->serializeTimestamp($members[2], $this->created, 2);
        }
        if ($this->count !== null) {
            $serializer->serializeInteger($members[3], $this->count, 3);
        }
        if ($this->tags !== null) {
            $serializer->serializeList($members[4], $this->tags, 4);
        }
    }

    /**
     * Return the value of one member.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->name,
            1 => $this->data,
            2 => $this->created,
            3 => $this->count,
            4 => $this->tags,
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
