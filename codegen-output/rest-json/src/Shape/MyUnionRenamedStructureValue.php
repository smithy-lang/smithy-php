<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestJson\Shape;

/** @internal */
final readonly class MyUnionRenamedStructureValue extends MyUnion
{
    public function __construct(public GreetingStruct $value) {}

    public function getMemberIndex(): int
    {
        return 9;
    }

    public function tag(): string
    {
        return 'renamedStructureValue';
    }

    public function value(): GreetingStruct
    {
        return $this->value;
    }
}
