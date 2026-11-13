<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestJson\Shape;

/** @internal */
final readonly class UnionWithJsonNameBar extends UnionWithJsonName
{
    public function __construct(public string $value) {}

    public function getMemberIndex(): int
    {
        return 1;
    }

    public function tag(): string
    {
        return 'bar';
    }

    public function value(): string
    {
        return $this->value;
    }
}
