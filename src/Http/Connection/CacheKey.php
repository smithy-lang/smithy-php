<?php

declare(strict_types=1);

namespace Smithy\Http\Connection;

/**
 * Identify a unique pooled connection destination.
 *
 * Connection pools are keyed by target URI and proxy URI so connections
 * through different proxies are not mixed.
 */
final class CacheKey implements \Stringable
{
    /**
     * Create connection pool cache key.
     */
    public function __construct(
        /** Target authority, such as "scheme://host:port". */
        public readonly string $target,

        /** Proxy URI, or null for direct connections. */
        public readonly ?string $proxy = null
    ) {}

    /**
     * Create from a PSR-7 URI and optional proxy URL.
     */
    public static function fromUri(
        \Psr\Http\Message\UriInterface $uri,
        ?string $proxy = null
    ): self
    {
        $scheme = $uri->getScheme() ?: 'https';
        $host = $uri->getHost();
        $port = $uri->getPort() ?? ($scheme === 'https' ? 443 : 80);

        return new self(
            target: "{$scheme}://{$host}:{$port}",
            proxy: $proxy
        );
    }

    /**
     * Render the cache key as a stable string.
     */
    public function __toString(): string
    {
        if ($this->proxy !== null) {
            return "{$this->target}|proxy={$this->proxy}";
        }

        return $this->target;
    }

    /**
     * Check whether another key targets the same connection.
     */
    public function equals(self $other): bool
    {
        return $this->target === $other->target
            && $this->proxy === $other->proxy;
    }
}
