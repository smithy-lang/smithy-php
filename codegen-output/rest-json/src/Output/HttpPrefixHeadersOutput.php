<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestJson\Output;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RestJson\Schemas;

final class HttpPrefixHeadersOutput implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.restjson#HttpPrefixHeadersOutput';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_FOO;
    private static Schema $SCHEMA_FOO_MAP;

    /**
     * @param array<string, string>|null $fooMap
     */
    public function __construct(
        public readonly ?string $foo = null,
        public readonly ?array $fooMap = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::httpPrefixHeadersOutput();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_FOO = $m[0];
        self::$SCHEMA_FOO_MAP = $m[1];
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
        if (isset($this->fooMap)) {
            $serializer->serializeMap(self::$SCHEMA_FOO_MAP, $this->fooMap, 1);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->foo,
            1 => $this->fooMap,
            default => null,
        };
    }

}
