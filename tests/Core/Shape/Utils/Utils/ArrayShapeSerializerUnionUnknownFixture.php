<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Utils\Utils;

final class ArrayShapeSerializerUnionUnknownFixture
    extends ArrayShapeSerializerUnionFixture
{
    /**
     * Create an unknown variant.
     */
    public function __construct(
        private readonly string $tagName,
        private readonly mixed $variantValue
    ) {}

    /** @inheritDoc */
    public function tag(): string
    {
        return $this->tagName;
    }

    /** @inheritDoc */
    public function value(): mixed
    {
        return $this->variantValue;
    }
}
