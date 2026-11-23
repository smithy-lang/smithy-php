<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Auth;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Auth\{
    CachingIdentityResolver,
    IdentityInterface,
    IdentityResult,
    NonRecoverableIdentityException
};
use Smithy\Tests\Auth\Utils\{
    ClockFixture,
    CountingResolverFixture,
    ExpiringIdentityFixture,
    FlakyResolverFixture,
    ReentrantResolverFixture,
    ScriptedResolverFixture,
    SequenceResolverFixture
};
final class CachingIdentityResolverTest extends TestCase
{
    private const SPECIFICATION_SEED_LIFETIME = 21600;

    public function testFirstCallDelegatesToInner(): void
    {
        $identity = self::identityExpiring(null);
        $inner = new CountingResolverFixture($identity);
        $resolver = new CachingIdentityResolver($inner);

        $this->assertSame($identity, $resolver->resolveIdentity()->unwrap());
        $this->assertSame(1, $inner->calls);
    }

    public function testSubsequentCallsReturnCachedIdentity(): void
    {
        $identity = self::identityExpiring(null);
        $inner = new CountingResolverFixture($identity);
        $resolver = new CachingIdentityResolver($inner);

        for ($i = 0; $i < 5; $i++) {
            $this->assertSame(
                $identity,
                $resolver->resolveIdentity()->unwrap()
            );
        }
        $this->assertSame(1, $inner->calls);
    }

    public function testDifferentPropertiesBypassCachedIdentity(): void
    {
        $first = self::identityExpiring(null);
        $second = self::identityExpiring(null);
        $inner = new SequenceResolverFixture([$first, $second]);
        $resolver = new CachingIdentityResolver($inner);

        $this->assertSame($first, $resolver->resolveIdentity([
            'scope' => 'one'
        ])->unwrap());
        $this->assertSame($first, $resolver->resolveIdentity([
            'scope' => 'one'
        ])->unwrap());
        $this->assertSame($second, $resolver->resolveIdentity([
            'scope' => 'two'
        ])->unwrap());
        $this->assertSame($second, $resolver->resolveIdentity([
            'scope' => 'two'
        ])->unwrap());
        $this->assertSame(2, $inner->calls);
    }

    public function testExpiredIdentityTriggersRefresh(): void
    {
        $expired = self::identityExpiring(\microtime(true) - 60);
        $fresh = self::identityExpiring(\microtime(true) + 3600);
        $inner = new SequenceResolverFixture([$expired, $fresh]);
        $resolver = new CachingIdentityResolver($inner);

        // First resolve caches the already-expired identity, second sees
        // the refresh window has been passed and re-enters the inner.
        $this->assertSame($expired, $resolver->resolveIdentity()->unwrap());
        $this->assertSame($fresh, $resolver->resolveIdentity()->unwrap());
        $this->assertSame(2, $inner->calls);
    }

    public function testIdentityInsideRefreshWindowTriggersRefresh(): void
    {
        // Expires 10 seconds from now; default refresh window is 300s, so
        // the resolver should treat this as needing refresh on every call.
        $expiring = self::identityExpiring(\microtime(true) + 10);
        $fresh = self::identityExpiring(\microtime(true) + 3600);
        $inner = new SequenceResolverFixture([$expiring, $fresh, $fresh]);
        $resolver = new CachingIdentityResolver($inner);

        $this->assertSame($expiring, $resolver->resolveIdentity()->unwrap());
        $this->assertSame($fresh, $resolver->resolveIdentity()->unwrap());
        $this->assertSame($fresh, $resolver->resolveIdentity()->unwrap());
        $this->assertSame(2, $inner->calls);
    }

    public function testIdentityAtRefreshWindowBoundaryTriggersRefresh(): void
    {
        $atBoundary = self::identityExpiring(\microtime(true) + 300);
        $fresh = self::identityExpiring(\microtime(true) + 3600);
        $inner = new SequenceResolverFixture([$atBoundary, $fresh]);
        $resolver = new CachingIdentityResolver($inner);

        $this->assertSame(
            $atBoundary,
            $resolver->resolveIdentity()->unwrap()
        );
        $this->assertSame($fresh, $resolver->resolveIdentity()->unwrap());
        $this->assertSame(2, $inner->calls);
    }

    public function testCustomRefreshWindow(): void
    {
        // Identity expires in 100s. Refresh window of 30s means the
        // identity stays fresh for 70s of wall time; on this synchronous
        // test all calls happen within that window so a single resolve.
        $identity = self::identityExpiring(\microtime(true) + 100);
        $inner = new CountingResolverFixture($identity);
        $resolver = new CachingIdentityResolver(
            $inner,
            refreshWindowSeconds: 30
        );

        $resolver->resolveIdentity();
        $resolver->resolveIdentity();
        $this->assertSame(1, $inner->calls);
    }

