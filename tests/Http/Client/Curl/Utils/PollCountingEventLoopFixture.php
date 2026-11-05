<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Http\Client\Curl\Utils;

use Smithy\Async\EventLoopInterface;
use Smithy\Async\RevoltEventLoop;

final class PollCountingEventLoopFixture implements EventLoopInterface
{
    public int $delayCalls = 0;
    public int $scheduleCalls = 0;
    public int $deferCalls = 0;
    public int $maxConsecutiveDefers = 0;

    /** @var list<float> */
    public array $scheduledDelays = [];

    private readonly RevoltEventLoop $inner;
    private int $consecutiveDefers = 0;

    public function __construct()
    {
        $this->inner = new RevoltEventLoop();
    }

    /** @inheritDoc */
    public function run(array $callbacks): array
    {
        return $this->inner->run($callbacks);
    }

    /** @inheritDoc */
    public function suspend(\Closure $onSuspend): mixed
    {
        return $this->inner->suspend($onSuspend);
    }

    /** @inheritDoc */
    public function delay(float $seconds): void
    {
        $this->delayCalls++;
        $this->inner->delay($seconds);
    }

    /** @inheritDoc */
    public function schedule(
        float $seconds,
        callable $callback
    ): string
    {
        $this->scheduleCalls++;
        $this->consecutiveDefers = 0;
        $this->scheduledDelays[] = $seconds;

        return $this->inner->schedule(
            $seconds,
            $callback
        );
    }

    /** @inheritDoc */
    public function cancel(string $callbackId): void
    {
        $this->inner->cancel($callbackId);
    }

    /** @inheritDoc */
    public function defer(callable $callback): void
    {
        $this->deferCalls++;
        $this->consecutiveDefers++;
        $this->maxConsecutiveDefers = \max(
            $this->maxConsecutiveDefers,
            $this->consecutiveDefers
        );
        $this->inner->defer($callback);
    }
}
