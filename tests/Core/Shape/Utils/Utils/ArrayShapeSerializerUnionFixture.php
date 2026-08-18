<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Utils\Utils;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Serde\ShapeType;
use Smithy\Core\Shape\UnionInterface;

final class ArrayShapeSerializerUnionFixture implements UnionInterface
{
    private static Schema $SCHEMA;

    public function __construct(
        public readonly ?string $foo = null
    ) {
    }

    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: 'test#ArrayShapeSerializerUnionFixture',
            type: ShapeType::Union,
            members: [
                new Schema(
                    id: 'smithy.api#String',
                    type: ShapeType::String_,
                    memberName: 'foo',
                    memberIndex: 0
                )
            ],
            targetClass: self::class
        );
    }

    public function getSchema(): Schema
    {
        return self::schema();
    }

    public function serialize(ShapeSerializerInterface $serializer, int $idx): void {}

    public function tag(): string
    {
        return 'foo';
    }

    public function value(): mixed
    {
        return $this->foo;
    }
}
