<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestJson\Shape;

use Smithy\ProtocolTests\RestJson\Exception\ErrorEventException;

/** @internal */
final readonly class EventStreamError extends EventStream
{
    public function __construct(public ErrorEventException $value) {}

    public function getMemberIndex(): int
    {
        return 7;
    }

    public function tag(): string
    {
        return 'error';
    }

    public function value(): ErrorEventException
    {
        return $this->value;
    }
}
