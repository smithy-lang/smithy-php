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
    ExpiringIdentityFixture,
    ScriptedResolverFixture
};

/**
 * Replays the Credential Refresh SEP test suite against the caching resolver.
 *
 * `fixtures/credential-refresh-tests.json` is a verbatim copy of AwsDrSeps
 * `seps/accepted/shared/credential-refresh/credential-refresh-tests.json`.
 */
final class CachingIdentityResolverSuiteTest extends TestCase
{
    private const SEED_LIFETIME = 21600;

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function casesProvider(): iterable
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
    #[DataProvider('casesProvider')]
    public function testSuiteCase(array $case): void
    {
        $given = $case['given'];
        $clock = new ClockFixture();
        $inner = new ScriptedResolverFixture();
        $resolver = self::resolver(
            $inner,
            $clock,
            $given['configuredAdvisoryWindowSeconds'] ?? null,
            (float) ($given['refreshBackoffSeconds'] ?? 300)
        );

        $seed = null;
        if ($given['cachedCredentials'] !== 'none') {
            $seed = new ExpiringIdentityFixture(
                $given['accessKeyId'] ?? 'AKID-1',
                $clock->now + self::SEED_LIFETIME
            );
            $inner->push(IdentityResult::success($seed));
            $resolver->resolveIdentity();
            $clock->advance(match ($given['cachedCredentials']) {
                'valid' => 0,
                'advisory' => self::SEED_LIFETIME - 1800,
                'mandatory' => self::SEED_LIFETIME - 30,
                'expired' => self::SEED_LIFETIME + 60,
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
                    $this->runGetCredentials($step, $resolver, $inner, $clock, $seed, $case);
                    break;
            }
        }
    }

    /**
     * @param array<string, mixed> $step
     * @param array<string, mixed> $case
     */
    private function runGetCredentials(
        array $step,
        CachingIdentityResolver $resolver,
        ScriptedResolverFixture $inner,
        ClockFixture $clock,
        ?IdentityInterface $seed,
        array $case
    ): void {
        $inner->clear();
        $fresh = null;
        $lifetime = $step['lifetimeSeconds'] ?? self::SEED_LIFETIME;
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
            $this->assertAdvisoryWindow(
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
    private function assertAdvisoryWindow(
        int $window,
        int $lifetime,
        ?int $configuredWindow
    ): void {
        $clock = new ClockFixture();
        $inner = new ScriptedResolverFixture();
        $resolver = self::resolver($inner, $clock, $configuredWindow, 300.0);

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

    private static function resolver(
        ScriptedResolverFixture $inner,
        ClockFixture $clock,
        ?int $advisoryWindow,
        float $backoff
    ): CachingIdentityResolver {
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
}
