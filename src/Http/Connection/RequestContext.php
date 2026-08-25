<?php

declare(strict_types=1);

namespace Smithy\Http\Connection;

/**
 * Context for a connection: where it connects to.
 *
 * Captures the target host, port, scheme, and optional proxy
 * so that connection creation and pool keying have a single
 * source of truth.
 */
final class RequestContext
{
    /**
     * Create connection request context.
     */
    public function __construct(
        public readonly string $scheme,
        public readonly string $host,
        public readonly int $port,
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
        return new self(
            scheme: $uri->getScheme() ?: 'https',
            host: $uri->getHost(),
            port: $uri->getPort()
                ?? ($uri->getScheme() === 'https' ? 443 : 80),
            proxy: $proxy
        );
    }

    /**
     * Derive the CacheKey for pool lookups.
     */
    public function cacheKey(): CacheKey
    {
        return new CacheKey(
            target: "{$this->scheme}://{$this->host}:{$this->port}",
            proxy: $this->proxy
        );
    }
}
