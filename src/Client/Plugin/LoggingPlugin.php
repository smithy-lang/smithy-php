<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Client\Plugin;

use Smithy\Client\AbstractClientConfig;
use Smithy\Client\Interceptor\{
    LogContextProviderInterface,
    LoggingInterceptor
};

/**
 * Register a {@see LoggingInterceptor} for the config's PSR-3 logger.
 *
 * A client registers this plugin with default options when its config has
 * a logger. Register an instance with options to add context providers,
 * redact more headers, or log headers; the first registered instance is the
 * one applied. With no logger, the plugin leaves the config unchanged.
 *
 * ```php
 * $client = new WeatherClient(
 *     $config->withLogger($logger),
 *     new PluginCollection(new LoggingPlugin(
 *         contextProviders: [new RequestIdLogContextProvider()],
 *         redactedHeaders: ['X-Api-Key'],
 *         logHeaders: true
 *     ))
 * );
 * ```
 */
final class LoggingPlugin extends AbstractPlugin
{
    /**
     * @param list<LogContextProviderInterface> $contextProviders Add fields
     *        to every record.
     * @param list<string> $redactedHeaders Header names redacted in addition
     *        to {@see LoggingInterceptor::DEFAULT_REDACTED_HEADERS}.
     * @param bool $logHeaders Add redacted request and response headers to
     *        attempt records.
     */
    public function __construct(
        private readonly array $contextProviders = [],
        private readonly array $redactedHeaders = [],
        private readonly bool $logHeaders = false
    ) {}

    /** @inheritDoc */
    public function configure(AbstractClientConfig $config): AbstractClientConfig
    {
        if ($config->logger === null
            || $config->interceptorChain->contains(LoggingInterceptor::class)
        ) {
            return $config;
        }

        return $config->withInterceptor(new LoggingInterceptor(
            $config->logger,
            $this->contextProviders,
            $this->redactedHeaders,
            $this->logHeaders
        ));
    }

    /**
     * Run after other plugins so the interceptor observes their changes.
     */
    public function getPluginPhase(): PluginPhase
    {
        return PluginPhase::Last;
    }
}
