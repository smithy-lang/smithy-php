<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestXml\Output;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RestXml\Schemas;

final class NestedXmlMapsResponse implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.restxml#NestedXmlMapsResponse';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_NESTED_MAP;
    private static Schema $SCHEMA_FLAT_NESTED_MAP;

    /**
     * @param array<string, array<string, \Smithy\ProtocolTests\RestXml\Shape\FooEnum>>|null $nestedMap
     * @param array<string, array<string, \Smithy\ProtocolTests\RestXml\Shape\FooEnum>>|null $flatNestedMap
     */
    public function __construct(
        public readonly ?array $nestedMap = null,
        public readonly ?array $flatNestedMap = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::nestedXmlMapsResponse();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_NESTED_MAP = $m[0];
        self::$SCHEMA_FLAT_NESTED_MAP = $m[1];
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
        if (isset($this->nestedMap)) {
            $serializer->serializeMap(self::$SCHEMA_NESTED_MAP, $this->nestedMap, 0);
        }
        if (isset($this->flatNestedMap)) {
            $serializer->serializeMap(self::$SCHEMA_FLAT_NESTED_MAP, $this->flatNestedMap, 1);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->nestedMap,
            1 => $this->flatNestedMap,
            default => null,
        };
    }

}
