<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RpcV2Cbor\Shape;

/** @internal */
final readonly class RpcV2CborUnionUnionValue extends RpcV2CborUnion
{
    public function __construct(public RpcV2CborNestedUnion $value) {}

    public function getMemberIndex(): int
    {
        return 1;
    }

    public function tag(): string
    {
        return 'unionValue';
    }

    public function value(): RpcV2CborNestedUnion
    {
        return $this->value;
    }
}
