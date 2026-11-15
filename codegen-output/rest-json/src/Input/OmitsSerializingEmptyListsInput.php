<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestJson\Input;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\ProtocolTests\RestJson\Schemas;

final class OmitsSerializingEmptyListsInput implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.restjson#OmitsSerializingEmptyListsInput';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_QUERY_STRING_LIST;
    private static Schema $SCHEMA_QUERY_INTEGER_LIST;
    private static Schema $SCHEMA_QUERY_DOUBLE_LIST;
    private static Schema $SCHEMA_QUERY_BOOLEAN_LIST;
    private static Schema $SCHEMA_QUERY_TIMESTAMP_LIST;
    private static Schema $SCHEMA_QUERY_ENUM_LIST;
    private static Schema $SCHEMA_QUERY_INTEGER_ENUM_LIST;

    /**
     * @param list<string>|null $queryStringList
     * @param list<int>|null $queryIntegerList
     * @param list<float>|null $queryDoubleList
     * @param list<bool>|null $queryBooleanList
     * @param list<float>|null $queryTimestampList
     * @param list<\Smithy\ProtocolTests\RestJson\Shape\FooEnum>|null $queryEnumList
     * @param list<\Smithy\ProtocolTests\RestJson\Shape\IntegerEnum>|null $queryIntegerEnumList
     */
    public function __construct(
        public readonly ?array $queryStringList = null,
        public readonly ?array $queryIntegerList = null,
        public readonly ?array $queryDoubleList = null,
        public readonly ?array $queryBooleanList = null,
        public readonly ?array $queryTimestampList = null,
        public readonly ?array $queryEnumList = null,
        public readonly ?array $queryIntegerEnumList = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::omitsSerializingEmptyListsInput();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_QUERY_STRING_LIST = $m[0];
        self::$SCHEMA_QUERY_INTEGER_LIST = $m[1];
        self::$SCHEMA_QUERY_DOUBLE_LIST = $m[2];
        self::$SCHEMA_QUERY_BOOLEAN_LIST = $m[3];
        self::$SCHEMA_QUERY_TIMESTAMP_LIST = $m[4];
        self::$SCHEMA_QUERY_ENUM_LIST = $m[5];
        self::$SCHEMA_QUERY_INTEGER_ENUM_LIST = $m[6];
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
        if (isset($this->queryStringList)) {
            $serializer->serializeList(self::$SCHEMA_QUERY_STRING_LIST, $this->queryStringList, 0);
        }
        if (isset($this->queryIntegerList)) {
            $serializer->serializeList(self::$SCHEMA_QUERY_INTEGER_LIST, $this->queryIntegerList, 1);
        }
        if (isset($this->queryDoubleList)) {
            $serializer->serializeList(self::$SCHEMA_QUERY_DOUBLE_LIST, $this->queryDoubleList, 2);
        }
        if (isset($this->queryBooleanList)) {
            $serializer->serializeList(self::$SCHEMA_QUERY_BOOLEAN_LIST, $this->queryBooleanList, 3);
        }
        if (isset($this->queryTimestampList)) {
            $serializer->serializeList(self::$SCHEMA_QUERY_TIMESTAMP_LIST, $this->queryTimestampList, 4);
        }
        if (isset($this->queryEnumList)) {
            $serializer->serializeList(self::$SCHEMA_QUERY_ENUM_LIST, $this->queryEnumList, 5);
        }
        if (isset($this->queryIntegerEnumList)) {
            $serializer->serializeList(self::$SCHEMA_QUERY_INTEGER_ENUM_LIST, $this->queryIntegerEnumList, 6);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->queryStringList,
            1 => $this->queryIntegerList,
            2 => $this->queryDoubleList,
            3 => $this->queryBooleanList,
            4 => $this->queryTimestampList,
            5 => $this->queryEnumList,
            6 => $this->queryIntegerEnumList,
            default => null,
        };
    }

    /**
     * Return a copy with one member replaced.
     */
    public function withMemberValue(Schema $member, mixed $value): static
    {
        $members = [
            $this->queryStringList,
            $this->queryIntegerList,
            $this->queryDoubleList,
            $this->queryBooleanList,
            $this->queryTimestampList,
            $this->queryEnumList,
            $this->queryIntegerEnumList
        ];
        $members[$member->memberIndex] = $value;

        return new self(...$members);
    }

}
