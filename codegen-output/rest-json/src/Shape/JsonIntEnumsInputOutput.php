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

final class JsonIntEnumsInputOutput implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.restjson#JsonIntEnumsInputOutput';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_INTEGER_ENUM1;
    private static Schema $SCHEMA_INTEGER_ENUM2;
    private static Schema $SCHEMA_INTEGER_ENUM3;
    private static Schema $SCHEMA_INTEGER_ENUM_LIST;
    private static Schema $SCHEMA_INTEGER_ENUM_SET;
    private static Schema $SCHEMA_INTEGER_ENUM_MAP;

    public readonly ?IntegerEnum $integerEnum1;
    public readonly ?IntegerEnum $integerEnum2;
    public readonly ?IntegerEnum $integerEnum3;

    /**
     * @param list<\Smithy\ProtocolTests\RestJson\Shape\IntegerEnum>|null $integerEnumList
     * @param list<\Smithy\ProtocolTests\RestJson\Shape\IntegerEnum>|null $integerEnumSet
     * @param array<string, \Smithy\ProtocolTests\RestJson\Shape\IntegerEnum>|null $integerEnumMap
     */
    public function __construct(
        IntegerEnum|int|null $integerEnum1 = null,
        IntegerEnum|int|null $integerEnum2 = null,
        IntegerEnum|int|null $integerEnum3 = null,
        public readonly ?array $integerEnumList = null,
        public readonly ?array $integerEnumSet = null,
        public readonly ?array $integerEnumMap = null
    ) {
        if ($integerEnum1 !== null) {
            $this->integerEnum1 = $integerEnum1 instanceof IntegerEnum ? $integerEnum1 : IntegerEnum::fromValue($integerEnum1);
        }
        if ($integerEnum2 !== null) {
            $this->integerEnum2 = $integerEnum2 instanceof IntegerEnum ? $integerEnum2 : IntegerEnum::fromValue($integerEnum2);
        }
        if ($integerEnum3 !== null) {
            $this->integerEnum3 = $integerEnum3 instanceof IntegerEnum ? $integerEnum3 : IntegerEnum::fromValue($integerEnum3);
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
        $s = Schemas::jsonIntEnumsInputOutput();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_INTEGER_ENUM1 = $m[0];
        self::$SCHEMA_INTEGER_ENUM2 = $m[1];
        self::$SCHEMA_INTEGER_ENUM3 = $m[2];
        self::$SCHEMA_INTEGER_ENUM_LIST = $m[3];
        self::$SCHEMA_INTEGER_ENUM_SET = $m[4];
        self::$SCHEMA_INTEGER_ENUM_MAP = $m[5];
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
        if (isset($this->integerEnum1)) {
            $serializer->serializeInteger(self::$SCHEMA_INTEGER_ENUM1, $this->integerEnum1->value, 0);
        }
        if (isset($this->integerEnum2)) {
            $serializer->serializeInteger(self::$SCHEMA_INTEGER_ENUM2, $this->integerEnum2->value, 1);
        }
        if (isset($this->integerEnum3)) {
            $serializer->serializeInteger(self::$SCHEMA_INTEGER_ENUM3, $this->integerEnum3->value, 2);
        }
        if (isset($this->integerEnumList)) {
            $serializer->serializeList(self::$SCHEMA_INTEGER_ENUM_LIST, $this->integerEnumList, 3);
        }
        if (isset($this->integerEnumSet)) {
            $serializer->serializeList(self::$SCHEMA_INTEGER_ENUM_SET, $this->integerEnumSet, 4);
        }
        if (isset($this->integerEnumMap)) {
            $serializer->serializeMap(self::$SCHEMA_INTEGER_ENUM_MAP, $this->integerEnumMap, 5);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->integerEnum1 ?? null,
            1 => $this->integerEnum2 ?? null,
            2 => $this->integerEnum3 ?? null,
            3 => $this->integerEnumList,
            4 => $this->integerEnumSet,
            5 => $this->integerEnumMap,
            default => null,
        };
    }

    /**
     * Return a copy with one member replaced.
     */
    public function withMemberValue(Schema $member, mixed $value): static
    {
        $members = [
            $this->integerEnum1 ?? null,
            $this->integerEnum2 ?? null,
            $this->integerEnum3 ?? null,
            $this->integerEnumList,
            $this->integerEnumSet,
            $this->integerEnumMap
        ];
        $members[$member->memberIndex] = $value;

        return new self(...$members);
    }

}
