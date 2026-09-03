<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Pagination\Fixture;

use Smithy\Core\Serde\{
    Schema,
    SerializableStructInterface,
    ShapeSerializerInterface
};
use Smithy\Core\Shape\ShapeType;
final class PaginationResult implements SerializableStructInterface
{
    use \Smithy\Core\Shape\WithMemberValueTrait;
    private static ?Schema $schema = null;

    /** @param list<string> $items */
    public function __construct(
        public readonly string|array|null $nextToken,
        public readonly array $items = []
    ) {}

    public static function schema(): Schema
    {
        return self::$schema ??= new Schema(
            id: 'smithy.tests#PaginationResult',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.api#Document',
                    type: ShapeType::Document,
                    memberName: 'nextToken',
                    memberIndex: 0
                ),
                new Schema(
                    id: 'smithy.tests#PaginationItems',
                    type: ShapeType::List_,
                    memberName: 'items',
                    memberIndex: 1,
                    listMember: new Schema(
                        id: 'smithy.api#String',
                        type: ShapeType::String_
                    )
                )
            ]
        );
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
            0 => $this->nextToken,
            1 => $this->items,
            default => null
        };
    }
}
