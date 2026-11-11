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

final class ConstantAndVariableQueryStringInput implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.restxml#ConstantAndVariableQueryStringInput';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_BAZ;
    private static Schema $SCHEMA_MAYBE_SET;

    public function __construct(
        public readonly ?string $baz = null,
        public readonly ?string $maybeSet = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::constantAndVariableQueryStringInput();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_BAZ = $m[0];
        self::$SCHEMA_MAYBE_SET = $m[1];
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
        if (isset($this->baz)) {
            $serializer->serializeString(self::$SCHEMA_BAZ, $this->baz, 0);
        }
        if (isset($this->maybeSet)) {
            $serializer->serializeString(self::$SCHEMA_MAYBE_SET, $this->maybeSet, 1);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->baz,
            1 => $this->maybeSet,
            default => null,
        };
    }

}
