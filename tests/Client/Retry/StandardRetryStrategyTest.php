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
    RetryInfoInterface,
    RetryRequestFlags,
    RetrySafety,
    RetryState,
    RetryTokenBucket,
    StandardRetryStrategy,
    TokenAcquisitionFailedException
};
use Smithy\Core\Exception\{ApiException, ErrorFault};
use Smithy\Http\Client\Exception\NetworkException;
use Smithy\Http\Message\Request;

#[CoversClass(StandardRetryStrategy::class)]
#[CoversClass(RetryTokenBucket::class)]
final class StandardRetryStrategyTest extends TestCase
{
    public function testConstantsMatchSmithyJava(): void
    {
        self::assertSame(3, StandardRetryStrategy::MAX_ATTEMPTS);
        self::assertSame(0.05, StandardRetryStrategy::BASE_DELAY);
        self::assertSame(1.0, StandardRetryStrategy::THROTTLING_BASE_DELAY);
        self::assertSame(20.0, StandardRetryStrategy::MAX_BACKOFF);
        self::assertSame(14, StandardRetryStrategy::RETRY_COST);
        self::assertSame(5, StandardRetryStrategy::THROTTLING_RETRY_COST);
        self::assertSame(500, StandardRetryStrategy::INITIAL_RETRY_TOKENS);
        self::assertSame(128, StandardRetryStrategy::MAX_SCOPES);
        self::assertSame(3, (new StandardRetryStrategy())->maxAttempts());
    }

    public function testBackoffGrowsExponentiallyAndIsCapped(): void
    {
        // Random 0.0 gives jitter 1.0: the full computed delay.
        $strategy = $this->strategy(
            maxAttempts: 10,
            maxBackoff: 0.3,
            initialRetryTokens: 10000
        );
        $state = $strategy->begin('s');
        $delays = [];
        for ($i = 0; $i < 6; $i++) {
            $delays[] = $strategy->refresh($state, $this->serverError());
        }

        self::assertEqualsWithDelta(
            [0.05, 0.1, 0.2, 0.3, 0.3, 0.3],
            $delays,
            1e-9
        );
    }

    public function testJitterStaysWithinBounds(): void
    {
        $delays = [];
        foreach ([0.0, 0.5, 0.999999] as $random) {
            $strategy = $this->strategy(random: static fn(): float => $random);
            $state = $strategy->begin('s');
            $strategy->refresh($state, $this->serverError());
            $delays[] = $strategy->refresh($state, $this->serverError());
        }

        // Second retry has a computed delay of 100 ms.
        self::assertEqualsWithDelta(0.1, $delays[0], 1e-9);
        self::assertEqualsWithDelta(0.05, $delays[1], 1e-9);
        self::assertEqualsWithDelta(0.001, $delays[2], 1e-9);
    }

    public function testThrottlingUsesThrottlingDelayAndCost(): void
    {
        $strategy = $this->strategy();
        $state = $strategy->begin('s');

        $delay = $strategy->refresh($state, $this->info(RetrySafety::Yes, true));

        self::assertEqualsWithDelta(1.0, $delay, 1e-9);
        self::assertSame(5, $state->lastRetryCost);
        self::assertSame(495, $state->capacityRemaining);
    }

    public function testNonThrottlingRetryCostsFourteen(): void
    {
        $strategy = $this->strategy();
        $state = $strategy->begin('s');

        $strategy->refresh($state, $this->serverError());

        self::assertSame(14, $state->lastRetryCost);
        self::assertSame(486, $state->capacityRemaining);
        self::assertSame(2, $state->attempt);
    }

    public function testNonRetryableErrorsThrowImmediately(): void
    {
        $strategy = $this->strategy();
        $errors = [
            new \RuntimeException('plain'),
            new ApiException('client', ErrorFault::Client),
            $this->info(RetrySafety::No, false),
            $this->info(RetrySafety::Maybe, false),
        ];

        foreach ($errors as $error) {
            $state = $strategy->begin('s');
            try {
                $strategy->refresh($state, $error);
                self::fail('Expected non-retryable failure.');
            } catch (TokenAcquisitionFailedException $e) {
                self::assertSame($error, $e->getPrevious());
                self::assertSame(1, $state->attempt);
                self::assertSame(500, $state->capacityRemaining);
            }
        }
    }

