<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestXml\Input;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RestXml\Schemas;

final class XmlTimestampsRequest implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.restxml#XmlTimestampsRequest';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_NORMAL;
    private static Schema $SCHEMA_DATE_TIME;
    private static Schema $SCHEMA_DATE_TIME_ON_TARGET;
    private static Schema $SCHEMA_EPOCH_SECONDS;
    private static Schema $SCHEMA_EPOCH_SECONDS_ON_TARGET;
    private static Schema $SCHEMA_HTTP_DATE;
    private static Schema $SCHEMA_HTTP_DATE_ON_TARGET;

    public function __construct(
        public readonly ?float $normal = null,
        public readonly ?float $dateTime = null,
        public readonly ?float $dateTimeOnTarget = null,
        public readonly ?float $epochSeconds = null,
        public readonly ?float $epochSecondsOnTarget = null,
        public readonly ?float $httpDate = null,
        public readonly ?float $httpDateOnTarget = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::xmlTimestampsRequest();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_NORMAL = $m[0];
        self::$SCHEMA_DATE_TIME = $m[1];
        self::$SCHEMA_DATE_TIME_ON_TARGET = $m[2];
        self::$SCHEMA_EPOCH_SECONDS = $m[3];
        self::$SCHEMA_EPOCH_SECONDS_ON_TARGET = $m[4];
        self::$SCHEMA_HTTP_DATE = $m[5];
        self::$SCHEMA_HTTP_DATE_ON_TARGET = $m[6];
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
        if (isset($this->normal)) {
            $serializer->serializeTimestamp(self::$SCHEMA_NORMAL, $this->normal, 0);
        }
        if (isset($this->dateTime)) {
            $serializer->serializeTimestamp(self::$SCHEMA_DATE_TIME, $this->dateTime, 1);
        }
        if (isset($this->dateTimeOnTarget)) {
            $serializer->serializeTimestamp(self::$SCHEMA_DATE_TIME_ON_TARGET, $this->dateTimeOnTarget, 2);
        }
        if (isset($this->epochSeconds)) {
            $serializer->serializeTimestamp(self::$SCHEMA_EPOCH_SECONDS, $this->epochSeconds, 3);
        }
        if (isset($this->epochSecondsOnTarget)) {
            $serializer->serializeTimestamp(self::$SCHEMA_EPOCH_SECONDS_ON_TARGET, $this->epochSecondsOnTarget, 4);
        }
        if (isset($this->httpDate)) {
            $serializer->serializeTimestamp(self::$SCHEMA_HTTP_DATE, $this->httpDate, 5);
        }
        if (isset($this->httpDateOnTarget)) {
            $serializer->serializeTimestamp(self::$SCHEMA_HTTP_DATE_ON_TARGET, $this->httpDateOnTarget, 6);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->normal,
            1 => $this->dateTime,
            2 => $this->dateTimeOnTarget,
            3 => $this->epochSeconds,
            4 => $this->epochSecondsOnTarget,
            5 => $this->httpDate,
            6 => $this->httpDateOnTarget,
            default => null,
        };
    }

}
