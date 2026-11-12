<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestXml\Shape;

/** @internal */
final readonly class XmlUnionShapeStructValue extends XmlUnionShape
{
    public function __construct(public XmlNestedUnionStruct $value) {}

    public function getMemberIndex(): int
    {
        return 9;
    }

    public function tag(): string
    {
        return 'structValue';
    }

    public function value(): XmlNestedUnionStruct
    {
        return $this->value;
    }
}
