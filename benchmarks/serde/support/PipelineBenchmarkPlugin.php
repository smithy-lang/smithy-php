<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Benchmarks\Serde\Support;

use Smithy\Client\AbstractClientConfig;
use Smithy\Client\Interceptor\AbstractInterceptor;
use Smithy\Client\Plugin\AbstractPlugin;

/**
 * Installs pass-through middleware for measuring interceptor dispatch.
 */
final class PipelineBenchmarkPlugin extends AbstractPlugin
{
    public function configure(AbstractClientConfig $config): AbstractClientConfig
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
