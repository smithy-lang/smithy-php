<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestXml\Shape;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RestXml\Schemas;

final class XmlNamespaceNested implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.restxml#XmlNamespaceNested';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_FOO;
    private static Schema $SCHEMA_VALUES;

    /**
     * @param list<string>|null $values
     */
    public function __construct(
        public readonly ?string $foo = null,
        public readonly ?array $values = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::xmlNamespaceNested();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_FOO = $m[0];
        self::$SCHEMA_VALUES = $m[1];
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
        if (isset($this->foo)) {
            $serializer->serializeString(self::$SCHEMA_FOO, $this->foo, 0);
        }
        if (isset($this->values)) {
            $serializer->serializeList(self::$SCHEMA_VALUES, $this->values, 1);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->foo,
            1 => $this->values,
            default => null,
        };
    }

}
