<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Waiter\Fixture;

use Smithy\Async\EventLoopInterface;
final class RecordingEventLoop implements EventLoopInterface
{
    /** @var list<float> */
    public array $delays = [];
    private int $nextCallbackId = 0;

    public function run(array $callbacks): array
    {
        $results = [];
        foreach ($callbacks as $callback) {
            $results[] = $callback();
        }

        return $results;
    }

    public function suspend(\Closure $onSuspend): mixed
    {
        throw new \LogicException(
            'RecordingEventLoop cannot suspend a fiber.'
        );
    }

    public function delay(float $seconds): void
    {
        $this->delays[] = $seconds;
    }

    public function schedule(
        float $seconds,
        callable $callback
    ): string
    {
        $this->delays[] = $seconds;

        return (string) ++$this->nextCallbackId;
    }

    public function cancel(string $callbackId): void {}

    public function defer(callable $callback): void
    {
        $callback();
    }
}
