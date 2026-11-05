<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Utils\Utils;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\BigInteger;

final class ArrayShapeSerializerBigNumberFixture extends AbstractArrayShapeSerializerFixture
{
    private static Schema $SCHEMA;

    public function __construct(public readonly BigInteger $big) {}

    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: 'test#ArrayShapeSerializerBigNumberFixture',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.api#BigInteger',
                    type: ShapeType::BigInteger,
                    memberName: 'big',
                    memberIndex: 0
                )
            ],
            targetClass: self::class
        );
    }
}
