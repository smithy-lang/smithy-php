<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestXml\Shape;

/** @internal */
final readonly class XmlUnionShapeIntegerValue extends XmlUnionShape
{
    public function __construct(public int $value) {}

    public function getMemberIndex(): int
    {
        return 4;
    }

    public function tag(): string
    {
        return 'integerValue';
    }

    public function value(): int
    {
        return $this->value;
    }
}
