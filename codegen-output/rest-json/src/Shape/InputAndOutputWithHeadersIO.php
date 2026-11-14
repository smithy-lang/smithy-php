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
use Smithy\ProtocolTests\RestJson\Schemas;

final class InputAndOutputWithHeadersIO implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.restjson#InputAndOutputWithHeadersIO';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_HEADER_STRING;
    private static Schema $SCHEMA_HEADER_BYTE;
    private static Schema $SCHEMA_HEADER_SHORT;
    private static Schema $SCHEMA_HEADER_INTEGER;
    private static Schema $SCHEMA_HEADER_LONG;
    private static Schema $SCHEMA_HEADER_FLOAT;
    private static Schema $SCHEMA_HEADER_DOUBLE;
    private static Schema $SCHEMA_HEADER_TRUE_BOOL;
    private static Schema $SCHEMA_HEADER_FALSE_BOOL;
    private static Schema $SCHEMA_HEADER_STRING_LIST;
    private static Schema $SCHEMA_HEADER_STRING_SET;
    private static Schema $SCHEMA_HEADER_INTEGER_LIST;
    private static Schema $SCHEMA_HEADER_BOOLEAN_LIST;
    private static Schema $SCHEMA_HEADER_TIMESTAMP_LIST;
    private static Schema $SCHEMA_HEADER_ENUM;
    private static Schema $SCHEMA_HEADER_ENUM_LIST;
    private static Schema $SCHEMA_HEADER_INTEGER_ENUM;
    private static Schema $SCHEMA_HEADER_INTEGER_ENUM_LIST;

    public readonly ?FooEnum $headerEnum;
    public readonly ?IntegerEnum $headerIntegerEnum;

    /**
     * @param list<string>|null $headerStringList
     * @param list<string>|null $headerStringSet
     * @param list<int>|null $headerIntegerList
     * @param list<bool>|null $headerBooleanList
     * @param list<float>|null $headerTimestampList
     * @param list<\Smithy\ProtocolTests\RestJson\Shape\FooEnum>|null $headerEnumList
     * @param list<\Smithy\ProtocolTests\RestJson\Shape\IntegerEnum>|null $headerIntegerEnumList
     */
    public function __construct(
        public readonly ?string $headerString = null,
        public readonly ?int $headerByte = null,
        public readonly ?int $headerShort = null,
        public readonly ?int $headerInteger = null,
        public readonly ?int $headerLong = null,
        public readonly ?float $headerFloat = null,
        public readonly ?float $headerDouble = null,
        public readonly ?bool $headerTrueBool = null,
        public readonly ?bool $headerFalseBool = null,
        public readonly ?array $headerStringList = null,
        public readonly ?array $headerStringSet = null,
        public readonly ?array $headerIntegerList = null,
        public readonly ?array $headerBooleanList = null,
        public readonly ?array $headerTimestampList = null,
        FooEnum|string|null $headerEnum = null,
        public readonly ?array $headerEnumList = null,
        IntegerEnum|int|null $headerIntegerEnum = null,
        public readonly ?array $headerIntegerEnumList = null
    ) {
        if ($headerEnum !== null) {
            $this->headerEnum = $headerEnum instanceof FooEnum ? $headerEnum : FooEnum::fromValue($headerEnum);
        }
        if ($headerIntegerEnum !== null) {
            $this->headerIntegerEnum = $headerIntegerEnum instanceof IntegerEnum ? $headerIntegerEnum : IntegerEnum::fromValue($headerIntegerEnum);
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
        $s = Schemas::inputAndOutputWithHeadersIO();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_HEADER_STRING = $m[0];
        self::$SCHEMA_HEADER_BYTE = $m[1];
        self::$SCHEMA_HEADER_SHORT = $m[2];
        self::$SCHEMA_HEADER_INTEGER = $m[3];
        self::$SCHEMA_HEADER_LONG = $m[4];
        self::$SCHEMA_HEADER_FLOAT = $m[5];
        self::$SCHEMA_HEADER_DOUBLE = $m[6];
        self::$SCHEMA_HEADER_TRUE_BOOL = $m[7];
        self::$SCHEMA_HEADER_FALSE_BOOL = $m[8];
        self::$SCHEMA_HEADER_STRING_LIST = $m[9];
        self::$SCHEMA_HEADER_STRING_SET = $m[10];
        self::$SCHEMA_HEADER_INTEGER_LIST = $m[11];
        self::$SCHEMA_HEADER_BOOLEAN_LIST = $m[12];
        self::$SCHEMA_HEADER_TIMESTAMP_LIST = $m[13];
        self::$SCHEMA_HEADER_ENUM = $m[14];
        self::$SCHEMA_HEADER_ENUM_LIST = $m[15];
        self::$SCHEMA_HEADER_INTEGER_ENUM = $m[16];
        self::$SCHEMA_HEADER_INTEGER_ENUM_LIST = $m[17];
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
        if (isset($this->headerString)) {
            $serializer->serializeString(self::$SCHEMA_HEADER_STRING, $this->headerString, 0);
        }
        if (isset($this->headerByte)) {
            $serializer->serializeInteger(self::$SCHEMA_HEADER_BYTE, $this->headerByte, 1);
        }
        if (isset($this->headerShort)) {
            $serializer->serializeInteger(self::$SCHEMA_HEADER_SHORT, $this->headerShort, 2);
        }
        if (isset($this->headerInteger)) {
            $serializer->serializeInteger(self::$SCHEMA_HEADER_INTEGER, $this->headerInteger, 3);
        }
        if (isset($this->headerLong)) {
            $serializer->serializeInteger(self::$SCHEMA_HEADER_LONG, $this->headerLong, 4);
        }
        if (isset($this->headerFloat)) {
            $serializer->serializeFloat(self::$SCHEMA_HEADER_FLOAT, $this->headerFloat, 5);
        }
        if (isset($this->headerDouble)) {
            $serializer->serializeFloat(self::$SCHEMA_HEADER_DOUBLE, $this->headerDouble, 6);
        }
        if (isset($this->headerTrueBool)) {
            $serializer->serializeBoolean(self::$SCHEMA_HEADER_TRUE_BOOL, $this->headerTrueBool, 7);
        }
        if (isset($this->headerFalseBool)) {
            $serializer->serializeBoolean(self::$SCHEMA_HEADER_FALSE_BOOL, $this->headerFalseBool, 8);
        }
        if (isset($this->headerStringList)) {
            $serializer->serializeList(self::$SCHEMA_HEADER_STRING_LIST, $this->headerStringList, 9);
        }
        if (isset($this->headerStringSet)) {
            $serializer->serializeList(self::$SCHEMA_HEADER_STRING_SET, $this->headerStringSet, 10);
        }
        if (isset($this->headerIntegerList)) {
            $serializer->serializeList(self::$SCHEMA_HEADER_INTEGER_LIST, $this->headerIntegerList, 11);
        }
        if (isset($this->headerBooleanList)) {
            $serializer->serializeList(self::$SCHEMA_HEADER_BOOLEAN_LIST, $this->headerBooleanList, 12);
        }
        if (isset($this->headerTimestampList)) {
            $serializer->serializeList(self::$SCHEMA_HEADER_TIMESTAMP_LIST, $this->headerTimestampList, 13);
        }
        if (isset($this->headerEnum)) {
            $serializer->serializeString(self::$SCHEMA_HEADER_ENUM, $this->headerEnum->value, 14);
        }
        if (isset($this->headerEnumList)) {
            $serializer->serializeList(self::$SCHEMA_HEADER_ENUM_LIST, $this->headerEnumList, 15);
        }
        if (isset($this->headerIntegerEnum)) {
            $serializer->serializeInteger(self::$SCHEMA_HEADER_INTEGER_ENUM, $this->headerIntegerEnum->value, 16);
        }
        if (isset($this->headerIntegerEnumList)) {
            $serializer->serializeList(self::$SCHEMA_HEADER_INTEGER_ENUM_LIST, $this->headerIntegerEnumList, 17);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->headerString,
            1 => $this->headerByte,
            2 => $this->headerShort,
            3 => $this->headerInteger,
            4 => $this->headerLong,
            5 => $this->headerFloat,
            6 => $this->headerDouble,
            7 => $this->headerTrueBool,
            8 => $this->headerFalseBool,
            9 => $this->headerStringList,
            10 => $this->headerStringSet,
            11 => $this->headerIntegerList,
            12 => $this->headerBooleanList,
            13 => $this->headerTimestampList,
            14 => $this->headerEnum ?? null,
            15 => $this->headerEnumList,
            16 => $this->headerIntegerEnum ?? null,
            17 => $this->headerIntegerEnumList,
            default => null,
        };
    }

    /**
     * Return a copy with one member replaced.
     */
    public function withMemberValue(Schema $member, mixed $value): static
    {
        $members = [
            $this->headerString,
            $this->headerByte,
            $this->headerShort,
            $this->headerInteger,
            $this->headerLong,
            $this->headerFloat,
            $this->headerDouble,
            $this->headerTrueBool,
            $this->headerFalseBool,
            $this->headerStringList,
            $this->headerStringSet,
            $this->headerIntegerList,
            $this->headerBooleanList,
            $this->headerTimestampList,
            $this->headerEnum ?? null,
            $this->headerEnumList,
            $this->headerIntegerEnum ?? null,
            $this->headerIntegerEnumList
        ];
        $members[$member->memberIndex] = $value;

        return new self(...$members);
    }

}
