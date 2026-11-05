<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RpcV2Json\Shape;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RpcV2Json\Schemas;

final class Defaults implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'smithy.protocoltests.rpcv2Json#Defaults';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_DEFAULT_STRING;
    private static Schema $SCHEMA_DEFAULT_BOOLEAN;
    private static Schema $SCHEMA_DEFAULT_LIST;
    private static Schema $SCHEMA_DEFAULT_TIMESTAMP;
    private static Schema $SCHEMA_DEFAULT_BLOB;
    private static Schema $SCHEMA_DEFAULT_BYTE;
    private static Schema $SCHEMA_DEFAULT_SHORT;
    private static Schema $SCHEMA_DEFAULT_INTEGER;
    private static Schema $SCHEMA_DEFAULT_LONG;
    private static Schema $SCHEMA_DEFAULT_FLOAT;
    private static Schema $SCHEMA_DEFAULT_DOUBLE;
    private static Schema $SCHEMA_DEFAULT_MAP;
    private static Schema $SCHEMA_DEFAULT_ENUM;
    private static Schema $SCHEMA_DEFAULT_INT_ENUM;
    private static Schema $SCHEMA_EMPTY_STRING;
    private static Schema $SCHEMA_FALSE_BOOLEAN;
    private static Schema $SCHEMA_EMPTY_BLOB;
    private static Schema $SCHEMA_ZERO_BYTE;
    private static Schema $SCHEMA_ZERO_SHORT;
    private static Schema $SCHEMA_ZERO_INTEGER;
    private static Schema $SCHEMA_ZERO_LONG;
    private static Schema $SCHEMA_ZERO_FLOAT;
    private static Schema $SCHEMA_ZERO_DOUBLE;

    public readonly string $defaultString;
    public readonly bool $defaultBoolean;
    public readonly array $defaultList;
    public readonly float $defaultTimestamp;
    public readonly string $defaultBlob;
    public readonly int $defaultByte;
    public readonly int $defaultShort;
    public readonly int $defaultInteger;
    public readonly int $defaultLong;
    public readonly float $defaultFloat;
    public readonly float $defaultDouble;
    public readonly array $defaultMap;
    public readonly TestEnum $defaultEnum;
    public readonly TestIntEnum $defaultIntEnum;
    public readonly string $emptyString;
    public readonly bool $falseBoolean;
    public readonly string $emptyBlob;
    public readonly int $zeroByte;
    public readonly int $zeroShort;
    public readonly int $zeroInteger;
    public readonly int $zeroLong;
    public readonly float $zeroFloat;
    public readonly float $zeroDouble;

    /**
     * @param list<string>|null $defaultList
     * @param array<string, string>|null $defaultMap
     */
    public function __construct(
        ?string $defaultString = null,
        ?bool $defaultBoolean = null,
        ?array $defaultList = null,
        ?float $defaultTimestamp = null,
        ?string $defaultBlob = null,
        ?int $defaultByte = null,
        ?int $defaultShort = null,
        ?int $defaultInteger = null,
        ?int $defaultLong = null,
        ?float $defaultFloat = null,
        ?float $defaultDouble = null,
        ?array $defaultMap = null,
        TestEnum|string|null $defaultEnum = null,
        TestIntEnum|int|null $defaultIntEnum = null,
        ?string $emptyString = null,
        ?bool $falseBoolean = null,
        ?string $emptyBlob = null,
        ?int $zeroByte = null,
        ?int $zeroShort = null,
        ?int $zeroInteger = null,
        ?int $zeroLong = null,
        ?float $zeroFloat = null,
        ?float $zeroDouble = null
    ) {
        $this->defaultString = $defaultString ?? 'hi';
        $this->defaultBoolean = $defaultBoolean ?? true;
        $this->defaultList = $defaultList ?? [];
        $this->defaultTimestamp = $defaultTimestamp ?? 0.0;
        $this->defaultBlob = $defaultBlob ?? "\x61\x62\x63";
        $this->defaultByte = $defaultByte ?? 1;
        $this->defaultShort = $defaultShort ?? 1;
        $this->defaultInteger = $defaultInteger ?? 10;
        $this->defaultLong = $defaultLong ?? 100;
        $this->defaultFloat = $defaultFloat ?? 1.0;
        $this->defaultDouble = $defaultDouble ?? 1.0;
        $this->defaultMap = $defaultMap ?? [];
        $this->defaultEnum = $defaultEnum instanceof TestEnum ? $defaultEnum : TestEnum::fromValue($defaultEnum ?? 'FOO');
        $this->defaultIntEnum = $defaultIntEnum instanceof TestIntEnum ? $defaultIntEnum : TestIntEnum::fromValue($defaultIntEnum ?? 1);
        $this->emptyString = $emptyString ?? '';
        $this->falseBoolean = $falseBoolean ?? false;
        $this->emptyBlob = $emptyBlob ?? '';
        $this->zeroByte = $zeroByte ?? 0;
        $this->zeroShort = $zeroShort ?? 0;
        $this->zeroInteger = $zeroInteger ?? 0;
        $this->zeroLong = $zeroLong ?? 0;
        $this->zeroFloat = $zeroFloat ?? 0.0;
        $this->zeroDouble = $zeroDouble ?? 0.0;
    }

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::defaults();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_DEFAULT_STRING = $m[0];
        self::$SCHEMA_DEFAULT_BOOLEAN = $m[1];
        self::$SCHEMA_DEFAULT_LIST = $m[2];
        self::$SCHEMA_DEFAULT_TIMESTAMP = $m[3];
        self::$SCHEMA_DEFAULT_BLOB = $m[4];
        self::$SCHEMA_DEFAULT_BYTE = $m[5];
        self::$SCHEMA_DEFAULT_SHORT = $m[6];
        self::$SCHEMA_DEFAULT_INTEGER = $m[7];
        self::$SCHEMA_DEFAULT_LONG = $m[8];
        self::$SCHEMA_DEFAULT_FLOAT = $m[9];
        self::$SCHEMA_DEFAULT_DOUBLE = $m[10];
        self::$SCHEMA_DEFAULT_MAP = $m[11];
        self::$SCHEMA_DEFAULT_ENUM = $m[12];
        self::$SCHEMA_DEFAULT_INT_ENUM = $m[13];
        self::$SCHEMA_EMPTY_STRING = $m[14];
        self::$SCHEMA_FALSE_BOOLEAN = $m[15];
        self::$SCHEMA_EMPTY_BLOB = $m[16];
        self::$SCHEMA_ZERO_BYTE = $m[17];
        self::$SCHEMA_ZERO_SHORT = $m[18];
        self::$SCHEMA_ZERO_INTEGER = $m[19];
        self::$SCHEMA_ZERO_LONG = $m[20];
        self::$SCHEMA_ZERO_FLOAT = $m[21];
        self::$SCHEMA_ZERO_DOUBLE = $m[22];
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
        if (isset($this->defaultString)) {
            $serializer->serializeString(self::$SCHEMA_DEFAULT_STRING, $this->defaultString, 0);
        }
        if (isset($this->defaultBoolean)) {
            $serializer->serializeBoolean(self::$SCHEMA_DEFAULT_BOOLEAN, $this->defaultBoolean, 1);
        }
        if (isset($this->defaultList)) {
            $serializer->serializeList(self::$SCHEMA_DEFAULT_LIST, $this->defaultList, 2);
        }
        if (isset($this->defaultTimestamp)) {
            $serializer->serializeTimestamp(self::$SCHEMA_DEFAULT_TIMESTAMP, $this->defaultTimestamp, 3);
        }
        if (isset($this->defaultBlob)) {
            $serializer->serializeBlob(self::$SCHEMA_DEFAULT_BLOB, $this->defaultBlob, 4);
        }
        if (isset($this->defaultByte)) {
            $serializer->serializeInteger(self::$SCHEMA_DEFAULT_BYTE, $this->defaultByte, 5);
        }
        if (isset($this->defaultShort)) {
            $serializer->serializeInteger(self::$SCHEMA_DEFAULT_SHORT, $this->defaultShort, 6);
        }
        if (isset($this->defaultInteger)) {
            $serializer->serializeInteger(self::$SCHEMA_DEFAULT_INTEGER, $this->defaultInteger, 7);
        }
        if (isset($this->defaultLong)) {
            $serializer->serializeInteger(self::$SCHEMA_DEFAULT_LONG, $this->defaultLong, 8);
        }
        if (isset($this->defaultFloat)) {
            $serializer->serializeFloat(self::$SCHEMA_DEFAULT_FLOAT, $this->defaultFloat, 9);
        }
        if (isset($this->defaultDouble)) {
            $serializer->serializeFloat(self::$SCHEMA_DEFAULT_DOUBLE, $this->defaultDouble, 10);
        }
        if (isset($this->defaultMap)) {
            $serializer->serializeMap(self::$SCHEMA_DEFAULT_MAP, $this->defaultMap, 11);
        }
        if (isset($this->defaultEnum)) {
            $serializer->serializeString(self::$SCHEMA_DEFAULT_ENUM, $this->defaultEnum->value, 12);
        }
        if (isset($this->defaultIntEnum)) {
            $serializer->serializeInteger(self::$SCHEMA_DEFAULT_INT_ENUM, $this->defaultIntEnum->value, 13);
        }
        if (isset($this->emptyString)) {
            $serializer->serializeString(self::$SCHEMA_EMPTY_STRING, $this->emptyString, 14);
        }
        if (isset($this->falseBoolean)) {
            $serializer->serializeBoolean(self::$SCHEMA_FALSE_BOOLEAN, $this->falseBoolean, 15);
        }
        if (isset($this->emptyBlob)) {
            $serializer->serializeBlob(self::$SCHEMA_EMPTY_BLOB, $this->emptyBlob, 16);
        }
        if (isset($this->zeroByte)) {
            $serializer->serializeInteger(self::$SCHEMA_ZERO_BYTE, $this->zeroByte, 17);
        }
        if (isset($this->zeroShort)) {
            $serializer->serializeInteger(self::$SCHEMA_ZERO_SHORT, $this->zeroShort, 18);
        }
        if (isset($this->zeroInteger)) {
            $serializer->serializeInteger(self::$SCHEMA_ZERO_INTEGER, $this->zeroInteger, 19);
        }
        if (isset($this->zeroLong)) {
            $serializer->serializeInteger(self::$SCHEMA_ZERO_LONG, $this->zeroLong, 20);
        }
        if (isset($this->zeroFloat)) {
            $serializer->serializeFloat(self::$SCHEMA_ZERO_FLOAT, $this->zeroFloat, 21);
        }
        if (isset($this->zeroDouble)) {
            $serializer->serializeFloat(self::$SCHEMA_ZERO_DOUBLE, $this->zeroDouble, 22);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->defaultString,
            1 => $this->defaultBoolean,
            2 => $this->defaultList,
            3 => $this->defaultTimestamp,
            4 => $this->defaultBlob,
            5 => $this->defaultByte,
            6 => $this->defaultShort,
            7 => $this->defaultInteger,
            8 => $this->defaultLong,
            9 => $this->defaultFloat,
            10 => $this->defaultDouble,
            11 => $this->defaultMap,
            12 => $this->defaultEnum,
            13 => $this->defaultIntEnum,
            14 => $this->emptyString,
            15 => $this->falseBoolean,
            16 => $this->emptyBlob,
            17 => $this->zeroByte,
            18 => $this->zeroShort,
            19 => $this->zeroInteger,
            20 => $this->zeroLong,
            21 => $this->zeroFloat,
            22 => $this->zeroDouble,
            default => null,
        };
    }

}
