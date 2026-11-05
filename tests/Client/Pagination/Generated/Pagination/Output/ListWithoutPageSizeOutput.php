<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Pagination\Generated\Pagination\Output;

use Smithy\Core\Serde\{
    Schema,
    SerializableStructInterface,
    ShapeSerializerInterface
};
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\Tests\Client\Pagination\Generated\Pagination\Schemas;
final class ListWithoutPageSizeOutput implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'smithy.pagination#ListWithoutPageSizeOutput';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_ITEMS;
    private static Schema $SCHEMA_CURSOR;

    /**
     * @param list<string>|null $items
     */
    public function __construct(
        public readonly ?array $items = null,
        public readonly ?string $cursor = null
    ) {
        self::schema();
    }

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::listWithoutPageSizeOutput();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_ITEMS = $m[0];
        self::$SCHEMA_CURSOR = $m[1];
        return self::$SCHEMA = $s;
    }

    /**
     * Serialize the shape to the desired wire format.
     *
     * @return void
     */
    public function getSchema(): Schema
    {
        return self::schema();
    }

    public function serialize(ShapeSerializerInterface $serializer, int $idx): void
    {
        $serializer->serializeStruct(self::schema(), $this, $idx);
    }

    /**
     * Serialize the shape members to the desired wire format.
     *
     * @return void
     */
    public function serializeMembers(ShapeSerializerInterface $serializer): void
    {
        if (isset($this->items)) {
            $serializer->serializeList(self::$SCHEMA_ITEMS, $this->items, 0);
        }
        if (isset($this->cursor)) {
            $serializer->serializeString(self::$SCHEMA_CURSOR, $this->cursor, 1);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->items,
            1 => $this->cursor,
            default => null,
        };
    }

}
