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
 * Provide a structure fixture holding a nested struct, a list of structs and a map of structs.
 */
final class WidgetBoxFixture implements SerializableStructInterface
{
    use \Smithy\Core\Shape\WithMemberValueTrait;

    public const SHAPE_ID = 'smithy.test#WidgetBox';

    private static ?Schema $SCHEMA = null;

    /**
     * @param list<WidgetFixture|null>|null $items
     * @param array<string, WidgetFixture|null>|null $byName
     */
    public function __construct(
        public readonly ?WidgetFixture $main = null,
        public readonly ?array $items = null,
        public readonly ?array $byName = null
    ) {}

    /**
     * Return the fixture schema.
     */
    public static function schema(): Schema
    {
        if (self::$SCHEMA !== null) {
            return self::$SCHEMA;
        }

        $widget = WidgetFixture::schema();

        return self::$SCHEMA = new Schema(
            id: self::SHAPE_ID,
            type: ShapeType::Structure,
            targetClass: self::class,
            members: [
                new Schema(
                    id: self::SHAPE_ID . '$main',
                    type: ShapeType::Structure,
                    memberName: 'main',
                    memberIndex: 0,
                    target: $widget,
                    targetClass: WidgetFixture::class
                ),
                new Schema(
                    id: self::SHAPE_ID . '$items',
                    type: ShapeType::List_,
                    memberName: 'items',
                    memberIndex: 1,
                    listMember: new Schema(
                        id: 'smithy.test#Widgets$member',
                        type: ShapeType::Structure,
                        memberName: 'member',
                        memberIndex: 0,
                        target: $widget,
                        targetClass: WidgetFixture::class
                    )
                ),
                new Schema(
                    id: self::SHAPE_ID . '$byName',
                    type: ShapeType::Map,
                    memberName: 'byName',
                    memberIndex: 2,
                    mapKey: new Schema(
                        id: 'smithy.test#WidgetMap$key',
                        type: ShapeType::String_,
                        memberName: 'key',
                        memberIndex: 0
                    ),
                    mapValue: new Schema(
                        id: 'smithy.test#WidgetMap$value',
                        type: ShapeType::Structure,
                        memberName: 'value',
                        memberIndex: 1,
                        target: $widget,
                        targetClass: WidgetFixture::class
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
        if ($this->main !== null) {
            $serializer->serializeStruct($members[0], $this->main, 0);
        }
        if ($this->items !== null) {
            $serializer->serializeList($members[1], $this->items, 1);
        }
        if ($this->byName !== null) {
            $serializer->serializeMap($members[2], $this->byName, 2);
        }
    }

    /**
     * Return the value of one member.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->main,
            1 => $this->items,
            2 => $this->byName,
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
