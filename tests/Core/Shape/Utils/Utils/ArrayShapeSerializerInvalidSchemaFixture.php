<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Utils\Utils;

/**
 * Struct fixture whose static schema() returns a value that is not a Schema.
 */
final class ArrayShapeSerializerInvalidSchemaFixture extends AbstractArrayShapeSerializerFixture
{
    public static function schema(): string
    {
        return 'not-a-schema';
    }
}
