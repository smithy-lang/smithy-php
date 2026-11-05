<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codec\Json\Fixture;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;


final class JsonEnumStructFixture
{
    private static ?Schema $SCHEMA = null;

    public function __construct(
        public readonly JsonStringEnumFixture|string|null $status = null,
        public readonly JsonIntEnumFixture|int|null $priority = null,
        public readonly ?array $statuses = null,
        public readonly ?array $priorities = null
    ) {}

    /**
     * Return the fixture schema.
     */
    public static function schema(): Schema
    {
        if (self::$SCHEMA !== null) {
            return self::$SCHEMA;
        }

        $statusMember = new Schema(
            id: 'smithy.test#JsonEnumStruct$status',
            type: ShapeType::Enum,
            memberName: 'status',
            memberIndex: 0,
            targetClass: JsonStringEnumFixture::class
        );
        $priorityMember = new Schema(
            id: 'smithy.test#JsonEnumStruct$priority',
            type: ShapeType::IntEnum,
            memberName: 'priority',
            memberIndex: 1,
            targetClass: JsonIntEnumFixture::class
        );
        $listMember = new Schema(
            id: 'smithy.test#JsonEnumStruct$statuses$member',
            type: ShapeType::Enum,
            memberName: 'member',
            memberIndex: 0,
            targetClass: JsonStringEnumFixture::class
        );
        $mapKey = new Schema(
            id: 'smithy.test#JsonEnumStruct$priorities$key',
            type: ShapeType::String_,
            memberName: 'key',
            memberIndex: 0
        );
        $mapValue = new Schema(
            id: 'smithy.test#JsonEnumStruct$priorities$value',
            type: ShapeType::IntEnum,
            memberName: 'value',
            memberIndex: 1,
            targetClass: JsonIntEnumFixture::class
        );

        self::$SCHEMA = new Schema(
            id: 'smithy.test#JsonEnumStruct',
            type: ShapeType::Structure,
            members: [
                $statusMember,
                $priorityMember,
                new Schema(
                    id: 'smithy.test#JsonEnumStruct$statuses',
                    type: ShapeType::List_,
                    memberName: 'statuses',
                    memberIndex: 2,
                    listMember: $listMember
                ),
                new Schema(
                    id: 'smithy.test#JsonEnumStruct$priorities',
                    type: ShapeType::Map,
                    memberName: 'priorities',
                    memberIndex: 3,
                    mapKey: $mapKey,
                    mapValue: $mapValue
                ),
            ]
        );

        return self::$SCHEMA;
    }

    /**
     * Rehydrate this fixture.
     *
     * @param array<int, mixed> $fields Positional members.
     */
    public static function fromMembers(array $fields): static
    {
        return new self(
            status: $fields[0] ?? null,
            priority: $fields[1] ?? null,
            statuses: $fields[2] ?? null,
            priorities: $fields[3] ?? null
        );
    }
}
