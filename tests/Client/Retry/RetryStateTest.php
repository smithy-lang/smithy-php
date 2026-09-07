<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Retry;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Retry\{
    RetryRequestFlags,
    RetryState
};

#[CoversClass(RetryState::class)]
final class RetryStateTest extends TestCase
{
    public function testUsesInitialAttemptDefaults(): void
    {
        $state = new RetryState('service');

        self::assertSame('service', $state->scope);
        self::assertSame(0, $state->requestFlags);
        self::assertSame(1, $state->attempt);
        self::assertSame(0, $state->lastRetryCost);
        self::assertSame(0, $state->capacityRemaining);
    }

    public function testStoresAndUpdatesStrategyState(): void
    {
        $state = new RetryState(
            scope: 'service',
            requestFlags: RetryRequestFlags::LONG_POLLING,
            attempt: 2,
            lastRetryCost: 3,
            capacityRemaining: 4
        );

        self::assertSame(
            RetryRequestFlags::LONG_POLLING,
            $state->requestFlags
        );
        self::assertSame(2, $state->attempt);
        self::assertSame(3, $state->lastRetryCost);
        self::assertSame(4, $state->capacityRemaining);

        $state->attempt = 5;
        $state->lastRetryCost = 6;
        $state->capacityRemaining = 7;

        self::assertSame(5, $state->attempt);
        self::assertSame(6, $state->lastRetryCost);
        self::assertSame(7, $state->capacityRemaining);
    }
}
