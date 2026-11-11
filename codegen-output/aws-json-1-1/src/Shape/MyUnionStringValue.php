<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\AwsJson1_1\Shape;

/** @internal */
final readonly class MyUnionStringValue extends MyUnion
{
    public function __construct(public string $value) {}

    public function getMemberIndex(): int
    {
        return 0;
    }

    public function tag(): string
    {
        return 'stringValue';
    }

    public function value(): string
    {
        return $this->value;
    }
}