    public function testRejectsNegativeRefreshWindow(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Refresh window must be non-negative.'
        );

        new CachingIdentityResolver(
            new CountingResolverFixture(self::identityExpiring(null)),
            refreshWindowSeconds: -1
        );
    }

    public function testGetInnerReturnsWrappedResolver(): void
    {
        $inner = new CountingResolverFixture(self::identityExpiring(null));
        $resolver = new CachingIdentityResolver($inner);
        $this->assertSame($inner, $resolver->getInner());
    }

    public function testMatchingInvalidationRefreshesCachedIdentity(): void
    {
        $initial = self::identityExpiring(null);
        $fresh = self::identityExpiring(null);
        $inner = new SequenceResolverFixture([$initial, $fresh]);
        $resolver = new CachingIdentityResolver($inner);

        $this->assertSame($initial, $resolver->resolveIdentity()->unwrap());
        $resolver->invalidate($initial);

        $this->assertSame($fresh, $resolver->resolveIdentity()->unwrap());
        $this->assertSame(2, $inner->calls);
        $this->assertSame([$initial], $inner->invalidated);
    }

    public function testStaleInvalidationPreservesCachedIdentity(): void
    {
        $current = self::identityExpiring(null);
        $stale = self::identityExpiring(null);
        $inner = new CountingResolverFixture($current);
        $resolver = new CachingIdentityResolver($inner);

        $this->assertSame($current, $resolver->resolveIdentity()->unwrap());
        $resolver->invalidate($stale);

        $this->assertSame($current, $resolver->resolveIdentity()->unwrap());
        $this->assertSame(1, $inner->calls);
        $this->assertSame([$stale], $inner->invalidated);
    }

    public function testCustomMatcherInvalidatesEquivalentIdentity(): void
    {
        $initial = self::identityExpiring(null);
        $equivalent = self::identityExpiring(null);
        $fresh = self::identityExpiring(null);
        $inner = new SequenceResolverFixture([$initial, $fresh]);
        $resolver = new CachingIdentityResolver(
            $inner,
            identityMatcher: static fn(
                IdentityInterface $cached,
                IdentityInterface $rejected
            ): bool => $cached->getExpirationTime()
                === $rejected->getExpirationTime()
        );

        $this->assertSame($initial, $resolver->resolveIdentity()->unwrap());
        $resolver->invalidate($equivalent);

        $this->assertSame($fresh, $resolver->resolveIdentity()->unwrap());
        $this->assertSame(2, $inner->calls);
    }

    public function testInnerThrowIsNotCached(): void
    {
        $identity = self::identityExpiring(null);
        $inner = new FlakyResolverFixture([
            new \RuntimeException('boom'),
            $identity
        ]);
        $resolver = new CachingIdentityResolver($inner);

        try {
            $resolver->resolveIdentity();
            $this->fail('expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertSame('boom', $e->getMessage());
        }

        // Cache stayed cold; second call goes back into the inner and
        // succeeds this time.
        $this->assertSame($identity, $resolver->resolveIdentity()->unwrap());
        $this->assertSame(2, $inner->calls);
    }

    public function testExpectedMissIsNotCached(): void
    {
        $identity = self::identityExpiring(null);
        $inner = new FlakyResolverFixture([
            IdentityResult::notFound(self::class, 'not configured'),
            $identity
        ]);
        $resolver = new CachingIdentityResolver($inner);

        $miss = $resolver->resolveIdentity();
        $resolved = $resolver->resolveIdentity();

        $this->assertNull($miss->identity);
        $this->assertSame($identity, $resolved->unwrap());
        $this->assertSame(2, $inner->calls);
    }

    public function testReentrantCallDuringRefreshReturnsCachedIdentity(): void
    {
        $clock = new ClockFixture();
        $identity = new ExpiringIdentityFixture('AKID-1', $clock->now + 30);
        $inner = new ReentrantResolverFixture($identity);
        $resolver = new CachingIdentityResolver($inner, clock: $clock);

        $resolver->resolveIdentity();
        $inner->caller = $resolver;
        $resolver->resolveIdentity();

        $this->assertSame(2, $inner->calls);
        $this->assertSame($identity, $inner->reentrantResult?->unwrap());
    }

    public function testRefreshWithoutIdentityKeepsCachedIdentityAndBacksOff(): void
    {
        $clock = new ClockFixture();
        $cached = new ExpiringIdentityFixture('AKID-1', $clock->now + 30);
        $inner = new ScriptedResolverFixture();
        $resolver = new CachingIdentityResolver($inner, clock: $clock);

        $inner->push(IdentityResult::success($cached));
        $resolver->resolveIdentity();
        $inner->push(IdentityResult::notFound(self::class, 'gone'));

        $this->assertSame($cached, $resolver->resolveIdentity()->unwrap());
        $this->assertSame($cached, $resolver->resolveIdentity()->unwrap());
        $this->assertSame(2, $inner->calls);
    }

    public function testDefaultBackoffLastsBetween300And600Seconds(): void
    {
        $clock = new ClockFixture();
        $cached = new ExpiringIdentityFixture('AKID-1', $clock->now + 30);
        $inner = new ScriptedResolverFixture();
        $resolver = new CachingIdentityResolver($inner, clock: $clock);

        $inner->push(IdentityResult::success($cached));
        $resolver->resolveIdentity();
        $inner->push(new \RuntimeException('down'));
        $resolver->resolveIdentity();

        $clock->advance(299);
        $resolver->resolveIdentity();
        $this->assertSame(2, $inner->calls);

        $inner->push(new \RuntimeException('down'));
        $clock->advance(302);
        $resolver->resolveIdentity();
        $this->assertSame(3, $inner->calls);
    }

    public function testDefaultErrorCacheLastsBetween1And5Seconds(): void
    {
        $clock = new ClockFixture();
        $cached = new ExpiringIdentityFixture('AKID-1', $clock->now + 30);
        $inner = new ScriptedResolverFixture();
        $resolver = new CachingIdentityResolver($inner, clock: $clock);

        $inner->push(IdentityResult::success($cached));
        $resolver->resolveIdentity();
        $inner->push(new NonRecoverableIdentityException('sign in'));
        try {
            $resolver->resolveIdentity();
            $this->fail('expected exception was not thrown');
        } catch (NonRecoverableIdentityException) {
        }

        $clock->advance(0.5);
        try {
            $resolver->resolveIdentity();
            $this->fail('expected exception was not thrown');
        } catch (NonRecoverableIdentityException) {
        }
        $this->assertSame(2, $inner->calls);

        $inner->push(IdentityResult::success($cached));
        $clock->advance(5.6);
        $resolver->resolveIdentity();
        $this->assertSame(3, $inner->calls);
    }

    /**
     * Credential refresh specification test cases from
     * `fixtures/credential-refresh-tests.json`.
     *
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function specificationCasesProvider(): iterable
    {
        $cases = \json_decode(
            (string) \file_get_contents(
                __DIR__ . '/fixtures/credential-refresh-tests.json'
            ),
            true,
            flags: \JSON_THROW_ON_ERROR
        );

        foreach ($cases as $i => $case) {
            yield \sprintf('%02d %s', $i + 1, $case['documentation']) => [$case];
        }
    }

    /** @param array<string, mixed> $case */
    #[DataProvider('specificationCasesProvider')]
    public function testSpecificationCase(array $case): void
    {
        $given = $case['given'];
        $clock = new ClockFixture();
        $inner = new ScriptedResolverFixture();
        $resolver = self::specificationResolver(
            $inner,
            $clock,
            $given['configuredAdvisoryWindowSeconds'] ?? null,
            (float) ($given['refreshBackoffSeconds'] ?? 300)
        );

        $seed = null;
        if ($given['cachedCredentials'] !== 'none') {
            $seed = new ExpiringIdentityFixture(
                $given['accessKeyId'] ?? 'AKID-1',
                $clock->now + self::SPECIFICATION_SEED_LIFETIME
            );
            $inner->push(IdentityResult::success($seed));
            $resolver->resolveIdentity();
            $clock->advance(match ($given['cachedCredentials']) {
                'valid' => 0,
                'advisory' => self::SPECIFICATION_SEED_LIFETIME - 1800,
                'mandatory' => self::SPECIFICATION_SEED_LIFETIME - 30,
                'expired' => self::SPECIFICATION_SEED_LIFETIME + 60,
            });
        }

        foreach ($case['steps'] as $step) {
            switch ($step['type']) {
                case 'advanceTime':
                    $clock->advance($step['seconds']);
                    break;
                case 'invalidate':
                    $resolver->invalidate(new ExpiringIdentityFixture(
                        $step['rejectedAccessKeyId'],
                        null
                    ));
                    break;
                case 'getCredentials':
                    $this->runSpecificationGetCredentials(
                        $step,
                        $resolver,
                        $inner,
                        $clock,
                        $seed,
                        $case
                    );
                    break;
            }
        }
    }

    /**
     * @param array<string, mixed> $step
     * @param array<string, mixed> $case
     */
    private function runSpecificationGetCredentials(
        array $step,
        CachingIdentityResolver $resolver,
        ScriptedResolverFixture $inner,
        ClockFixture $clock,
        ?IdentityInterface $seed,
        array $case
    ): void
    {
        $inner->clear();
        $fresh = null;
        $lifetime = $step['lifetimeSeconds'] ?? self::SPECIFICATION_SEED_LIFETIME;
        switch ($step['response'] ?? null) {
            case 'freshCredentials':
                $fresh = new ExpiringIdentityFixture(
                    'AKID-NEW',
                    $clock->now + $lifetime
                );
                $inner->push(IdentityResult::success($fresh));
                break;
            case 'staleCredentials':
                $inner->push(IdentityResult::success(
                    new ExpiringIdentityFixture('AKID-STALE', $clock->now)
                ));
                break;
            case 'error':
                $inner->push(new \RuntimeException('source unavailable'));
                break;
            case 'nonRecoverableError':
                $inner->push(new NonRecoverableIdentityException('sign in again'));
                break;
        }

        $before = $inner->calls;
        $expected = $step['expected'];
        $error = null;
        $result = null;
        try {
            $result = $resolver->resolveIdentity()->unwrap();
        } catch (\Throwable $e) {
            $error = $e;
        }

        switch ($expected['result']) {
            case 'cachedCredentials':
                $this->assertNull($error);
                $this->assertSame($seed, $result);
                break;
            case 'newCredentials':
                $this->assertNull($error);
                $this->assertSame($fresh, $result);
                break;
            case 'noCredentialsError':
                $this->assertInstanceOf(\RuntimeException::class, $error);
                $this->assertNotInstanceOf(
                    NonRecoverableIdentityException::class,
                    $error
                );
                break;
            case 'nonRecoverableError':
                $this->assertInstanceOf(
                    NonRecoverableIdentityException::class,
                    $error
                );
                break;
        }

        $this->assertSame(
            $expected['sourceContacted'],
            $inner->calls > $before
        );
        if ($expected['rateLimited']) {
            $this->assertSame($before, $inner->calls);
        }
        if (isset($expected['advisoryWindowSeconds'])) {
            $this->assertSpecificationAdvisoryWindow(
                $expected['advisoryWindowSeconds'],
                $lifetime,
                $case['given']['configuredAdvisoryWindowSeconds'] ?? null
            );
        }
    }

    /**
     * Fetch an identity with the given lifetime on a fresh resolver and check
     * that it refreshes once exactly `$window` seconds remain.
     */
    private function assertSpecificationAdvisoryWindow(
        int $window,
        int $lifetime,
        ?int $configuredWindow
    ): void
    {
        $clock = new ClockFixture();
        $inner = new ScriptedResolverFixture();
        $resolver = self::specificationResolver($inner, $clock, $configuredWindow, 300.0);

        $inner->push(IdentityResult::success(
            new ExpiringIdentityFixture('AKID-PROBE', $clock->now + $lifetime)
        ));
        $resolver->resolveIdentity();

        $clock->advance($lifetime - $window - 1);
        $resolver->resolveIdentity();
        $this->assertSame(1, $inner->calls, 'refreshed before the window opened');

        $inner->push(IdentityResult::success(
            new ExpiringIdentityFixture('AKID-PROBE-2', $clock->now + $lifetime)
        ));
        $clock->advance(1);
        $resolver->resolveIdentity();
        $this->assertSame(2, $inner->calls, 'did not refresh inside the window');
    }

    private static function specificationResolver(
        ScriptedResolverFixture $inner,
        ClockFixture $clock,
        ?int $advisoryWindow,
        float $backoff
    ): CachingIdentityResolver
    {
        return new CachingIdentityResolver(
            $inner,
            refreshWindowSeconds: $advisoryWindow,
            identityMatcher: static fn(
                ExpiringIdentityFixture $cached,
                ExpiringIdentityFixture $rejected
            ): bool => $cached->accessKeyId === $rejected->accessKeyId,
            clock: $clock,
            backoffJitter: static fn(): float => $backoff,
            errorCacheJitter: static fn(): float => 5.0
        );
    }

    private static function identityExpiring(
        ?float $expiration
    ): IdentityInterface
    {
        return new class($expiration) implements IdentityInterface {
            public function __construct(private readonly ?float $expiration) {}

            public function getExpirationTime(): ?float
            {
                return $this->expiration;
            }
        };
    }
}