    public function testRetryableErrorsAreClassified(): void
    {
        $strategy = $this->strategy();
        $network = new NetworkException(
            'down',
            new Request('GET', 'https://example.test/')
        );
        $errors = [
            new ApiException('server', ErrorFault::Server),
            $network,
            new \RuntimeException('wrapped', 0, $network),
            $this->info(RetrySafety::Yes, false),
            new \RuntimeException('w', 0, $this->info(RetrySafety::Yes, true)),
        ];

        foreach ($errors as $error) {
            $state = $strategy->begin('s');
            self::assertGreaterThan(0.0, $strategy->refresh($state, $error));
        }
    }

    public function testRetryInfoOverridesFaultClassification(): void
    {
        $strategy = $this->strategy();
        $error = new class('x', ErrorFault::Server) extends ApiException
            implements RetryInfoInterface {
            public function getRetrySafety(): RetrySafety
            {
                return RetrySafety::No;
            }

            public function isThrottle(): bool
            {
                return false;
            }

            public function getRetryAfter(): ?float
            {
                return null;
            }
        };

        $this->expectException(TokenAcquisitionFailedException::class);
        $strategy->refresh($strategy->begin('s'), $error);
    }

    public function testMaxAttemptsIsRespected(): void
    {
        $strategy = $this->strategy(maxAttempts: 3);
        $state = $strategy->begin('s');

        $strategy->refresh($state, $this->serverError());
        $strategy->refresh($state, $this->serverError());
        self::assertSame(3, $state->attempt);

        try {
            $strategy->refresh($state, $this->serverError());
            self::fail('Expected retries to be exhausted.');
        } catch (TokenAcquisitionFailedException $e) {
            self::assertStringContainsString('exhausted', $e->getMessage());
            // No tokens are spent once attempts are exhausted.
            self::assertSame(500 - 28, $state->capacityRemaining);
        }
    }

    public function testSingleAttemptStrategyNeverRetries(): void
    {
        $strategy = $this->strategy(maxAttempts: 1);

        $this->expectException(TokenAcquisitionFailedException::class);
        $strategy->refresh($strategy->begin('s'), $this->serverError());
    }

    public function testBucketDepletesAndRefusesWhenExhausted(): void
    {
        $strategy = $this->strategy(
            maxAttempts: 100,
            initialRetryTokens: 30,
            maxBackoff: 0.0
        );
        $state = $strategy->begin('s');

        $strategy->refresh($state, $this->serverError());
        $strategy->refresh($state, $this->serverError());
        self::assertSame(2, $state->capacityRemaining);

        try {
            $strategy->refresh($state, $this->serverError());
            self::fail('Expected capacity exhaustion.');
        } catch (TokenAcquisitionFailedException $e) {
            self::assertStringContainsString('retry capacity', $e->getMessage());
            self::assertSame(0.0, $e->delay);
            self::assertSame(2, $state->capacityRemaining);
            self::assertSame(0, $state->lastRetryCost);
        }

        // Even a throttling retry (cost 5) no longer fits in 2 tokens.
        $this->expectException(TokenAcquisitionFailedException::class);
        $strategy->refresh($state, $this->info(RetrySafety::Yes, true));
    }

    public function testLongPollingExhaustionCarriesBackoffDelay(): void
    {
        $strategy = $this->strategy(initialRetryTokens: 0);
        $state = $strategy->begin('s', RetryRequestFlags::LONG_POLLING);

        try {
            $strategy->refresh($state, $this->serverError());
            self::fail('Expected capacity exhaustion.');
        } catch (TokenAcquisitionFailedException $e) {
            self::assertEqualsWithDelta(0.05, $e->delay, 1e-9);
        }
    }

    public function testRecordSuccessRestoresCapacity(): void
    {
        $strategy = $this->strategy();
        $state = $strategy->begin('s');
        $strategy->refresh($state, $this->serverError());
        $strategy->refresh($state, $this->serverError());
        self::assertSame(472, $state->capacityRemaining);

        $strategy->recordSuccess($state);
        self::assertSame(486, $state->capacityRemaining);
        self::assertSame(486, $strategy->begin('s')->capacityRemaining);
    }

