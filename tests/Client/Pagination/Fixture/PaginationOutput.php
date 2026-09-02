<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Pagination\Fixture;

use Smithy\Core\Serde\{
    Schema,
    SerializableStructInterface,
    ShapeSerializerInterface
};
use Smithy\Core\Shape\ShapeType;
final class PaginationOutput implements SerializableStructInterface
{
    use \Smithy\Core\Shape\WithMemberValueTrait;
    private static ?Schema $schema = null;

    public function __construct(
        public readonly string $value,
        public readonly ?PaginationResult $result
    ) {}

    public static function schema(): Schema
    {
        return self::$schema ??= new Schema(
            id: 'smithy.tests#PaginationOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.tests#PaginationResult',
                    type: ShapeType::Structure,
                    memberName: 'result',
                    memberIndex: 0,
                    targetClass: PaginationResult::class
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
        return $member->memberIndex === 0 ? $this->result : null;
    }
}
