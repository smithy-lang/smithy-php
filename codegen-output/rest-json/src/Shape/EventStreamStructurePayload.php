<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestJson\Shape;

/** @internal */
final readonly class EventStreamStructurePayload extends EventStream
{
    public function __construct(public StructurePayloadEvent $value) {}

    public function getMemberIndex(): int
    {
        return 3;
    }

    public function tag(): string
    {
        return 'structurePayload';
    }

    public function value(): StructurePayloadEvent
    {
        return $this->value;
    }
}
