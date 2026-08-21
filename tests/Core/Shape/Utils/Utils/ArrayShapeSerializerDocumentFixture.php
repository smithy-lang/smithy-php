<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Utils\Utils;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\Document\AbstractDocument;

final class ArrayShapeSerializerDocumentFixture extends AbstractArrayShapeSerializerFixture
{
    private static Schema $SCHEMA;

    public function __construct(public readonly ?AbstractDocument $doc = null) {}

    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: 'test#ArrayShapeSerializerDocumentFixture',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.api#Document',
                    type: ShapeType::Document,
                    memberName: 'doc',
                    memberIndex: 0
                )
            ],
            targetClass: self::class
        );
    }
}
