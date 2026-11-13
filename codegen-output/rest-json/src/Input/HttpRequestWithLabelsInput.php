<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestJson\Input;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RestJson\Schemas;

final class HttpRequestWithLabelsInput implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.restjson#HttpRequestWithLabelsInput';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_STRING;
    private static Schema $SCHEMA_SHORT;
    private static Schema $SCHEMA_INTEGER;
    private static Schema $SCHEMA_LONG;
    private static Schema $SCHEMA_FLOAT;
    private static Schema $SCHEMA_DOUBLE;
    private static Schema $SCHEMA_BOOLEAN;
    private static Schema $SCHEMA_TIMESTAMP;

    /**
     * @param ?bool $boolean Serialized in the path as true or false.
     * @param ?float $timestamp Note that this member has no format, so it's serialized as an RFC 3399 date-time.
     */
    public function __construct(
        public readonly ?string $string = null,
        public readonly ?int $short = null,
        public readonly ?int $integer = null,
        public readonly ?int $long = null,
        public readonly ?float $float = null,
        public readonly ?float $double = null,
        public readonly ?bool $boolean = null,
        public readonly ?float $timestamp = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::httpRequestWithLabelsInput();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_STRING = $m[0];
        self::$SCHEMA_SHORT = $m[1];
        self::$SCHEMA_INTEGER = $m[2];
        self::$SCHEMA_LONG = $m[3];
        self::$SCHEMA_FLOAT = $m[4];
        self::$SCHEMA_DOUBLE = $m[5];
        self::$SCHEMA_BOOLEAN = $m[6];
        self::$SCHEMA_TIMESTAMP = $m[7];
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
        if (isset($this->string)) {
            $serializer->serializeString(self::$SCHEMA_STRING, $this->string, 0);
        }
        if (isset($this->short)) {
            $serializer->serializeInteger(self::$SCHEMA_SHORT, $this->short, 1);
        }
        if (isset($this->integer)) {
            $serializer->serializeInteger(self::$SCHEMA_INTEGER, $this->integer, 2);
        }
        if (isset($this->long)) {
            $serializer->serializeInteger(self::$SCHEMA_LONG, $this->long, 3);
        }
        if (isset($this->float)) {
            $serializer->serializeFloat(self::$SCHEMA_FLOAT, $this->float, 4);
        }
        if (isset($this->double)) {
            $serializer->serializeFloat(self::$SCHEMA_DOUBLE, $this->double, 5);
        }
        if (isset($this->boolean)) {
            $serializer->serializeBoolean(self::$SCHEMA_BOOLEAN, $this->boolean, 6);
        }
        if (isset($this->timestamp)) {
            $serializer->serializeTimestamp(self::$SCHEMA_TIMESTAMP, $this->timestamp, 7);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->string,
            1 => $this->short,
            2 => $this->integer,
            3 => $this->long,
            4 => $this->float,
            5 => $this->double,
            6 => $this->boolean,
            7 => $this->timestamp,
            default => null,
        };
    }

}
