<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Http\Client\Curl\Utils;

use Smithy\Async\EventLoopInterface;
use Smithy\Async\RevoltEventLoop;

final class TimeoutBoundaryEventLoopFixture implements EventLoopInterface
{
    public int $delayedPollTimers = 0;
    private readonly RevoltEventLoop $inner;
    private int $pollTimers = 0;

    public function __construct(
        private readonly int $pollTimersBeforeDelay = 4,
        private readonly float $delayedPollSeconds = 0.1
    )
    {
        $this->inner = new RevoltEventLoop();
    }

    public function run(array $callbacks): array
    {
        return $this->inner->run($callbacks);
    }

    public function suspend(\Closure $onSuspend): mixed
    {
        return $this->inner->suspend($onSuspend);
    }

    public function delay(float $seconds): void
    {
        $this->inner->delay($seconds);
    }

    public function schedule(
        float $seconds,
        callable $callback
    ): string
    {
        if ($seconds <= 0.011) {
            $this->pollTimers++;

            if ($this->pollTimers > $this->pollTimersBeforeDelay) {
                $seconds = $this->delayedPollSeconds;
                $this->delayedPollTimers++;
            }
        }

        return $this->inner->schedule(
            $seconds,
            $callback
        );
    }

    public function cancel(string $callbackId): void
    {
        $this->inner->cancel($callbackId);
    }

    public function defer(callable $callback): void
    {
        $this->inner->defer($callback);
    }
}
