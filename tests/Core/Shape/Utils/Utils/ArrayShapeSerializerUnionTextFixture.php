<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Utils\Utils;

final class ArrayShapeSerializerUnionTextFixture
    extends ArrayShapeSerializerUnionFixture
{
    /**
     * Create a text variant.
     */
    public function __construct(private readonly string $value) {}

    /** @inheritDoc */
    public function tag(): string
    {
        return 'text';
    }

    /** @inheritDoc */
    public function value(): mixed
    {
        return $this->value;
    }
}
