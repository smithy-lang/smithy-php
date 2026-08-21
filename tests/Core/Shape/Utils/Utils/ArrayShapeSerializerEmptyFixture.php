<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Utils\Utils;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;

final class ArrayShapeSerializerEmptyFixture extends AbstractArrayShapeSerializerFixture
{
    private static Schema $SCHEMA;

    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: 'test#ArrayShapeSerializerEmptyFixture',
            type: ShapeType::Structure,
            targetClass: self::class
        );
    }
}
