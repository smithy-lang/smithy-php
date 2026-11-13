<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestJson\Shape;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RestJson\Schemas;

final class TimestampFormatHeadersIO implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.restjson#TimestampFormatHeadersIO';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_MEMBER_EPOCH_SECONDS;
    private static Schema $SCHEMA_MEMBER_HTTP_DATE;
    private static Schema $SCHEMA_MEMBER_DATE_TIME;
    private static Schema $SCHEMA_DEFAULT_FORMAT;
    private static Schema $SCHEMA_TARGET_EPOCH_SECONDS;
    private static Schema $SCHEMA_TARGET_HTTP_DATE;
    private static Schema $SCHEMA_TARGET_DATE_TIME;

    public function __construct(
        public readonly ?float $memberEpochSeconds = null,
        public readonly ?float $memberHttpDate = null,
        public readonly ?float $memberDateTime = null,
        public readonly ?float $defaultFormat = null,
        public readonly ?float $targetEpochSeconds = null,
        public readonly ?float $targetHttpDate = null,
        public readonly ?float $targetDateTime = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::timestampFormatHeadersIO();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_MEMBER_EPOCH_SECONDS = $m[0];
        self::$SCHEMA_MEMBER_HTTP_DATE = $m[1];
        self::$SCHEMA_MEMBER_DATE_TIME = $m[2];
        self::$SCHEMA_DEFAULT_FORMAT = $m[3];
        self::$SCHEMA_TARGET_EPOCH_SECONDS = $m[4];
        self::$SCHEMA_TARGET_HTTP_DATE = $m[5];
        self::$SCHEMA_TARGET_DATE_TIME = $m[6];
        return self::$SCHEMA = $s;
    }

    /**
     * Get the shape schema for this instance.
     */
    public function getSchema(): Schema
    {
        return self::schema();
    }

    /**
     * Serialize the shape to the desired wire format.
     *
     * @return void
     */
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
        if (isset($this->memberEpochSeconds)) {
            $serializer->serializeTimestamp(self::$SCHEMA_MEMBER_EPOCH_SECONDS, $this->memberEpochSeconds, 0);
        }
        if (isset($this->memberHttpDate)) {
            $serializer->serializeTimestamp(self::$SCHEMA_MEMBER_HTTP_DATE, $this->memberHttpDate, 1);
        }
        if (isset($this->memberDateTime)) {
            $serializer->serializeTimestamp(self::$SCHEMA_MEMBER_DATE_TIME, $this->memberDateTime, 2);
        }
        if (isset($this->defaultFormat)) {
            $serializer->serializeTimestamp(self::$SCHEMA_DEFAULT_FORMAT, $this->defaultFormat, 3);
        }
        if (isset($this->targetEpochSeconds)) {
            $serializer->serializeTimestamp(self::$SCHEMA_TARGET_EPOCH_SECONDS, $this->targetEpochSeconds, 4);
        }
        if (isset($this->targetHttpDate)) {
            $serializer->serializeTimestamp(self::$SCHEMA_TARGET_HTTP_DATE, $this->targetHttpDate, 5);
        }
        if (isset($this->targetDateTime)) {
            $serializer->serializeTimestamp(self::$SCHEMA_TARGET_DATE_TIME, $this->targetDateTime, 6);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->memberEpochSeconds,
            1 => $this->memberHttpDate,
            2 => $this->memberDateTime,
            3 => $this->defaultFormat,
            4 => $this->targetEpochSeconds,
            5 => $this->targetHttpDate,
            6 => $this->targetDateTime,
            default => null,
        };
    }

}
