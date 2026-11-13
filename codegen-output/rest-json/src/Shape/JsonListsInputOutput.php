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
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RestJson\Schemas;

final class JsonListsInputOutput implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.restjson#JsonListsInputOutput';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_STRING_LIST;
    private static Schema $SCHEMA_STRING_SET;
    private static Schema $SCHEMA_INTEGER_LIST;
    private static Schema $SCHEMA_BOOLEAN_LIST;
    private static Schema $SCHEMA_TIMESTAMP_LIST;
    private static Schema $SCHEMA_ENUM_LIST;
    private static Schema $SCHEMA_INT_ENUM_LIST;
    private static Schema $SCHEMA_NESTED_STRING_LIST;
    private static Schema $SCHEMA_STRUCTURE_LIST;

    /**
     * @param list<string>|null $stringList
     * @param list<string>|null $stringSet
     * @param list<int>|null $integerList
     * @param list<bool>|null $booleanList
     * @param list<float>|null $timestampList
     * @param list<\Smithy\ProtocolTests\RestJson\Shape\FooEnum>|null $enumList
     * @param list<\Smithy\ProtocolTests\RestJson\Shape\IntegerEnum>|null $intEnumList
     * @param list<list<string>>|null $nestedStringList
     * @param list<\Smithy\ProtocolTests\RestJson\Shape\StructureListMember>|null $structureList
     */
    public function __construct(
        public readonly ?array $stringList = null,
        public readonly ?array $stringSet = null,
        public readonly ?array $integerList = null,
        public readonly ?array $booleanList = null,
        public readonly ?array $timestampList = null,
        public readonly ?array $enumList = null,
        public readonly ?array $intEnumList = null,
        public readonly ?array $nestedStringList = null,
        public readonly ?array $structureList = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::jsonListsInputOutput();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_STRING_LIST = $m[0];
        self::$SCHEMA_STRING_SET = $m[1];
        self::$SCHEMA_INTEGER_LIST = $m[2];
        self::$SCHEMA_BOOLEAN_LIST = $m[3];
        self::$SCHEMA_TIMESTAMP_LIST = $m[4];
        self::$SCHEMA_ENUM_LIST = $m[5];
        self::$SCHEMA_INT_ENUM_LIST = $m[6];
        self::$SCHEMA_NESTED_STRING_LIST = $m[7];
        self::$SCHEMA_STRUCTURE_LIST = $m[8];
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
        if (isset($this->stringList)) {
            $serializer->serializeList(self::$SCHEMA_STRING_LIST, $this->stringList, 0);
        }
        if (isset($this->stringSet)) {
            $serializer->serializeList(self::$SCHEMA_STRING_SET, $this->stringSet, 1);
        }
        if (isset($this->integerList)) {
            $serializer->serializeList(self::$SCHEMA_INTEGER_LIST, $this->integerList, 2);
        }
        if (isset($this->booleanList)) {
            $serializer->serializeList(self::$SCHEMA_BOOLEAN_LIST, $this->booleanList, 3);
        }
        if (isset($this->timestampList)) {
            $serializer->serializeList(self::$SCHEMA_TIMESTAMP_LIST, $this->timestampList, 4);
        }
        if (isset($this->enumList)) {
            $serializer->serializeList(self::$SCHEMA_ENUM_LIST, $this->enumList, 5);
        }
        if (isset($this->intEnumList)) {
            $serializer->serializeList(self::$SCHEMA_INT_ENUM_LIST, $this->intEnumList, 6);
        }
        if (isset($this->nestedStringList)) {
            $serializer->serializeList(self::$SCHEMA_NESTED_STRING_LIST, $this->nestedStringList, 7);
        }
        if (isset($this->structureList)) {
            $serializer->serializeList(self::$SCHEMA_STRUCTURE_LIST, $this->structureList, 8);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->stringList,
            1 => $this->stringSet,
            2 => $this->integerList,
            3 => $this->booleanList,
            4 => $this->timestampList,
            5 => $this->enumList,
            6 => $this->intEnumList,
            7 => $this->nestedStringList,
            8 => $this->structureList,
            default => null,
        };
    }

}
