<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\AwsJson1_0\Shape;

/** @internal */
final readonly class MyUnionListValue extends MyUnion
{
    /**
     * @param list<string> $value
     */
    public function __construct(public array $value) {}

    public function getMemberIndex(): int
    {
        return 7;
    }

    public function tag(): string
    {
        return 'listValue';
    }

    /**
     * @return list<string>
     */
    public function value(): array
    {
        return $this->value;
    }
}
