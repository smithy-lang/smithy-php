<?php

declare(strict_types=1);

namespace Smithy\Benchmarks\Serde\Support;

use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface
};
use Smithy\Http\Client\Cancellation\CancellationInterface;
use Smithy\Http\Client\Configuration\{
    ConnectionManagerConfiguration,
    ConnectConfiguration
};
use Smithy\Http\Client\Connection\{
    ConnectionManagerInterface,
    ExchangeInterface,
    RequestContext
};
use Smithy\Http\Client\{
    HttpClientInterface,
    HttpRequestOptions
};

/**
 * Returns a prepared response without performing network I/O.
 */
final class FixedResponseHttpClient implements HttpClientInterface
{
    private readonly HttpRequestOptions $defaultOptions;
    private readonly ConnectionManagerInterface $connectionManager;

    public function __construct(
        private readonly ResponseInterface $response
    ) {
        $this->defaultOptions = new HttpRequestOptions();
        $this->connectionManager = new NoOpConnectionManager();
    }

    public function getDefaultRequestOptions(): HttpRequestOptions
    {
        return $this->defaultOptions;
    }

    public function sendRequest(
        RequestInterface $request,
        ?HttpRequestOptions $options = null
    ): ResponseInterface {
        $this->response->getBody()->rewind();

        return $this->response;
    }

    public function getConnectionManager(): ConnectionManagerInterface
    {
        return $this->connectionManager;
    }

    public function close(): void {}
}

/**
 * Satisfies the transport contract for an HTTP client that never connects.
 */
final class NoOpConnectionManager implements ConnectionManagerInterface
{
    private readonly ConnectionManagerConfiguration $configuration;

    public function __construct()
    {
        $this->configuration = new ConnectionManagerConfiguration();
    }

    public function lease(
        RequestContext $requestContext,
        ConnectConfiguration $connectConfig = new ConnectConfiguration(),
        ?CancellationInterface $cancellation = null
    ): ExchangeInterface {
        throw new \BadMethodCallException(
            'The in-memory benchmark client does not lease connections.'
        );
    }

    public function release(ExchangeInterface $exchange): void {}

    public function prune(): void {}

    public function clear(): void {}

    public function idleCount(): int
    {
        return 0;
    }

    public function getConfiguration(): ConnectionManagerConfiguration
    {
        return $this->configuration;
    }
}
