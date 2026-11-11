<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestJson\Shape;

/** @internal */
final readonly class EventStreamUnionPayload extends EventStream
{
    public function __construct(public UnionPayloadEvent $value) {}

    public function getMemberIndex(): int
    {
        return 4;
    }

    public function tag(): string
    {
        return 'unionPayload';
    }

    public function value(): UnionPayloadEvent
    {
        return $this->value;
    }
}
