<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Client\Plugin;

use Smithy\Client\AbstractClientConfig;
use Smithy\Client\Interceptor\IdempotencyTokenInterceptor;
use Smithy\Core\Context\ContextKey;

/**
 * Register idempotency-token autofill for modeled request inputs.
 */
final class IdempotencyTokenPlugin extends AbstractPlugin
{
    private static ?self $instance = null;

    /** @var ContextKey<string|callable(): string>|null */
    private static ?ContextKey $tokenProviderKey = null;

    private function __construct() {}

    /**
     * Key for the token provider: a fixed non-empty token or a callable
     * that returns one.
     *
     * @return ContextKey<string|callable(): string>
     */
    public static function tokenProviderKey(): ContextKey
    {
        return self::$tokenProviderKey ??= new ContextKey(
            'IdempotencyTokenProvider'
        );
    }

    /**
     * Access the shared plugin instance.
     */
    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * @inheritDoc
     *
     * @throws \InvalidArgumentException If the configured provider is invalid.
     */
    public function configure(AbstractClientConfig $config): AbstractClientConfig
    {
        if ($config->interceptorChain->contains(
            IdempotencyTokenInterceptor::class
        )) {
            return $config;
        }

        return $config->withInterceptor(
            new IdempotencyTokenInterceptor(
                $this->resolveTokenProvider($config)
            )
        );
    }

    private function resolveTokenProvider(AbstractClientConfig $config): ?\Closure
    {
        $provider = $config->context->get(self::tokenProviderKey());

        if ($provider === null) {
            return null;
        }

        if (\is_string($provider)) {
            if ($provider === '') {
                throw new \InvalidArgumentException(
                    'IdempotencyTokenProvider must not be an empty string.'
                );
            }

            return static fn(): string => $provider;
        }

        return \Closure::fromCallable($provider);
    }
}
