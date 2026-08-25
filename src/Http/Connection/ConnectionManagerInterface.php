<?php

declare(strict_types=1);

namespace Smithy\Http\Connection;

use Smithy\Async\CancellationInterface;
use Smithy\Async\CancelledException;
use Smithy\Http\Configuration\ConnectionManagerConfiguration;
use Smithy\Http\Configuration\ConnectConfiguration;
use Smithy\Http\Exception\ConnectionAcquireTimeoutException;

/**
 * Manage the lifecycle of HTTP connections.
 *
 * Leases protocol-agnostic exchanges and enforces connection concurrency.
 * Idle connections are pooled in FIFO order by CacheKey.
 *
 * @see Smithy Reference Architecture section 3.5.1, Connection Manager.
 */
interface ConnectionManagerInterface
{
    /**
     * Lease an Exchange for sending a request.
     *
     * Reuses an idle connection or creates one when capacity is available.
     * If the concurrency limit is reached, waits for capacity until the
     * acquisition timeout expires.
     *
     * @param RequestContext $requestContext Target host, port, scheme, proxy.
     * @param ConnectConfiguration $connectConfig Connection settings.
     * @param CancellationInterface|null $cancellation Cancellation signal.
     *
     * @return ExchangeInterface A ready-to-use exchange.
     *
     * @throws CancelledException If cancellation is requested.
     * @throws ConnectionAcquireTimeoutException On acquisition timeout.
     */
    public function lease(
        RequestContext $requestContext,
        ConnectConfiguration $connectConfig = new ConnectConfiguration(),
        ?CancellationInterface $cancellation = null
    ): ExchangeInterface;

    /**
     * Release an Exchange back to the pool.
     *
     * The underlying connection is returned to the FIFO pool for
     * reuse. If the connection is unhealthy or the pool is full,
     * it is closed instead.
     *
     * @return void
     */
    public function release(ExchangeInterface $exchange): void;

    /**
     * Remove and close connections that have been idle longer
     * than the idle timeout.
     *
     * @return void
     */
    public function prune(): void;

    /**
     * Close all managed connections and reset the pools.
     *
     * @return void
     */
    public function clear(): void;

    /**
     * Get the total number of idle connections.
     */
    public function idleCount(): int;

    /**
     * Get the connection manager configuration.
     */
    public function getConfiguration(): ConnectionManagerConfiguration;
}
