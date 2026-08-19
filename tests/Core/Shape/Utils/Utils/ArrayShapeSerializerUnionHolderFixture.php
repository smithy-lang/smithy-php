<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Utils\Utils;

use Smithy\Core\Serde\{
    Schema,
    ShapeType
};

final class ArrayShapeSerializerUnionHolderFixture
    extends AbstractArrayShapeSerializerFixture
{
    private static Schema $SCHEMA;

    /**
     * Create a fixture containing a union.
     */
    public function __construct(
        public readonly ArrayShapeSerializerUnionFixture $choice
    ) {}

    /**
     * Return the fixture schema.
     */
    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: 'test#ArrayShapeSerializerUnionHolderFixture',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'test#ArrayShapeSerializerUnionFixture',
                    type: ShapeType::Union,
                    memberName: 'choice',
                    memberIndex: 0,
                    members: \array_values(
                        ArrayShapeSerializerUnionFixture::schema()
                            ->getMembersByIndex()
                    ),
                    targetClass: ArrayShapeSerializerUnionFixture::class
                )
            ],
            targetClass: self::class
        );
    }
}
