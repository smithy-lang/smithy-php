<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\AwsJson1_0\Shape;

/** @internal */
final readonly class MyUnionIntEnumValue extends MyUnion
{
    public function __construct(public IntegerEnum $value) {}

    public function getMemberIndex(): int
    {
        return 6;
    }

    public function tag(): string
    {
        return 'intEnumValue';
    }

    public function value(): IntegerEnum
    {
        return $this->value;
    }
}
