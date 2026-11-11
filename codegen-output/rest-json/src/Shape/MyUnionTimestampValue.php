<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestJson\Shape;

/** @internal */
final readonly class MyUnionTimestampValue extends MyUnion
{
    public function __construct(public float $value) {}

    public function getMemberIndex(): int
    {
        return 4;
    }

    public function tag(): string
    {
        return 'timestampValue';
    }

    public function value(): float
    {
        return $this->value;
    }
}
