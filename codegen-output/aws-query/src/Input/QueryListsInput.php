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
use Smithy\ProtocolTests\AwsQuery\Shape\NestedStructWithList;

final class QueryListsInput implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.query#QueryListsInput';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_LIST_ARG;
    private static Schema $SCHEMA_COMPLEX_LIST_ARG;
    private static Schema $SCHEMA_FLATTENED_LIST_ARG;
    private static Schema $SCHEMA_LIST_ARG_WITH_XML_NAME_MEMBER;
    private static Schema $SCHEMA_FLATTENED_LIST_ARG_WITH_XML_NAME;
    private static Schema $SCHEMA_NESTED_WITH_LIST;

    /**
     * @param list<string>|null $listArg
     * @param list<\Smithy\ProtocolTests\AwsQuery\Shape\GreetingStruct>|null $complexListArg
     * @param list<string>|null $flattenedListArg
     * @param list<string>|null $listArgWithXmlNameMember
     * @param list<string>|null $flattenedListArgWithXmlName
     */
    public function __construct(
        public readonly ?array $listArg = null,
        public readonly ?array $complexListArg = null,
        public readonly ?array $flattenedListArg = null,
        public readonly ?array $listArgWithXmlNameMember = null,
        public readonly ?array $flattenedListArgWithXmlName = null,
        public readonly ?NestedStructWithList $nestedWithList = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::queryListsInput();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_LIST_ARG = $m[0];
        self::$SCHEMA_COMPLEX_LIST_ARG = $m[1];
        self::$SCHEMA_FLATTENED_LIST_ARG = $m[2];
        self::$SCHEMA_LIST_ARG_WITH_XML_NAME_MEMBER = $m[3];
        self::$SCHEMA_FLATTENED_LIST_ARG_WITH_XML_NAME = $m[4];
        self::$SCHEMA_NESTED_WITH_LIST = $m[5];
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
        if (isset($this->listArg)) {
            $serializer->serializeList(self::$SCHEMA_LIST_ARG, $this->listArg, 0);
        }
        if (isset($this->complexListArg)) {
            $serializer->serializeList(self::$SCHEMA_COMPLEX_LIST_ARG, $this->complexListArg, 1);
        }
        if (isset($this->flattenedListArg)) {
            $serializer->serializeList(self::$SCHEMA_FLATTENED_LIST_ARG, $this->flattenedListArg, 2);
        }
        if (isset($this->listArgWithXmlNameMember)) {
            $serializer->serializeList(self::$SCHEMA_LIST_ARG_WITH_XML_NAME_MEMBER, $this->listArgWithXmlNameMember, 3);
        }
        if (isset($this->flattenedListArgWithXmlName)) {
            $serializer->serializeList(self::$SCHEMA_FLATTENED_LIST_ARG_WITH_XML_NAME, $this->flattenedListArgWithXmlName, 4);
        }
        if (isset($this->nestedWithList)) {
            $serializer->serializeStruct(self::$SCHEMA_NESTED_WITH_LIST, $this->nestedWithList, 5);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->listArg,
            1 => $this->complexListArg,
            2 => $this->flattenedListArg,
            3 => $this->listArgWithXmlNameMember,
            4 => $this->flattenedListArgWithXmlName,
            5 => $this->nestedWithList,
            default => null,
        };
    }

}
