<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RpcV2Cbor\Shape;

/**
 * Unknown variant for forward-compatibility.
 */
/** @internal */
final readonly class RpcV2CborUnionUnknown extends RpcV2CborUnion
{
    public function __construct(private string $unknownTag, private mixed $unknownValue) {}

    public function getMemberIndex(): int
    {
        return -1;
    }

    public function tag(): string
    {
        return $this->unknownTag;
    }

    public function value(): mixed
    {
        return $this->unknownValue;
    }
}
