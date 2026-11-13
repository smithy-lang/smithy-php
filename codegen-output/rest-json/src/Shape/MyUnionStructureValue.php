<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestJson\Shape;

/** @internal */
final readonly class MyUnionStructureValue extends MyUnion
{
    public function __construct(public SharedGreetingStruct $value) {}

    public function getMemberIndex(): int
    {
        return 8;
    }

    public function tag(): string
    {
        return 'structureValue';
    }

    public function value(): SharedGreetingStruct
    {
        return $this->value;
    }
}
