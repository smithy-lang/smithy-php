<?php

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
final class ListStringsInput implements
    SerializableStructInterface,
    PaginatedRequestInterface,
    PageSizePaginatedRequestInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'smithy.pagination#ListStringsInput';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_NEXT_TOKEN;
    private static Schema $SCHEMA_PAGE_SIZE;

    private ?string $nextToken;
    private ?int $pageSize;

    public function __construct(
        ?string $nextToken = null,
        ?int $pageSize = null
    ) {
        $this->nextToken = $nextToken;
        $this->pageSize = $pageSize;
    }

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::listStringsInput();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_NEXT_TOKEN = $m[0];
        self::$SCHEMA_PAGE_SIZE = $m[1];
        return self::$SCHEMA = $s;
    }

    /**
     * Get the pagination token.
     */
    public function getPaginationToken(): ?string
    {
        return $this->nextToken;
    }

    /**
     * Create a copy with a pagination token.
     *
     * @param string|null $token
     */
    public function withPaginationToken(string|array|null $token): self
    {
        $copy = clone $this;
        $copy->nextToken = $token;

        return $copy;
    }

    /**
     * Get the pagination page size.
     */
    public function getPaginationPageSize(): ?int
    {
        return $this->pageSize;
    }

    /**
     * Create a copy with a pagination page size.
     */
    public function withPaginationPageSize(int $pageSize): self
    {
        $copy = clone $this;
        $copy->pageSize = $pageSize;

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
        if (isset($this->nextToken)) {
            $serializer->serializeString(self::$SCHEMA_NEXT_TOKEN, $this->nextToken, 0);
        }
        if (isset($this->pageSize)) {
            $serializer->serializeInteger(self::$SCHEMA_PAGE_SIZE, $this->pageSize, 1);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->nextToken,
            1 => $this->pageSize,
            default => null,
        };
    }

}
