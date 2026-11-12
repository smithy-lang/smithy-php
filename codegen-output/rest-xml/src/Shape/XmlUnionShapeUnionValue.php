<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestXml\Shape;

/** @internal */
final readonly class XmlUnionShapeUnionValue extends XmlUnionShape
{
    public function __construct(public XmlUnionShape $value) {}

    public function getMemberIndex(): int
    {
        return 8;
    }

    public function tag(): string
    {
        return 'unionValue';
    }

    public function value(): XmlUnionShape
    {
        return $this->value;
    }
}
