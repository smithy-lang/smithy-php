<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Utils\Utils;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;

final class ArrayShapeSerializerFloatFixture extends AbstractArrayShapeSerializerFixture
{
    private static Schema $SCHEMA;

    public function __construct(public readonly float $v) {}

    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: 'test#ArrayShapeSerializerFloatFixture',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.api#Float',
                    type: ShapeType::Float_,
                    memberName: 'v',
                    memberIndex: 0
                )
            ],
            targetClass: self::class
        );
    }
}
