<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Auth\Utils;

/**
 * Test clock that only moves when advanced.
 */
final class ClockFixture
{
    public function __construct(public float $now = 1_000_000.0) {}

    public function advance(float $seconds): void
    {
        $this->now += $seconds;
    }

    public function __invoke(): float
    {
        return $this->now;
    }
}
