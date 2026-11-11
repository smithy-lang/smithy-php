<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestJson\Shape;

/** @internal */
final readonly class MyUnionNumberValue extends MyUnion
{
    public function __construct(public int $value) {}

    public function getMemberIndex(): int
    {
        return 2;
    }

    public function tag(): string
    {
        return 'numberValue';
    }

    public function value(): int
    {
        return $this->value;
    }
}
