<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestJson\Shape;

/** @internal */
final readonly class EventStreamHeadersAndExplicitPayload extends EventStream
{
    public function __construct(public HeadersAndExplicitPayloadEvent $value) {}

    public function getMemberIndex(): int
    {
        return 5;
    }

    public function tag(): string
    {
        return 'headersAndExplicitPayload';
    }

    public function value(): HeadersAndExplicitPayloadEvent
    {
        return $this->value;
    }
}
