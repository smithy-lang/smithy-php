<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Waiter\Generated\Waiter\Input;

use Smithy\Core\Serde\{
    Schema,
    SerializableStructInterface,
    ShapeSerializerInterface
};
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\Tests\Client\Waiter\Generated\Waiter\Schemas;
final class GetThingInput implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'smithy.waiter#GetThingInput';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_THING_ID;

    public function __construct(
        public readonly ?string $thingId = null
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
        $s = Schemas::getThingInput();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_THING_ID = $m[0];
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
        if (isset($this->thingId)) {
            $serializer->serializeString(self::$SCHEMA_THING_ID, $this->thingId, 0);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->thingId,
            default => null,
        };
    }

}
