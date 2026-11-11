<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\AwsJson1_1\Shape;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\AwsJson1_1\Schemas;

final class SimpleScalarPropertiesInputOutput implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.json#SimpleScalarPropertiesInputOutput';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_FLOAT_VALUE;
    private static Schema $SCHEMA_DOUBLE_VALUE;

    public function __construct(
        public readonly ?float $floatValue = null,
        public readonly ?float $doubleValue = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::simpleScalarPropertiesInputOutput();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_FLOAT_VALUE = $m[0];
        self::$SCHEMA_DOUBLE_VALUE = $m[1];
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
        if (isset($this->floatValue)) {
            $serializer->serializeFloat(self::$SCHEMA_FLOAT_VALUE, $this->floatValue, 0);
        }
        if (isset($this->doubleValue)) {
            $serializer->serializeFloat(self::$SCHEMA_DOUBLE_VALUE, $this->doubleValue, 1);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->floatValue,
            1 => $this->doubleValue,
            default => null,
        };
    }

}
