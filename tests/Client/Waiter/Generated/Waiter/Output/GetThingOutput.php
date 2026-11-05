<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Waiter\Generated\Waiter\Output;

use Smithy\Core\Serde\{
    Schema,
    SerializableStructInterface,
    ShapeSerializerInterface
};
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\Tests\Client\Waiter\Generated\Waiter\Schemas;
final class GetThingOutput implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'smithy.waiter#GetThingOutput';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_STATUS;
    private static Schema $SCHEMA_FAILURE_REASON;

    public function __construct(
        public readonly ?string $status = null,
        public readonly ?string $failureReason = null
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
        $s = Schemas::getThingOutput();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_STATUS = $m[0];
        self::$SCHEMA_FAILURE_REASON = $m[1];
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
        if (isset($this->status)) {
            $serializer->serializeString(self::$SCHEMA_STATUS, $this->status, 0);
        }
        if (isset($this->failureReason)) {
            $serializer->serializeString(self::$SCHEMA_FAILURE_REASON, $this->failureReason, 1);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->status,
            1 => $this->failureReason,
            default => null,
        };
    }

}
