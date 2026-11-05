<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Core\Serde\Utils;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;

/**
 * Named helper for the `resolveTarget()` recursive-reference test. Schema dispatches to
 * `($targetClass)::schema()` when it holds a lazy stub, so the test needs an actual class with a
 * static `schema()` method, an inline anonymous class doesn't work here because we need its FQCN as
 * a string to hand to the Schema constructor.
 */
final class SchemaTestTargetFixture
{
    public static Schema $canonical;

    public static function schema(): Schema
    {
        return self::$canonical ??= new Schema(
            id: 'test#Target',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'test#Target$leaf',
                    type: ShapeType::String_,
                    memberName: 'leaf',
                    memberIndex: 0
                )
            ]
        );
    }
}
