<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Auth;

use PHPUnit\Framework\TestCase;
use Smithy\Client\Auth\{
    CachingIdentityResolver,
    IdentityInterface,
    IdentityResult
};
use Smithy\Tests\Client\Auth\Utils\{
    CountingResolverFixture,
    FlakyResolverFixture,
    SequenceResolverFixture
};
final class CachingIdentityResolverTest extends TestCase
{
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
