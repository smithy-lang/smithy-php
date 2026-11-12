<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestXml\Input;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RestXml\Schemas;

final class NestedXmlMapWithXmlNameRequest implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.restxml#NestedXmlMapWithXmlNameRequest';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_NESTED_XML_MAP_WITH_XML_NAME_MAP;

    /**
     * @param array<string, array<string, string>>|null $nestedXmlMapWithXmlNameMap
     */
    public function __construct(
        public readonly ?array $nestedXmlMapWithXmlNameMap = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::nestedXmlMapWithXmlNameRequest();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_NESTED_XML_MAP_WITH_XML_NAME_MAP = $m[0];
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
        if (isset($this->nestedXmlMapWithXmlNameMap)) {
            $serializer->serializeMap(self::$SCHEMA_NESTED_XML_MAP_WITH_XML_NAME_MAP, $this->nestedXmlMapWithXmlNameMap, 0);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->nestedXmlMapWithXmlNameMap,
            default => null,
        };
    }

}
