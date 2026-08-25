<?php

declare(strict_types=1);

namespace Smithy\Http\Connection;

use Smithy\Http\Configuration\ConnectConfiguration;

/**
 * Create new HTTP connections.
 *
 * Encapsulates connection establishment configuration. The
 * ConnectionManager delegates to this factory when the pool
 * has no idle connections.
 *
 * @see Smithy Reference Architecture section 3.5.1, ConnectionFactory.
 */
interface ConnectionFactoryInterface
{
    /**
     * Create a new connection to the given target.
     *
     * @param RequestContext $requestContext Target host, port, scheme, proxy.
     * @param ConnectConfiguration $config Connect timeout, TLS, HTTP version.
     *
     * @return HttpConnectionInterface A connection ready for use.
     */
    public function create(
        RequestContext $requestContext,
        ConnectConfiguration $config
    ): HttpConnectionInterface;
}
