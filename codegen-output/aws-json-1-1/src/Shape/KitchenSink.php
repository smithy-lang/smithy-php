<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\AwsJson1_1\Shape;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\ProtocolTests\AwsJson1_1\Schemas;

final class KitchenSink implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.json#KitchenSink';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_BLOB;
    private static Schema $SCHEMA_BOOLEAN;
    private static Schema $SCHEMA_DOUBLE;
    private static Schema $SCHEMA_EMPTY_STRUCT;
    private static Schema $SCHEMA_FLOAT;
    private static Schema $SCHEMA_HTTPDATE_TIMESTAMP;
    private static Schema $SCHEMA_INTEGER;
    private static Schema $SCHEMA_ISO8601_TIMESTAMP;
    private static Schema $SCHEMA_JSON_VALUE;
    private static Schema $SCHEMA_LIST_OF_LISTS;
    private static Schema $SCHEMA_LIST_OF_MAPS_OF_STRINGS;
    private static Schema $SCHEMA_LIST_OF_STRINGS;
    private static Schema $SCHEMA_LIST_OF_STRUCTS;
    private static Schema $SCHEMA_LONG;
    private static Schema $SCHEMA_MAP_OF_LISTS_OF_STRINGS;
    private static Schema $SCHEMA_MAP_OF_MAPS;
    private static Schema $SCHEMA_MAP_OF_STRINGS;
    private static Schema $SCHEMA_MAP_OF_STRUCTS;
    private static Schema $SCHEMA_RECURSIVE_LIST;
    private static Schema $SCHEMA_RECURSIVE_MAP;
    private static Schema $SCHEMA_RECURSIVE_STRUCT;
    private static Schema $SCHEMA_SIMPLE_STRUCT;
    private static Schema $SCHEMA_STRING;
    private static Schema $SCHEMA_STRUCT_WITH_JSON_NAME;
    private static Schema $SCHEMA_TIMESTAMP;
    private static Schema $SCHEMA_UNIX_TIMESTAMP;

    /**
     * @param list<list<string>>|null $listOfLists
     * @param list<array<string, string>>|null $listOfMapsOfStrings
     * @param list<string>|null $listOfStrings
     * @param list<\Smithy\ProtocolTests\AwsJson1_1\Shape\SimpleStruct>|null $listOfStructs
     * @param array<string, list<string>>|null $mapOfListsOfStrings
     * @param array<string, array<string, string>>|null $mapOfMaps
     * @param array<string, string>|null $mapOfStrings
     * @param array<string, \Smithy\ProtocolTests\AwsJson1_1\Shape\SimpleStruct>|null $mapOfStructs
     * @param list<\Smithy\ProtocolTests\AwsJson1_1\Shape\KitchenSink>|null $recursiveList
     * @param array<string, \Smithy\ProtocolTests\AwsJson1_1\Shape\KitchenSink>|null $recursiveMap
     */
    public function __construct(
        public readonly ?string $blob = null,
        public readonly ?bool $boolean = null,
        public readonly ?float $double = null,
        public readonly ?EmptyStruct $emptyStruct = null,
        public readonly ?float $float = null,
        public readonly ?float $httpdateTimestamp = null,
        public readonly ?int $integer = null,
        public readonly ?float $iso8601Timestamp = null,
        public readonly ?string $jsonValue = null,
        public readonly ?array $listOfLists = null,
        public readonly ?array $listOfMapsOfStrings = null,
        public readonly ?array $listOfStrings = null,
        public readonly ?array $listOfStructs = null,
        public readonly ?int $long = null,
        public readonly ?array $mapOfListsOfStrings = null,
        public readonly ?array $mapOfMaps = null,
        public readonly ?array $mapOfStrings = null,
        public readonly ?array $mapOfStructs = null,
        public readonly ?array $recursiveList = null,
        public readonly ?array $recursiveMap = null,
        public readonly ?KitchenSink $recursiveStruct = null,
        public readonly ?SimpleStruct $simpleStruct = null,
        public readonly ?string $string = null,
        public readonly ?StructWithJsonName $structWithJsonName = null,
        public readonly ?float $timestamp = null,
        public readonly ?float $unixTimestamp = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::kitchenSink();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_BLOB = $m[0];
        self::$SCHEMA_BOOLEAN = $m[1];
        self::$SCHEMA_DOUBLE = $m[2];
        self::$SCHEMA_EMPTY_STRUCT = $m[3];
        self::$SCHEMA_FLOAT = $m[4];
        self::$SCHEMA_HTTPDATE_TIMESTAMP = $m[5];
        self::$SCHEMA_INTEGER = $m[6];
        self::$SCHEMA_ISO8601_TIMESTAMP = $m[7];
        self::$SCHEMA_JSON_VALUE = $m[8];
        self::$SCHEMA_LIST_OF_LISTS = $m[9];
        self::$SCHEMA_LIST_OF_MAPS_OF_STRINGS = $m[10];
        self::$SCHEMA_LIST_OF_STRINGS = $m[11];
        self::$SCHEMA_LIST_OF_STRUCTS = $m[12];
        self::$SCHEMA_LONG = $m[13];
        self::$SCHEMA_MAP_OF_LISTS_OF_STRINGS = $m[14];
        self::$SCHEMA_MAP_OF_MAPS = $m[15];
        self::$SCHEMA_MAP_OF_STRINGS = $m[16];
        self::$SCHEMA_MAP_OF_STRUCTS = $m[17];
        self::$SCHEMA_RECURSIVE_LIST = $m[18];
        self::$SCHEMA_RECURSIVE_MAP = $m[19];
        self::$SCHEMA_RECURSIVE_STRUCT = $m[20];
        self::$SCHEMA_SIMPLE_STRUCT = $m[21];
        self::$SCHEMA_STRING = $m[22];
        self::$SCHEMA_STRUCT_WITH_JSON_NAME = $m[23];
        self::$SCHEMA_TIMESTAMP = $m[24];
        self::$SCHEMA_UNIX_TIMESTAMP = $m[25];
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
        if (isset($this->blob)) {
            $serializer->serializeBlob(self::$SCHEMA_BLOB, $this->blob, 0);
        }
        if (isset($this->boolean)) {
            $serializer->serializeBoolean(self::$SCHEMA_BOOLEAN, $this->boolean, 1);
        }
        if (isset($this->double)) {
            $serializer->serializeFloat(self::$SCHEMA_DOUBLE, $this->double, 2);
        }
        if (isset($this->emptyStruct)) {
            $serializer->serializeStruct(self::$SCHEMA_EMPTY_STRUCT, $this->emptyStruct, 3);
        }
        if (isset($this->float)) {
            $serializer->serializeFloat(self::$SCHEMA_FLOAT, $this->float, 4);
        }
        if (isset($this->httpdateTimestamp)) {
            $serializer->serializeTimestamp(self::$SCHEMA_HTTPDATE_TIMESTAMP, $this->httpdateTimestamp, 5);
        }
        if (isset($this->integer)) {
            $serializer->serializeInteger(self::$SCHEMA_INTEGER, $this->integer, 6);
        }
        if (isset($this->iso8601Timestamp)) {
            $serializer->serializeTimestamp(self::$SCHEMA_ISO8601_TIMESTAMP, $this->iso8601Timestamp, 7);
        }
        if (isset($this->jsonValue)) {
            $serializer->serializeString(self::$SCHEMA_JSON_VALUE, $this->jsonValue, 8);
        }
        if (isset($this->listOfLists)) {
            $serializer->serializeList(self::$SCHEMA_LIST_OF_LISTS, $this->listOfLists, 9);
        }
        if (isset($this->listOfMapsOfStrings)) {
            $serializer->serializeList(self::$SCHEMA_LIST_OF_MAPS_OF_STRINGS, $this->listOfMapsOfStrings, 10);
        }
        if (isset($this->listOfStrings)) {
            $serializer->serializeList(self::$SCHEMA_LIST_OF_STRINGS, $this->listOfStrings, 11);
        }
        if (isset($this->listOfStructs)) {
            $serializer->serializeList(self::$SCHEMA_LIST_OF_STRUCTS, $this->listOfStructs, 12);
        }
        if (isset($this->long)) {
            $serializer->serializeInteger(self::$SCHEMA_LONG, $this->long, 13);
        }
        if (isset($this->mapOfListsOfStrings)) {
            $serializer->serializeMap(self::$SCHEMA_MAP_OF_LISTS_OF_STRINGS, $this->mapOfListsOfStrings, 14);
        }
        if (isset($this->mapOfMaps)) {
            $serializer->serializeMap(self::$SCHEMA_MAP_OF_MAPS, $this->mapOfMaps, 15);
        }
        if (isset($this->mapOfStrings)) {
            $serializer->serializeMap(self::$SCHEMA_MAP_OF_STRINGS, $this->mapOfStrings, 16);
        }
        if (isset($this->mapOfStructs)) {
            $serializer->serializeMap(self::$SCHEMA_MAP_OF_STRUCTS, $this->mapOfStructs, 17);
        }
        if (isset($this->recursiveList)) {
            $serializer->serializeList(self::$SCHEMA_RECURSIVE_LIST, $this->recursiveList, 18);
        }
        if (isset($this->recursiveMap)) {
            $serializer->serializeMap(self::$SCHEMA_RECURSIVE_MAP, $this->recursiveMap, 19);
        }
        if (isset($this->recursiveStruct)) {
            $serializer->serializeStruct(self::$SCHEMA_RECURSIVE_STRUCT, $this->recursiveStruct, 20);
        }
        if (isset($this->simpleStruct)) {
            $serializer->serializeStruct(self::$SCHEMA_SIMPLE_STRUCT, $this->simpleStruct, 21);
        }
        if (isset($this->string)) {
            $serializer->serializeString(self::$SCHEMA_STRING, $this->string, 22);
        }
        if (isset($this->structWithJsonName)) {
            $serializer->serializeStruct(self::$SCHEMA_STRUCT_WITH_JSON_NAME, $this->structWithJsonName, 23);
        }
        if (isset($this->timestamp)) {
            $serializer->serializeTimestamp(self::$SCHEMA_TIMESTAMP, $this->timestamp, 24);
        }
        if (isset($this->unixTimestamp)) {
            $serializer->serializeTimestamp(self::$SCHEMA_UNIX_TIMESTAMP, $this->unixTimestamp, 25);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->blob,
            1 => $this->boolean,
            2 => $this->double,
            3 => $this->emptyStruct,
            4 => $this->float,
            5 => $this->httpdateTimestamp,
            6 => $this->integer,
            7 => $this->iso8601Timestamp,
            8 => $this->jsonValue,
            9 => $this->listOfLists,
            10 => $this->listOfMapsOfStrings,
            11 => $this->listOfStrings,
            12 => $this->listOfStructs,
            13 => $this->long,
            14 => $this->mapOfListsOfStrings,
            15 => $this->mapOfMaps,
            16 => $this->mapOfStrings,
            17 => $this->mapOfStructs,
            18 => $this->recursiveList,
            19 => $this->recursiveMap,
            20 => $this->recursiveStruct,
            21 => $this->simpleStruct,
            22 => $this->string,
            23 => $this->structWithJsonName,
            24 => $this->timestamp,
            25 => $this->unixTimestamp,
            default => null,
        };
    }

    /**
     * Return a copy with one member replaced.
     */
    public function withMemberValue(Schema $member, mixed $value): static
    {
        $members = [
            $this->blob,
            $this->boolean,
            $this->double,
            $this->emptyStruct,
            $this->float,
            $this->httpdateTimestamp,
            $this->integer,
            $this->iso8601Timestamp,
            $this->jsonValue,
            $this->listOfLists,
            $this->listOfMapsOfStrings,
            $this->listOfStrings,
            $this->listOfStructs,
            $this->long,
            $this->mapOfListsOfStrings,
            $this->mapOfMaps,
            $this->mapOfStrings,
            $this->mapOfStructs,
            $this->recursiveList,
            $this->recursiveMap,
            $this->recursiveStruct,
            $this->simpleStruct,
            $this->string,
            $this->structWithJsonName,
            $this->timestamp,
            $this->unixTimestamp
        ];
        $members[$member->memberIndex] = $value;

        return new self(...$members);
    }

}