    public function testRecordSuccessReleasesAtLeastOneTokenUpToMax(): void
    {
        $strategy = $this->strategy();
        $state = $strategy->begin('s');

        // No retries happened: capacity is full and stays capped.
        $strategy->recordSuccess($state);
        self::assertSame(500, $state->capacityRemaining);

        $strategy->refresh($state, $this->info(RetrySafety::Yes, true));
        $state->lastRetryCost = 0;
        $strategy->recordSuccess($state);
        self::assertSame(496, $state->capacityRemaining);
    }

    public function testScopesHaveSeparateBuckets(): void
    {
        $strategy = $this->strategy();
        $a = $strategy->begin('a');
        $strategy->refresh($a, $this->serverError());

        self::assertSame(500, $strategy->begin('b')->capacityRemaining);
        self::assertSame(486, $strategy->begin('a')->capacityRemaining);
    }

    public function testLeastRecentlyUsedScopeIsEvicted(): void
    {
        $strategy = $this->strategy(maxScopes: 2);
        $strategy->refresh($strategy->begin('a'), $this->serverError());
        $strategy->begin('b');
        $strategy->begin('a');
        $strategy->begin('c');

        // 'b' was evicted, 'a' survived with its spent capacity.
        self::assertSame(486, $strategy->begin('a')->capacityRemaining);
        self::assertSame(500, $strategy->begin('b')->capacityRemaining);
    }

    public function testRetryAfterIsClampedToWindowAboveBackoff(): void
    {
        $strategy = $this->strategy();

        $state = $strategy->begin('s');
        self::assertEqualsWithDelta(
            2.0,
            $strategy->refresh($state, $this->info(RetrySafety::Yes, false, 2.0)),
            1e-9
        );
        self::assertEqualsWithDelta(
            5.1,
            $strategy->refresh($state, $this->info(RetrySafety::Yes, false, 60.0)),
            1e-9
        );

        $strategy = $this->strategy();
        self::assertEqualsWithDelta(
            0.05,
            $strategy->refresh(
                $strategy->begin('s'),
                $this->info(RetrySafety::Yes, false, 0.001)
            ),
            1e-9
        );
    }

    public function testDefaultRandomSourceProducesBoundedDelay(): void
    {
        $strategy = new StandardRetryStrategy();
        $delay = $strategy->refresh(
            $strategy->begin('s'),
            $this->serverError()
        );

        self::assertGreaterThan(0.0, $delay);
        self::assertLessThanOrEqual(0.05, $delay);
    }

    public function testRejectsInvalidConfiguration(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new StandardRetryStrategy(maxAttempts: 0);
    }

    public function testTokenBucketRejectsInvalidAmounts(): void
    {
        $bucket = new RetryTokenBucket(10);

        self::assertTrue($bucket->tryAcquire(0));
        self::assertFalse($bucket->tryAcquire(11));
        self::assertSame(10, $bucket->capacity());
        $this->expectException(\InvalidArgumentException::class);
        $bucket->release(0);
    }

    public function testTokenBucketRejectsNegativeAcquire(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new RetryTokenBucket(10))->tryAcquire(-1);
    }

    /**
     * Build a strategy whose jitter is pinned to the full delay by default.
     */
    private function strategy(
        ?\Closure $random = null,
        mixed ...$args
    ): StandardRetryStrategy
    {
        return new StandardRetryStrategy(
            ...$args,
            random: $random ?? static fn(): float => 0.0
        );
    }

    private function serverError(): ApiException
    {
        return new ApiException('boom', ErrorFault::Server);
    }

    private function info(
        RetrySafety $safety,
        bool $throttle,
        ?float $retryAfter = null
    ): \RuntimeException&RetryInfoInterface
    {
        return new class($safety, $throttle, $retryAfter)
            extends \RuntimeException
            implements RetryInfoInterface {
            public function __construct(
                private readonly RetrySafety $safety,
                private readonly bool $throttle,
                private readonly ?float $retryAfter
            )
            {
                parent::__construct('info');
            }

            public function getRetrySafety(): RetrySafety
            {
                return $this->safety;
            }

            public function isThrottle(): bool
            {
                return $this->throttle;
            }

            public function getRetryAfter(): ?float
            {
                return $this->retryAfter;
            }
        };
    }
}
