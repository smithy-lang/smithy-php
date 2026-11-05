<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Pagination\Generated\Pagination\Input;

use Smithy\Client\Pagination\{
    PageSizePaginatedRequestInterface,
    PaginatedRequestInterface
};
use Smithy\Core\Serde\{
    Schema,
    SerializableStructInterface,
    ShapeSerializerInterface
};
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\Tests\Client\Pagination\Generated\Pagination\Schemas;
final class ListMapsInput implements
    SerializableStructInterface,
    PaginatedRequestInterface,
    PageSizePaginatedRequestInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'smithy.pagination#ListMapsInput';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_EXCLUSIVE_START_KEY;
    private static Schema $SCHEMA_LIMIT;

    private ?array $exclusiveStartKey;
    private ?int $limit;

    /**
     * @param array<string, string>|null $exclusiveStartKey
     */
    public function __construct(
        ?array $exclusiveStartKey = null,
        ?int $limit = null
    ) {
        $this->exclusiveStartKey = $exclusiveStartKey;
        $this->limit = $limit;
    }

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::listMapsInput();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_EXCLUSIVE_START_KEY = $m[0];
        self::$SCHEMA_LIMIT = $m[1];
        return self::$SCHEMA = $s;
    }

    /**
     * Get the pagination token.
     *
     * @return array<string, string>|null
     */
    public function getPaginationToken(): ?array
    {
        return $this->exclusiveStartKey;
    }

    /**
     * Create a copy with a pagination token.
     *
     * @param array<string, string>|null $token
     */
    public function withPaginationToken(string|array|null $token): self
    {
        $copy = clone $this;
        $copy->exclusiveStartKey = $token;

        return $copy;
    }

    /**
     * Get the pagination page size.
     */
    public function getPaginationPageSize(): ?int
    {
        return $this->limit;
    }

    /**
     * Create a copy with a pagination page size.
     */
    public function withPaginationPageSize(int $pageSize): self
    {
        $copy = clone $this;
        $copy->limit = $pageSize;

        return $copy;
    }

    /**
     * Serialize the shape to the desired wire format.
     *
     * @return void
     */
    public function getSchema(): Schema
    {
        return self::schema();
    }

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
        if (isset($this->exclusiveStartKey)) {
            $serializer->serializeMap(self::$SCHEMA_EXCLUSIVE_START_KEY, $this->exclusiveStartKey, 0);
        }
        if (isset($this->limit)) {
            $serializer->serializeInteger(self::$SCHEMA_LIMIT, $this->limit, 1);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->exclusiveStartKey,
            1 => $this->limit,
            default => null,
        };
    }

}
