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

final class JsonIntEnumsInputOutput implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.json#JsonIntEnumsInputOutput';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_INT_ENUM1;
    private static Schema $SCHEMA_INT_ENUM2;
    private static Schema $SCHEMA_INT_ENUM3;
    private static Schema $SCHEMA_INT_ENUM_LIST;
    private static Schema $SCHEMA_INT_ENUM_SET;
    private static Schema $SCHEMA_INT_ENUM_MAP;

    public readonly ?IntegerEnum $intEnum1;
    public readonly ?IntegerEnum $intEnum2;
    public readonly ?IntegerEnum $intEnum3;

    /**
     * @param list<\Smithy\ProtocolTests\AwsJson1_1\Shape\IntegerEnum>|null $intEnumList
     * @param list<\Smithy\ProtocolTests\AwsJson1_1\Shape\IntegerEnum>|null $intEnumSet
     * @param array<string, \Smithy\ProtocolTests\AwsJson1_1\Shape\IntegerEnum>|null $intEnumMap
     */
    public function __construct(
        IntegerEnum|int|null $intEnum1 = null,
        IntegerEnum|int|null $intEnum2 = null,
        IntegerEnum|int|null $intEnum3 = null,
        public readonly ?array $intEnumList = null,
        public readonly ?array $intEnumSet = null,
        public readonly ?array $intEnumMap = null
    ) {
        if ($intEnum1 !== null) {
            $this->intEnum1 = $intEnum1 instanceof IntegerEnum ? $intEnum1 : IntegerEnum::fromValue($intEnum1);
        }
        if ($intEnum2 !== null) {
            $this->intEnum2 = $intEnum2 instanceof IntegerEnum ? $intEnum2 : IntegerEnum::fromValue($intEnum2);
        }
        if ($intEnum3 !== null) {
            $this->intEnum3 = $intEnum3 instanceof IntegerEnum ? $intEnum3 : IntegerEnum::fromValue($intEnum3);
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
        self::$SCHEMA_INT_ENUM1 = $m[0];
        self::$SCHEMA_INT_ENUM2 = $m[1];
        self::$SCHEMA_INT_ENUM3 = $m[2];
        self::$SCHEMA_INT_ENUM_LIST = $m[3];
        self::$SCHEMA_INT_ENUM_SET = $m[4];
        self::$SCHEMA_INT_ENUM_MAP = $m[5];
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
        if (isset($this->intEnum1)) {
            $serializer->serializeInteger(self::$SCHEMA_INT_ENUM1, $this->intEnum1->value, 0);
        }
        if (isset($this->intEnum2)) {
            $serializer->serializeInteger(self::$SCHEMA_INT_ENUM2, $this->intEnum2->value, 1);
        }
        if (isset($this->intEnum3)) {
            $serializer->serializeInteger(self::$SCHEMA_INT_ENUM3, $this->intEnum3->value, 2);
        }
        if (isset($this->intEnumList)) {
            $serializer->serializeList(self::$SCHEMA_INT_ENUM_LIST, $this->intEnumList, 3);
        }
        if (isset($this->intEnumSet)) {
            $serializer->serializeList(self::$SCHEMA_INT_ENUM_SET, $this->intEnumSet, 4);
        }
        if (isset($this->intEnumMap)) {
            $serializer->serializeMap(self::$SCHEMA_INT_ENUM_MAP, $this->intEnumMap, 5);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->intEnum1 ?? null,
            1 => $this->intEnum2 ?? null,
            2 => $this->intEnum3 ?? null,
            3 => $this->intEnumList,
            4 => $this->intEnumSet,
            5 => $this->intEnumMap,
            default => null,
        };
    }

    /**
     * Return a copy with one member replaced.
     */
    public function withMemberValue(Schema $member, mixed $value): static
    {
        $members = [
            $this->intEnum1 ?? null,
            $this->intEnum2 ?? null,
            $this->intEnum3 ?? null,
            $this->intEnumList,
            $this->intEnumSet,
            $this->intEnumMap
        ];
        $members[$member->memberIndex] = $value;

        return new self(...$members);
    }

}
