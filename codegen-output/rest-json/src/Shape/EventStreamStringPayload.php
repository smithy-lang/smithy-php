<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestJson\Shape;

/** @internal */
final readonly class EventStreamStringPayload extends EventStream
{
    public function __construct(public StringPayloadEvent $value) {}

    public function getMemberIndex(): int
    {
        return 2;
    }

    public function tag(): string
    {
        return 'stringPayload';
    }

    public function value(): StringPayloadEvent
    {
        return $this->value;
    }
}
