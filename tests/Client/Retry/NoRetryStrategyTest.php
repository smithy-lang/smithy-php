<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Retry;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Retry\{
    NoRetryStrategy,
    RetryRequestFlags,
    RetryState,
    TokenAcquisitionFailedException
};

#[CoversClass(NoRetryStrategy::class)]
final class NoRetryStrategyTest extends TestCase
{
    public function testReturnsSharedInstance(): void
    {
        self::assertSame(
            NoRetryStrategy::instance(),
            NoRetryStrategy::instance()
        );
    }

    public function testBeginsSingleAttemptState(): void
    {
        $state = NoRetryStrategy::instance()->begin(
            'service',
            RetryRequestFlags::LONG_POLLING
        );

        self::assertSame('service', $state->scope);
        self::assertSame(
            RetryRequestFlags::LONG_POLLING,
            $state->requestFlags
        );
        self::assertSame(1, $state->attempt);
    }

    public function testRefreshRejectsRetry(): void
    {
        $strategy = NoRetryStrategy::instance();
        $state = new RetryState('service');
        $previous = new \RuntimeException('request failed');

        try {
            $strategy->refresh($state, $previous);
            self::fail('Expected retry token acquisition to fail.');
        } catch (TokenAcquisitionFailedException $exception) {
            self::assertSame(
                'Retries are disabled for this client.',
                $exception->getMessage()
            );
            self::assertSame($previous, $exception->getPrevious());
            self::assertSame($state, $exception->state);
        }
    }

    public function testRecordSuccessDoesNotChangeState(): void
    {
        $state = new RetryState(
            scope: 'service',
            requestFlags: RetryRequestFlags::LONG_POLLING,
            attempt: 2,
            lastRetryCost: 3,
            capacityRemaining: 4
        );

        NoRetryStrategy::instance()->recordSuccess($state);

        self::assertSame(2, $state->attempt);
        self::assertSame(3, $state->lastRetryCost);
        self::assertSame(4, $state->capacityRemaining);
    }

    public function testAllowsOneAttempt(): void
    {
        self::assertSame(1, NoRetryStrategy::instance()->maxAttempts());
    }
}
