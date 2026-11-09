<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Utils\Utils;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ArraySerializableTrait;
use Smithy\Core\Shape\ShapeType;

final class ArrayShapeSerializerScalarFixture extends AbstractArrayShapeSerializerFixture
{
    use ArraySerializableTrait;

    private static Schema $SCHEMA;

    public function __construct(
        public readonly string $name,
        public readonly int $count,
        public readonly ?bool $active = null
    ) {
    }

    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: 'test#ArrayShapeSerializerScalarFixture',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.api#String',
                    type: ShapeType::String_,
                    memberName: 'name',
                    memberIndex: 0
                ),
                new Schema(
                    id: 'smithy.api#Integer',
                    type: ShapeType::Integer,
                    memberName: 'count',
                    memberIndex: 1
                ),
                new Schema(
                    id: 'smithy.api#Boolean',
                    type: ShapeType::Boolean,
                    memberName: 'active',
                    memberIndex: 2
                )
            ],
            targetClass: self::class
        );
    }
}
