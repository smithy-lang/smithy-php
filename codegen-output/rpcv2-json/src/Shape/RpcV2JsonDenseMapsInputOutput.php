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

final class RpcV2JsonDenseMapsInputOutput implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'smithy.protocoltests.rpcv2Json#RpcV2JsonDenseMapsInputOutput';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_DENSE_STRUCT_MAP;
    private static Schema $SCHEMA_DENSE_NUMBER_MAP;
    private static Schema $SCHEMA_DENSE_BOOLEAN_MAP;
    private static Schema $SCHEMA_DENSE_STRING_MAP;
    private static Schema $SCHEMA_DENSE_SET_MAP;

    /**
     * @param array<string, \Smithy\ProtocolTests\RpcV2Json\Shape\GreetingStruct>|null $denseStructMap
     * @param array<string, int>|null $denseNumberMap
     * @param array<string, bool>|null $denseBooleanMap
     * @param array<string, string>|null $denseStringMap
     * @param array<string, list<string>>|null $denseSetMap
     */
    public function __construct(
        public readonly ?array $denseStructMap = null,
        public readonly ?array $denseNumberMap = null,
        public readonly ?array $denseBooleanMap = null,
        public readonly ?array $denseStringMap = null,
        public readonly ?array $denseSetMap = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::rpcV2JsonDenseMapsInputOutput();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_DENSE_STRUCT_MAP = $m[0];
        self::$SCHEMA_DENSE_NUMBER_MAP = $m[1];
        self::$SCHEMA_DENSE_BOOLEAN_MAP = $m[2];
        self::$SCHEMA_DENSE_STRING_MAP = $m[3];
        self::$SCHEMA_DENSE_SET_MAP = $m[4];
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
        if (isset($this->denseStructMap)) {
            $serializer->serializeMap(self::$SCHEMA_DENSE_STRUCT_MAP, $this->denseStructMap, 0);
        }
        if (isset($this->denseNumberMap)) {
            $serializer->serializeMap(self::$SCHEMA_DENSE_NUMBER_MAP, $this->denseNumberMap, 1);
        }
        if (isset($this->denseBooleanMap)) {
            $serializer->serializeMap(self::$SCHEMA_DENSE_BOOLEAN_MAP, $this->denseBooleanMap, 2);
        }
        if (isset($this->denseStringMap)) {
            $serializer->serializeMap(self::$SCHEMA_DENSE_STRING_MAP, $this->denseStringMap, 3);
        }
        if (isset($this->denseSetMap)) {
            $serializer->serializeMap(self::$SCHEMA_DENSE_SET_MAP, $this->denseSetMap, 4);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->denseStructMap,
            1 => $this->denseNumberMap,
            2 => $this->denseBooleanMap,
            3 => $this->denseStringMap,
            4 => $this->denseSetMap,
            default => null,
        };
    }

}
