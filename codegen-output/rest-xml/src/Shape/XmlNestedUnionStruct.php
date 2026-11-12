<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestXml\Shape;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RestXml\Schemas;

final class XmlNestedUnionStruct implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.restxml#XmlNestedUnionStruct';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_STRING_VALUE;
    private static Schema $SCHEMA_BOOLEAN_VALUE;
    private static Schema $SCHEMA_BYTE_VALUE;
    private static Schema $SCHEMA_SHORT_VALUE;
    private static Schema $SCHEMA_INTEGER_VALUE;
    private static Schema $SCHEMA_LONG_VALUE;
    private static Schema $SCHEMA_FLOAT_VALUE;
    private static Schema $SCHEMA_DOUBLE_VALUE;

    public function __construct(
        public readonly ?string $stringValue = null,
        public readonly ?bool $booleanValue = null,
        public readonly ?int $byteValue = null,
        public readonly ?int $shortValue = null,
        public readonly ?int $integerValue = null,
        public readonly ?int $longValue = null,
        public readonly ?float $floatValue = null,
        public readonly ?float $doubleValue = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::xmlNestedUnionStruct();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_STRING_VALUE = $m[0];
        self::$SCHEMA_BOOLEAN_VALUE = $m[1];
        self::$SCHEMA_BYTE_VALUE = $m[2];
        self::$SCHEMA_SHORT_VALUE = $m[3];
        self::$SCHEMA_INTEGER_VALUE = $m[4];
        self::$SCHEMA_LONG_VALUE = $m[5];
        self::$SCHEMA_FLOAT_VALUE = $m[6];
        self::$SCHEMA_DOUBLE_VALUE = $m[7];
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
        if (isset($this->stringValue)) {
            $serializer->serializeString(self::$SCHEMA_STRING_VALUE, $this->stringValue, 0);
        }
        if (isset($this->booleanValue)) {
            $serializer->serializeBoolean(self::$SCHEMA_BOOLEAN_VALUE, $this->booleanValue, 1);
        }
        if (isset($this->byteValue)) {
            $serializer->serializeInteger(self::$SCHEMA_BYTE_VALUE, $this->byteValue, 2);
        }
        if (isset($this->shortValue)) {
            $serializer->serializeInteger(self::$SCHEMA_SHORT_VALUE, $this->shortValue, 3);
        }
        if (isset($this->integerValue)) {
            $serializer->serializeInteger(self::$SCHEMA_INTEGER_VALUE, $this->integerValue, 4);
        }
        if (isset($this->longValue)) {
            $serializer->serializeInteger(self::$SCHEMA_LONG_VALUE, $this->longValue, 5);
        }
        if (isset($this->floatValue)) {
            $serializer->serializeFloat(self::$SCHEMA_FLOAT_VALUE, $this->floatValue, 6);
        }
        if (isset($this->doubleValue)) {
            $serializer->serializeFloat(self::$SCHEMA_DOUBLE_VALUE, $this->doubleValue, 7);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->stringValue,
            1 => $this->booleanValue,
            2 => $this->byteValue,
            3 => $this->shortValue,
            4 => $this->integerValue,
            5 => $this->longValue,
            6 => $this->floatValue,
            7 => $this->doubleValue,
            default => null,
        };
    }

}
