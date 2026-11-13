<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestJson\Shape;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RestJson\Schemas;

final class SparseJsonListsInputOutput implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.restjson#SparseJsonListsInputOutput';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_SPARSE_STRING_LIST;
    private static Schema $SCHEMA_SPARSE_SHORT_LIST;

    /**
     * @param list<string|null>|null $sparseStringList
     * @param list<int|null>|null $sparseShortList
     */
    public function __construct(
        public readonly ?array $sparseStringList = null,
        public readonly ?array $sparseShortList = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::sparseJsonListsInputOutput();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_SPARSE_STRING_LIST = $m[0];
        self::$SCHEMA_SPARSE_SHORT_LIST = $m[1];
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
        if (isset($this->sparseStringList)) {
            $serializer->serializeList(self::$SCHEMA_SPARSE_STRING_LIST, $this->sparseStringList, 0);
        }
        if (isset($this->sparseShortList)) {
            $serializer->serializeList(self::$SCHEMA_SPARSE_SHORT_LIST, $this->sparseShortList, 1);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->sparseStringList,
            1 => $this->sparseShortList,
            default => null,
        };
    }

}
