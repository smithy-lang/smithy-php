<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestJson\Shape;

/** @internal */
final readonly class MyUnionEnumValue extends MyUnion
{
    public function __construct(public FooEnum $value) {}

    public function getMemberIndex(): int
    {
        return 5;
    }

    public function tag(): string
    {
        return 'enumValue';
    }

    public function value(): FooEnum
    {
        return $this->value;
    }
}
