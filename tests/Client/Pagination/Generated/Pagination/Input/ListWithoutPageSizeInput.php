<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Pagination\Generated\Pagination\Input;

use Smithy\Client\Pagination\PaginatedRequestInterface;
use Smithy\Core\Serde\{
    Schema,
    SerializableStructInterface,
    ShapeSerializerInterface
};
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\Tests\Client\Pagination\Generated\Pagination\Schemas;
final class ListWithoutPageSizeInput implements SerializableStructInterface, PaginatedRequestInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'smithy.pagination#ListWithoutPageSizeInput';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_CURSOR;

    private ?string $cursor;

    public function __construct(
        ?string $cursor = null
    ) {
        $this->cursor = $cursor;
    }

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::listWithoutPageSizeInput();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_CURSOR = $m[0];
        return self::$SCHEMA = $s;
    }

    /**
     * Get the pagination token.
     */
    public function getPaginationToken(): ?string
    {
        return $this->cursor;
    }

    /**
     * Create a copy with a pagination token.
     *
     * @param string|null $token
     */
    public function withPaginationToken(string|array|null $token): self
    {
        $copy = clone $this;
        $copy->cursor = $token;

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
        if (isset($this->cursor)) {
            $serializer->serializeString(self::$SCHEMA_CURSOR, $this->cursor, 0);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->cursor,
            default => null,
        };
    }

}
