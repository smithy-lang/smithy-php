<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestXml\Shape;

/** @internal */
final readonly class XmlUnionShapeDoubleValue extends XmlUnionShape
{
    public function __construct(public float $value) {}

    public function getMemberIndex(): int
    {
        return 7;
    }

    public function tag(): string
    {
        return 'doubleValue';
    }

    public function value(): float
    {
        return $this->value;
    }
}
