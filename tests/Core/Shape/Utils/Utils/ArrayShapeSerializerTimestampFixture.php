<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Utils\Utils;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;

final class ArrayShapeSerializerTimestampFixture extends AbstractArrayShapeSerializerFixture
{
    private static Schema $SCHEMA;

    public function __construct(public readonly float $t) {}

    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: 'test#ArrayShapeSerializerTimestampFixture',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.api#Timestamp',
                    type: ShapeType::Timestamp,
                    memberName: 't',
                    memberIndex: 0
                )
            ],
            targetClass: self::class
        );
    }
}
