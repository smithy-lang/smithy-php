<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Pagination\Fixture;

use Smithy\Client\Pagination\PageSizePaginatedRequestInterface;
use Smithy\Core\Serde\{
    Schema,
    ShapeSerializerInterface
};
use Smithy\Core\Shape\ShapeType;
final class PaginationInput implements PageSizePaginatedRequestInterface
{
    use \Smithy\Core\Shape\WithMemberValueTrait;

    private static ?Schema $schema = null;

    public function __construct(
        private string|array|null $token = null,
        private ?int $pageSize = null
    ) {}

    public static function schema(): Schema
    {
        return self::$schema ??= new Schema(
            id: 'smithy.tests#PaginationInput',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.api#Document',
                    type: ShapeType::Document,
                    memberName: 'nextToken',
                    memberIndex: 0
                ),
                new Schema(
                    id: 'smithy.api#Integer',
                    type: ShapeType::Integer,
                    memberName: 'maxResults',
                    memberIndex: 1
                )
            ]
        );
    }

    public function getPaginationToken(): string|array|null
    {
        return $this->token;
    }

    public function withPaginationToken(string|array|null $token): self
    {
        $copy = clone $this;
        $copy->token = $token;

        return $copy;
    }

    public function getPaginationPageSize(): ?int
    {
        return $this->pageSize;
    }

    public function withPaginationPageSize(int $pageSize): self
    {
        $copy = clone $this;
        $copy->pageSize = $pageSize;

        return $copy;
    }

    public function getSchema(): Schema
    {
        return self::schema();
    }

    public function serialize(
        ShapeSerializerInterface $serializer,
        int $memberIndex
    ): void {}

    public function serializeMembers(
        ShapeSerializerInterface $serializer
    ): void {}

    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->token,
            1 => $this->pageSize,
            default => null
        };
    }
}
