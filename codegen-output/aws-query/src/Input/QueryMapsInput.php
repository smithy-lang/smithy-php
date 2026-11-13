<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\AwsQuery\Input;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\AwsQuery\Schemas;
use Smithy\ProtocolTests\AwsQuery\Shape\NestedStructWithMap;

final class QueryMapsInput implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.query#QueryMapsInput';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_MAP_ARG;
    private static Schema $SCHEMA_RENAMED_MAP_ARG;
    private static Schema $SCHEMA_COMPLEX_MAP_ARG;
    private static Schema $SCHEMA_MAP_WITH_XML_MEMBER_NAME;
    private static Schema $SCHEMA_FLATTENED_MAP;
    private static Schema $SCHEMA_FLATTENED_MAP_WITH_XML_NAME;
    private static Schema $SCHEMA_MAP_OF_LISTS;
    private static Schema $SCHEMA_NESTED_STRUCT_WITH_MAP;

    /**
     * @param array<string, string>|null $mapArg
     * @param array<string, string>|null $renamedMapArg
     * @param array<string, \Smithy\ProtocolTests\AwsQuery\Shape\GreetingStruct>|null $complexMapArg
     * @param array<string, string>|null $mapWithXmlMemberName
     * @param array<string, string>|null $flattenedMap
     * @param array<string, string>|null $flattenedMapWithXmlName
     * @param array<string, list<string>>|null $mapOfLists
     */
    public function __construct(
        public readonly ?array $mapArg = null,
        public readonly ?array $renamedMapArg = null,
        public readonly ?array $complexMapArg = null,
        public readonly ?array $mapWithXmlMemberName = null,
        public readonly ?array $flattenedMap = null,
        public readonly ?array $flattenedMapWithXmlName = null,
        public readonly ?array $mapOfLists = null,
        public readonly ?NestedStructWithMap $nestedStructWithMap = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::queryMapsInput();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_MAP_ARG = $m[0];
        self::$SCHEMA_RENAMED_MAP_ARG = $m[1];
        self::$SCHEMA_COMPLEX_MAP_ARG = $m[2];
        self::$SCHEMA_MAP_WITH_XML_MEMBER_NAME = $m[3];
        self::$SCHEMA_FLATTENED_MAP = $m[4];
        self::$SCHEMA_FLATTENED_MAP_WITH_XML_NAME = $m[5];
        self::$SCHEMA_MAP_OF_LISTS = $m[6];
        self::$SCHEMA_NESTED_STRUCT_WITH_MAP = $m[7];
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
        if (isset($this->mapArg)) {
            $serializer->serializeMap(self::$SCHEMA_MAP_ARG, $this->mapArg, 0);
        }
        if (isset($this->renamedMapArg)) {
            $serializer->serializeMap(self::$SCHEMA_RENAMED_MAP_ARG, $this->renamedMapArg, 1);
        }
        if (isset($this->complexMapArg)) {
            $serializer->serializeMap(self::$SCHEMA_COMPLEX_MAP_ARG, $this->complexMapArg, 2);
        }
        if (isset($this->mapWithXmlMemberName)) {
            $serializer->serializeMap(self::$SCHEMA_MAP_WITH_XML_MEMBER_NAME, $this->mapWithXmlMemberName, 3);
        }
        if (isset($this->flattenedMap)) {
            $serializer->serializeMap(self::$SCHEMA_FLATTENED_MAP, $this->flattenedMap, 4);
        }
        if (isset($this->flattenedMapWithXmlName)) {
            $serializer->serializeMap(self::$SCHEMA_FLATTENED_MAP_WITH_XML_NAME, $this->flattenedMapWithXmlName, 5);
        }
        if (isset($this->mapOfLists)) {
            $serializer->serializeMap(self::$SCHEMA_MAP_OF_LISTS, $this->mapOfLists, 6);
        }
        if (isset($this->nestedStructWithMap)) {
            $serializer->serializeStruct(self::$SCHEMA_NESTED_STRUCT_WITH_MAP, $this->nestedStructWithMap, 7);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->mapArg,
            1 => $this->renamedMapArg,
            2 => $this->complexMapArg,
            3 => $this->mapWithXmlMemberName,
            4 => $this->flattenedMap,
            5 => $this->flattenedMapWithXmlName,
            6 => $this->mapOfLists,
            7 => $this->nestedStructWithMap,
            default => null,
        };
    }

}
