<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Waiter;

use PHPUnit\Framework\TestCase;
use Smithy\Client\Waiter\Backoff;
final class BackoffTest extends TestCase
{
    public function testFirstRetryUsesMinimumDelay(): void
    {
        $backoff = new Backoff(2, 120);

        $this->assertSame(2.0, $backoff->computeDelay(1, 300.0));
    }

    public function testDelayRemainsInsideExponentialJitterBounds(): void
    {
        $backoff = new Backoff(2, 120);

        for ($attempt = 1; $attempt <= 32; $attempt++) {
            $delay = $backoff->computeDelay($attempt, 10_000.0);
            $upperBound = \min(120, 2 * (2 ** ($attempt - 1)));

            $this->assertGreaterThanOrEqual(2.0, $delay);
            $this->assertLessThanOrEqual((float) $upperBound, $delay);
        }
    }

    public function testDelayCapsAtMaximumAfterAttemptCeiling(): void
    {
        $backoff = new Backoff(2, 10);

        for ($attempt = 10; $attempt <= 20; $attempt++) {
            $delay = $backoff->computeDelay($attempt, 100.0);

            $this->assertGreaterThanOrEqual(2.0, $delay);
            $this->assertLessThanOrEqual(10.0, $delay);
        }
    }

    public function testEqualBoundsProduceLinearBackoff(): void
    {
        $backoff = new Backoff(5, 5);

        $this->assertSame(5.0, $backoff->computeDelay(1, 100.0));
        $this->assertSame(5.0, $backoff->computeDelay(20, 100.0));
    }

    public function testFinalDelayConsumesRemainingTime(): void
    {
        $backoff = new Backoff(2, 120);

        $this->assertSame(3.5, $backoff->computeDelay(1, 3.5));
    }

    public function testRejectsInvalidBounds(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('at least 1 second');
        new Backoff(0, 120);
    }

    public function testRejectsMaximumBelowMinimum(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('at least the minimum');
        new Backoff(5, 4);
    }

    public function testRejectsInvalidAttempt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('attempt must be at least 1');
        (new Backoff(2, 120))->computeDelay(0, 300.0);
    }

    public function testRejectsInvalidRemainingTime(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('finite and greater than zero');
        (new Backoff(2, 120))->computeDelay(1, \INF);
    }
}
