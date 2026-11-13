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
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\AwsJson1_1\Schemas;

final class JsonEnumsInputOutput implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.json#JsonEnumsInputOutput';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_FOO_ENUM1;
    private static Schema $SCHEMA_FOO_ENUM2;
    private static Schema $SCHEMA_FOO_ENUM3;
    private static Schema $SCHEMA_FOO_ENUM_LIST;
    private static Schema $SCHEMA_FOO_ENUM_SET;
    private static Schema $SCHEMA_FOO_ENUM_MAP;

    public readonly ?FooEnum $fooEnum1;
    public readonly ?FooEnum $fooEnum2;
    public readonly ?FooEnum $fooEnum3;

    /**
     * @param list<\Smithy\ProtocolTests\AwsJson1_1\Shape\FooEnum>|null $fooEnumList
     * @param list<\Smithy\ProtocolTests\AwsJson1_1\Shape\FooEnum>|null $fooEnumSet
     * @param array<string, \Smithy\ProtocolTests\AwsJson1_1\Shape\FooEnum>|null $fooEnumMap
     */
    public function __construct(
        FooEnum|string|null $fooEnum1 = null,
        FooEnum|string|null $fooEnum2 = null,
        FooEnum|string|null $fooEnum3 = null,
        public readonly ?array $fooEnumList = null,
        public readonly ?array $fooEnumSet = null,
        public readonly ?array $fooEnumMap = null
    ) {
        if ($fooEnum1 !== null) {
            $this->fooEnum1 = $fooEnum1 instanceof FooEnum ? $fooEnum1 : FooEnum::fromValue($fooEnum1);
        }
        if ($fooEnum2 !== null) {
            $this->fooEnum2 = $fooEnum2 instanceof FooEnum ? $fooEnum2 : FooEnum::fromValue($fooEnum2);
        }
        if ($fooEnum3 !== null) {
            $this->fooEnum3 = $fooEnum3 instanceof FooEnum ? $fooEnum3 : FooEnum::fromValue($fooEnum3);
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
        $s = Schemas::jsonEnumsInputOutput();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_FOO_ENUM1 = $m[0];
        self::$SCHEMA_FOO_ENUM2 = $m[1];
        self::$SCHEMA_FOO_ENUM3 = $m[2];
        self::$SCHEMA_FOO_ENUM_LIST = $m[3];
        self::$SCHEMA_FOO_ENUM_SET = $m[4];
        self::$SCHEMA_FOO_ENUM_MAP = $m[5];
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
        if (isset($this->fooEnum1)) {
            $serializer->serializeString(self::$SCHEMA_FOO_ENUM1, $this->fooEnum1->value, 0);
        }
        if (isset($this->fooEnum2)) {
            $serializer->serializeString(self::$SCHEMA_FOO_ENUM2, $this->fooEnum2->value, 1);
        }
        if (isset($this->fooEnum3)) {
            $serializer->serializeString(self::$SCHEMA_FOO_ENUM3, $this->fooEnum3->value, 2);
        }
        if (isset($this->fooEnumList)) {
            $serializer->serializeList(self::$SCHEMA_FOO_ENUM_LIST, $this->fooEnumList, 3);
        }
        if (isset($this->fooEnumSet)) {
            $serializer->serializeList(self::$SCHEMA_FOO_ENUM_SET, $this->fooEnumSet, 4);
        }
        if (isset($this->fooEnumMap)) {
            $serializer->serializeMap(self::$SCHEMA_FOO_ENUM_MAP, $this->fooEnumMap, 5);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->fooEnum1 ?? null,
            1 => $this->fooEnum2 ?? null,
            2 => $this->fooEnum3 ?? null,
            3 => $this->fooEnumList,
            4 => $this->fooEnumSet,
            5 => $this->fooEnumMap,
            default => null,
        };
    }

}
