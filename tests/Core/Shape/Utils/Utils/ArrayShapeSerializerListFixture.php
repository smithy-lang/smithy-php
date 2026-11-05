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

final class ArrayShapeSerializerListFixture extends AbstractArrayShapeSerializerFixture
{
    private static Schema $SCHEMA;

    /**
     * @param list<BigInteger> $big
     */
    public function __construct(public readonly array $big) {}

    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: 'test#ArrayShapeSerializerListFixture',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'test#bigList',
                    type: ShapeType::List_,
                    memberName: 'big',
                    memberIndex: 0,
                    listMember: new Schema(
                        id: 'smithy.api#BigInteger',
                        type: ShapeType::BigInteger
                    )
                )
            ],
            targetClass: self::class
        );
    }
}
