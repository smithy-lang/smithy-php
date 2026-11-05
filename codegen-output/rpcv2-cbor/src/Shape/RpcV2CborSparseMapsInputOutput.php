<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RpcV2Cbor\Shape;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RpcV2Cbor\Schemas;

final class RpcV2CborSparseMapsInputOutput implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'smithy.protocoltests.rpcv2Cbor#RpcV2CborSparseMapsInputOutput';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_SPARSE_STRUCT_MAP;
    private static Schema $SCHEMA_SPARSE_NUMBER_MAP;
    private static Schema $SCHEMA_SPARSE_BOOLEAN_MAP;
    private static Schema $SCHEMA_SPARSE_STRING_MAP;
    private static Schema $SCHEMA_SPARSE_SET_MAP;

    /**
     * @param array<string, \Smithy\ProtocolTests\RpcV2Cbor\Shape\GreetingStruct|null>|null $sparseStructMap
     * @param array<string, int|null>|null $sparseNumberMap
     * @param array<string, bool|null>|null $sparseBooleanMap
     * @param array<string, string|null>|null $sparseStringMap
     * @param array<string, list<string>|null>|null $sparseSetMap
     */
    public function __construct(
        public readonly ?array $sparseStructMap = null,
        public readonly ?array $sparseNumberMap = null,
        public readonly ?array $sparseBooleanMap = null,
        public readonly ?array $sparseStringMap = null,
        public readonly ?array $sparseSetMap = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::rpcV2CborSparseMapsInputOutput();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_SPARSE_STRUCT_MAP = $m[0];
        self::$SCHEMA_SPARSE_NUMBER_MAP = $m[1];
        self::$SCHEMA_SPARSE_BOOLEAN_MAP = $m[2];
        self::$SCHEMA_SPARSE_STRING_MAP = $m[3];
        self::$SCHEMA_SPARSE_SET_MAP = $m[4];
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
        if (isset($this->sparseStructMap)) {
            $serializer->serializeMap(self::$SCHEMA_SPARSE_STRUCT_MAP, $this->sparseStructMap, 0);
        }
        if (isset($this->sparseNumberMap)) {
            $serializer->serializeMap(self::$SCHEMA_SPARSE_NUMBER_MAP, $this->sparseNumberMap, 1);
        }
        if (isset($this->sparseBooleanMap)) {
            $serializer->serializeMap(self::$SCHEMA_SPARSE_BOOLEAN_MAP, $this->sparseBooleanMap, 2);
        }
        if (isset($this->sparseStringMap)) {
            $serializer->serializeMap(self::$SCHEMA_SPARSE_STRING_MAP, $this->sparseStringMap, 3);
        }
        if (isset($this->sparseSetMap)) {
            $serializer->serializeMap(self::$SCHEMA_SPARSE_SET_MAP, $this->sparseSetMap, 4);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->sparseStructMap,
            1 => $this->sparseNumberMap,
            2 => $this->sparseBooleanMap,
            3 => $this->sparseStringMap,
            4 => $this->sparseSetMap,
            default => null,
        };
    }

}
