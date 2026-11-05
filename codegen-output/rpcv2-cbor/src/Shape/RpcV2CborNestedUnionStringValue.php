<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RpcV2Cbor\Shape;

/** @internal */
final readonly class RpcV2CborNestedUnionStringValue extends RpcV2CborNestedUnion
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
