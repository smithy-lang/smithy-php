<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\CodegenOutput\TrimmedPetStore\Shape;

/** @internal */
final readonly class UnitUnionNone extends UnitUnion
{
    public function __construct(public Unit $value) {}

    public function getMemberIndex(): int
    {
        return 0;
    }

    public function tag(): string
    {
        return 'none';
    }

    public function value(): Unit
    {
        return $this->value;
    }
}
