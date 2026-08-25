<?php

declare(strict_types=1);

namespace Smithy\Http\Connection;

/**
 * FIFO pool of idle HTTP connections.
 *
 * The SRA explicitly requires FIFO ordering (section 3.5.2.2):
 *  - LIFO causes imbalanced load: most requests hit recently used connections.
 *  - LIFO leaves old connections idle until the server closes them.
 *  - FIFO rotates through all connections, keeping them alive and
 *    distributing load evenly, preventing mass reconnection storms.
 *
 * @see Smithy Reference Architecture section 3.5.1, ConnectionPool.
 */
interface ConnectionPoolInterface
{
    /**
     * Retrieve and remove the first (oldest) connection.
     *
     * @return HttpConnectionInterface|null The oldest idle connection.
     */
    public function getFirst(): ?HttpConnectionInterface;

    /**
     * Add a connection to the back (newest end) of the queue.
     *
     * @return void
     */
    public function offerLast(HttpConnectionInterface $connection): void;

    /**
     * Remove a specific connection from the pool.
     *
     * @return bool True if the connection was found and removed.
     */
    public function remove(HttpConnectionInterface $connection): bool;

    /**
     * Number of connections in the pool.
     */
    public function count(): int;

    /**
     * Whether the pool is empty.
     */
    public function isEmpty(): bool;

    /**
     * Remove and close connections that have been idle longer
     * than the given timeout.
     *
     * @param float $idleTimeout Idle timeout in seconds.
     * @return int Number of connections pruned.
     */
    public function prune(float $idleTimeout): int;

    /**
     * Close all connections and empty the pool.
     *
     * @return void
     */
    public function clear(): void;
}
