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
use Smithy\ProtocolTests\RestXml\Shape\FooEnum;
use Smithy\ProtocolTests\RestXml\Shape\IntegerEnum;

final class AllQueryStringTypesInput implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.restxml#AllQueryStringTypesInput';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_QUERY_STRING;
    private static Schema $SCHEMA_QUERY_STRING_LIST;
    private static Schema $SCHEMA_QUERY_STRING_SET;
    private static Schema $SCHEMA_QUERY_BYTE;
    private static Schema $SCHEMA_QUERY_SHORT;
    private static Schema $SCHEMA_QUERY_INTEGER;
    private static Schema $SCHEMA_QUERY_INTEGER_LIST;
    private static Schema $SCHEMA_QUERY_INTEGER_SET;
    private static Schema $SCHEMA_QUERY_LONG;
    private static Schema $SCHEMA_QUERY_FLOAT;
    private static Schema $SCHEMA_QUERY_DOUBLE;
    private static Schema $SCHEMA_QUERY_DOUBLE_LIST;
    private static Schema $SCHEMA_QUERY_BOOLEAN;
    private static Schema $SCHEMA_QUERY_BOOLEAN_LIST;
    private static Schema $SCHEMA_QUERY_TIMESTAMP;
    private static Schema $SCHEMA_QUERY_TIMESTAMP_LIST;
    private static Schema $SCHEMA_QUERY_ENUM;
    private static Schema $SCHEMA_QUERY_ENUM_LIST;
    private static Schema $SCHEMA_QUERY_INTEGER_ENUM;
    private static Schema $SCHEMA_QUERY_INTEGER_ENUM_LIST;
    private static Schema $SCHEMA_QUERY_PARAMS_MAP_OF_STRINGS;

    public readonly ?FooEnum $queryEnum;
    public readonly ?IntegerEnum $queryIntegerEnum;

    /**
     * @param list<string>|null $queryStringList
     * @param list<string>|null $queryStringSet
     * @param list<int>|null $queryIntegerList
     * @param list<int>|null $queryIntegerSet
     * @param list<float>|null $queryDoubleList
     * @param list<bool>|null $queryBooleanList
     * @param list<float>|null $queryTimestampList
     * @param list<\Smithy\ProtocolTests\RestXml\Shape\FooEnum>|null $queryEnumList
     * @param list<\Smithy\ProtocolTests\RestXml\Shape\IntegerEnum>|null $queryIntegerEnumList
     * @param array<string, string>|null $queryParamsMapOfStrings
     */
    public function __construct(
        public readonly ?string $queryString = null,
        public readonly ?array $queryStringList = null,
        public readonly ?array $queryStringSet = null,
        public readonly ?int $queryByte = null,
        public readonly ?int $queryShort = null,
        public readonly ?int $queryInteger = null,
        public readonly ?array $queryIntegerList = null,
        public readonly ?array $queryIntegerSet = null,
        public readonly ?int $queryLong = null,
        public readonly ?float $queryFloat = null,
        public readonly ?float $queryDouble = null,
        public readonly ?array $queryDoubleList = null,
        public readonly ?bool $queryBoolean = null,
        public readonly ?array $queryBooleanList = null,
        public readonly ?float $queryTimestamp = null,
        public readonly ?array $queryTimestampList = null,
        FooEnum|string|null $queryEnum = null,
        public readonly ?array $queryEnumList = null,
        IntegerEnum|int|null $queryIntegerEnum = null,
        public readonly ?array $queryIntegerEnumList = null,
        public readonly ?array $queryParamsMapOfStrings = null
    ) {
        if ($queryEnum !== null) {
            $this->queryEnum = $queryEnum instanceof FooEnum ? $queryEnum : FooEnum::fromValue($queryEnum);
        }
        if ($queryIntegerEnum !== null) {
            $this->queryIntegerEnum = $queryIntegerEnum instanceof IntegerEnum ? $queryIntegerEnum : IntegerEnum::fromValue($queryIntegerEnum);
        }
    }

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::allQueryStringTypesInput();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_QUERY_STRING = $m[0];
        self::$SCHEMA_QUERY_STRING_LIST = $m[1];
        self::$SCHEMA_QUERY_STRING_SET = $m[2];
        self::$SCHEMA_QUERY_BYTE = $m[3];
        self::$SCHEMA_QUERY_SHORT = $m[4];
        self::$SCHEMA_QUERY_INTEGER = $m[5];
        self::$SCHEMA_QUERY_INTEGER_LIST = $m[6];
        self::$SCHEMA_QUERY_INTEGER_SET = $m[7];
        self::$SCHEMA_QUERY_LONG = $m[8];
        self::$SCHEMA_QUERY_FLOAT = $m[9];
        self::$SCHEMA_QUERY_DOUBLE = $m[10];
        self::$SCHEMA_QUERY_DOUBLE_LIST = $m[11];
        self::$SCHEMA_QUERY_BOOLEAN = $m[12];
        self::$SCHEMA_QUERY_BOOLEAN_LIST = $m[13];
        self::$SCHEMA_QUERY_TIMESTAMP = $m[14];
        self::$SCHEMA_QUERY_TIMESTAMP_LIST = $m[15];
        self::$SCHEMA_QUERY_ENUM = $m[16];
        self::$SCHEMA_QUERY_ENUM_LIST = $m[17];
        self::$SCHEMA_QUERY_INTEGER_ENUM = $m[18];
        self::$SCHEMA_QUERY_INTEGER_ENUM_LIST = $m[19];
        self::$SCHEMA_QUERY_PARAMS_MAP_OF_STRINGS = $m[20];
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
        if (isset($this->queryString)) {
            $serializer->serializeString(self::$SCHEMA_QUERY_STRING, $this->queryString, 0);
        }
        if (isset($this->queryStringList)) {
            $serializer->serializeList(self::$SCHEMA_QUERY_STRING_LIST, $this->queryStringList, 1);
        }
        if (isset($this->queryStringSet)) {
            $serializer->serializeList(self::$SCHEMA_QUERY_STRING_SET, $this->queryStringSet, 2);
        }
        if (isset($this->queryByte)) {
            $serializer->serializeInteger(self::$SCHEMA_QUERY_BYTE, $this->queryByte, 3);
        }
        if (isset($this->queryShort)) {
            $serializer->serializeInteger(self::$SCHEMA_QUERY_SHORT, $this->queryShort, 4);
        }
        if (isset($this->queryInteger)) {
            $serializer->serializeInteger(self::$SCHEMA_QUERY_INTEGER, $this->queryInteger, 5);
        }
        if (isset($this->queryIntegerList)) {
            $serializer->serializeList(self::$SCHEMA_QUERY_INTEGER_LIST, $this->queryIntegerList, 6);
        }
        if (isset($this->queryIntegerSet)) {
            $serializer->serializeList(self::$SCHEMA_QUERY_INTEGER_SET, $this->queryIntegerSet, 7);
        }
        if (isset($this->queryLong)) {
            $serializer->serializeInteger(self::$SCHEMA_QUERY_LONG, $this->queryLong, 8);
        }
        if (isset($this->queryFloat)) {
            $serializer->serializeFloat(self::$SCHEMA_QUERY_FLOAT, $this->queryFloat, 9);
        }
        if (isset($this->queryDouble)) {
            $serializer->serializeFloat(self::$SCHEMA_QUERY_DOUBLE, $this->queryDouble, 10);
        }
        if (isset($this->queryDoubleList)) {
            $serializer->serializeList(self::$SCHEMA_QUERY_DOUBLE_LIST, $this->queryDoubleList, 11);
        }
        if (isset($this->queryBoolean)) {
            $serializer->serializeBoolean(self::$SCHEMA_QUERY_BOOLEAN, $this->queryBoolean, 12);
        }
        if (isset($this->queryBooleanList)) {
            $serializer->serializeList(self::$SCHEMA_QUERY_BOOLEAN_LIST, $this->queryBooleanList, 13);
        }
        if (isset($this->queryTimestamp)) {
            $serializer->serializeTimestamp(self::$SCHEMA_QUERY_TIMESTAMP, $this->queryTimestamp, 14);
        }
        if (isset($this->queryTimestampList)) {
            $serializer->serializeList(self::$SCHEMA_QUERY_TIMESTAMP_LIST, $this->queryTimestampList, 15);
        }
        if (isset($this->queryEnum)) {
            $serializer->serializeString(self::$SCHEMA_QUERY_ENUM, $this->queryEnum->value, 16);
        }
        if (isset($this->queryEnumList)) {
            $serializer->serializeList(self::$SCHEMA_QUERY_ENUM_LIST, $this->queryEnumList, 17);
        }
        if (isset($this->queryIntegerEnum)) {
            $serializer->serializeInteger(self::$SCHEMA_QUERY_INTEGER_ENUM, $this->queryIntegerEnum->value, 18);
        }
        if (isset($this->queryIntegerEnumList)) {
            $serializer->serializeList(self::$SCHEMA_QUERY_INTEGER_ENUM_LIST, $this->queryIntegerEnumList, 19);
        }
        if (isset($this->queryParamsMapOfStrings)) {
            $serializer->serializeMap(self::$SCHEMA_QUERY_PARAMS_MAP_OF_STRINGS, $this->queryParamsMapOfStrings, 20);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->queryString,
            1 => $this->queryStringList,
            2 => $this->queryStringSet,
            3 => $this->queryByte,
            4 => $this->queryShort,
            5 => $this->queryInteger,
            6 => $this->queryIntegerList,
            7 => $this->queryIntegerSet,
            8 => $this->queryLong,
            9 => $this->queryFloat,
            10 => $this->queryDouble,
            11 => $this->queryDoubleList,
            12 => $this->queryBoolean,
            13 => $this->queryBooleanList,
            14 => $this->queryTimestamp,
            15 => $this->queryTimestampList,
            16 => $this->queryEnum ?? null,
            17 => $this->queryEnumList,
            18 => $this->queryIntegerEnum ?? null,
            19 => $this->queryIntegerEnumList,
            20 => $this->queryParamsMapOfStrings,
            default => null,
        };
    }

}
