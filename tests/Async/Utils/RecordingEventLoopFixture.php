<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Async\Utils;

use Smithy\Async\EventLoopInterface;

final class RecordingEventLoopFixture implements EventLoopInterface
{
    public int $runCalls = 0;

    /** @var array<callable(): mixed> */
    public array $receivedCallbacks = [];

    /** @var list<float> */
    public array $delays = [];

    /** @var array<string, array{seconds: float, callback: callable(): void}> */
    public array $scheduled = [];

    /** @var list<string> */
    public array $cancelled = [];

    /** @var list<callable(): void> */
    public array $deferred = [];

    public function run(array $callbacks): array
    {
        $this->runCalls++;
        $this->receivedCallbacks = $callbacks;

        return \array_map(
            static fn(callable $callback): mixed => $callback(),
            $callbacks
        );
    }

    public function suspend(\Closure $onSuspend): mixed
    {
        $result = null;
        $onSuspend(
            static function (mixed $value = null) use (&$result): void {
                $result = $value;
            },
            static function (\Throwable $e): never {
                throw $e;
            }
        );

        return $result;
    }

    public function delay(float $seconds): void
    {
        $this->delays[] = $seconds;
    }

    public function schedule(float $seconds, callable $callback): string
    {
        $callbackId = 'callback-' . (\count($this->scheduled) + 1);
        $this->scheduled[$callbackId] = [
            'seconds' => $seconds,
            'callback' => $callback
        ];

        return $callbackId;
    }

    public function cancel(string $callbackId): void
    {
        $this->cancelled[] = $callbackId;
    }

    public function defer(callable $callback): void
    {
        $this->deferred[] = $callback;
    }
}
