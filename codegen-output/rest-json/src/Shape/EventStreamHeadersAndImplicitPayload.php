<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestJson\Shape;

/** @internal */
final readonly class EventStreamHeadersAndImplicitPayload extends EventStream
{
    public function __construct(public HeadersAndImplicitPayloadEvent $value) {}

    public function getMemberIndex(): int
    {
        return 6;
    }

    public function tag(): string
    {
        return 'headersAndImplicitPayload';
    }

    public function value(): HeadersAndImplicitPayloadEvent
    {
        return $this->value;
    }
}
