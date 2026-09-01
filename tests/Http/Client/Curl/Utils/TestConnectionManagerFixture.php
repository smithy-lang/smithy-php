<?php

declare(strict_types=1);

namespace Smithy\Tests\Http\Client\Curl\Utils;

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

final class TestConnectionManagerFixture implements ConnectionManagerInterface
{
    public int $releaseCount = 0;
    private readonly \Closure $lease;
    private readonly ?\Closure $release;
    private readonly ConnectionManagerConfiguration $configuration;

    public function __construct(
        \Closure $lease,
        ?\Closure $release = null,
        ?ConnectionManagerConfiguration $configuration = null
    )
    {
        $this->lease = $lease;
        $this->release = $release;
        $this->configuration = $configuration
            ?? new ConnectionManagerConfiguration();
    }

    public function lease(
        RequestContext $requestContext,
        ConnectConfiguration $connectConfig = new ConnectConfiguration(),
        ?CancellationInterface $cancellation = null
    ): ExchangeInterface
    {
        return ($this->lease)(
            $requestContext,
            $connectConfig,
            $cancellation
        );
    }

    public function release(ExchangeInterface $exchange): void
    {
        ++$this->releaseCount;
        ($this->release)?->__invoke($exchange);
    }

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
