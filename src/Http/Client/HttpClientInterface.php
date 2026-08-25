<?php

declare(strict_types=1);

namespace Smithy\Http\Client;

use Psr\Http\Message\RequestInterface;
use Smithy\Http\Connection\ConnectionManagerInterface;
use Smithy\Http\Message\ResponseInterface;

/**
 * Define transport-agnostic HTTP client operations.
 *
 * Uses PSR-7 requests and Smithy responses at the transport boundary. Calls
 * inside fibers suspend during I/O.
 *
 * @see Smithy Reference Architecture section 3.5.1, HTTP Client.
 */
interface HttpClientInterface
{
    /**
     * Send a single HTTP request and return the response.
     *
     * Calls outside a fiber block until the response arrives. Calls inside a
     * fiber suspend during I/O.
     *
     * @param RequestInterface $request  PSR-7 request to send.
     * @param HttpRequestOptions|null $options Per-request transport options.
     *
     * @return ResponseInterface Smithy HTTP response.
     *
     * @throws HttpException On transport-level failures (connection
     *                       refused, timeout, DNS resolution, etc.).
     * @throws ConnectionAcquireTimeoutException If no connection
     *                                           becomes available within
     *                                           the configured timeout.
     */
    public function send(
        RequestInterface $request,
        ?HttpRequestOptions $options = null
    ): ResponseInterface;

    /**
     * Send multiple HTTP requests concurrently.
     *
     * Each request runs in its own fiber. All requests share the
     * same transport and interleave during I/O. Blocks until all
     * requests have completed.
     *
     * @param array<RequestInterface> $requests PSR-7 requests to send.
     * @param HttpRequestOptions|null $options Transport options applied to
     *                                         all requests.
     *
     * @return array<ResponseInterface> Responses in input order.
     *
     * @throws HttpException On transport-level failures for any request.
     */
    public function sendMulti(
        array $requests,
        ?HttpRequestOptions $options = null
    ): array;

    /**
     * Access the connection manager used by this client.
     *
     * Exposes connection pool metrics and allows external pruning
     * in long-running processes.
     */
    public function getConnectionManager(): ConnectionManagerInterface;
}
