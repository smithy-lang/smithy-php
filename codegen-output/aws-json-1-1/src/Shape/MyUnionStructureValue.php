<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\AwsJson1_1\Shape;

/** @internal */
final readonly class MyUnionStructureValue extends MyUnion
{
    public function __construct(public GreetingStruct $value) {}

    public function getMemberIndex(): int
    {
        return 8;
    }

    public function tag(): string
    {
        return 'structureValue';
    }

    public function value(): GreetingStruct
    {
        return $this->value;
    }
}
