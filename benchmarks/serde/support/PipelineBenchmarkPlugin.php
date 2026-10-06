<?php

declare(strict_types=1);

namespace Smithy\Benchmarks\Serde\Support;

use Smithy\Client\ClientConfig;
use Smithy\Client\Interceptor\AbstractInterceptor;
use Smithy\Client\Plugin\AbstractPlugin;

/**
 * Installs pass-through middleware for measuring interceptor dispatch.
 */
final class PipelineBenchmarkPlugin extends AbstractPlugin
{
    public function configure(ClientConfig $config): ClientConfig
    {
        return $config->withInterceptor(
            new PipelineBenchmarkInterceptor()
        );
    }
}

final class PipelineBenchmarkInterceptor extends AbstractInterceptor
{
    public function interceptCalls(): bool
    {
        return true;
    }
}
