<?php

declare(strict_types=1);

namespace Smithy\Client\Auth;

/**
 * Cache the identity produced by an inner
 * {@see IdentityResolverInterface} across calls.
 *
 * Wrapping an ambient credential source in this decorator turns a
 * per-request `resolveIdentity` call into a cached read for the lifetime of
 * the identity's validity window.
 *
 * The refresh policy is deliberately simple: an identity is considered
 * fresh while its {@see IdentityInterface::getExpirationTime()} is
 * further than `$refreshWindowSeconds` in the future, or when it never
 * expires. An identity that has passed its refresh window (or expired
 * entirely) triggers one call into the inner resolver and the returned
 * identity is cached in its place. Errors from the inner resolver
 * propagate to the caller; the cache is not populated with a failure
 * sentinel.
 *
 * The decorator is not thread-safe. PHP-FPM and CLI clients are
 * single-threaded per request, so the flat `$cached` field is enough.
 * Long-lived shared processes with multiple concurrent workers should
 * either instantiate one client per worker (the current recommended
 * pattern) or slot a coroutine-aware resolver in place of this class.
 *
 * @see IdentityResolverInterface
 */
final class CachingIdentityResolver implements IdentityResolverInterface
{
    /**
     * Cached identity from the inner resolver's most recent successful
     * call, or null when the cache is cold.
     */
    private ?IdentityInterface $cached = null;

    /**
     * @param IdentityResolverInterface $inner Inner resolver that produces
     *                                         identities when the cache is
     *                                         cold or stale.
     * @param int $refreshWindowSeconds Skew allowance before expiration.
     *            The resolver refreshes this many seconds early so
     *            in-flight signed requests do not cross the hard-expiry
     *            boundary. Defaults to 300 seconds.
     */
    public function __construct(
        private readonly IdentityResolverInterface $inner,
        private readonly int $refreshWindowSeconds = 300
    ) {}

    /** @inheritDoc */
    public function resolveIdentity(array $properties = []): IdentityInterface
    {
        if ($this->cached !== null && !$this->requiresRefresh($this->cached)) {
            return $this->cached;
        }
        return $this->cached = $this->inner->resolveIdentity($properties);
    }

    /**
     * Return the wrapped resolver.
     */
    public function getInner(): IdentityResolverInterface
    {
        return $this->inner;
    }

    private function requiresRefresh(IdentityInterface $identity): bool
    {
        $expiry = $identity->getExpirationTime();
        if ($expiry === null) {
            return false;
        }

        return $expiry - \microtime(true) <= $this->refreshWindowSeconds;
    }
}
