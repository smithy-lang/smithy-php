<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Client\Plugin;

use Smithy\Client\AbstractClientConfig;
use Smithy\Client\Interceptor\RequestCompressionInterceptor;
use Smithy\Core\Context\ContextKey;

/**
 * Register modeled request compression.
 */
final class RequestCompressionPlugin extends AbstractPlugin
{
    public const DEFAULT_MIN_COMPRESSION_SIZE_BYTES = 10240;
    public const MAX_MIN_COMPRESSION_SIZE_BYTES = 10485760;

    /** @var ContextKey<bool|string>|null */
    private static ?ContextKey $disableRequestCompressionKey = null;

    /** @var ContextKey<int|string>|null */
    private static ?ContextKey $minCompressionSizeBytesKey = null;

    /**
     * @param bool|null $disabled Override the configured disable setting.
     * @param int|null $minCompressionSizeBytes Override the minimum body size.
     */
    public function __construct(
        private readonly ?bool $disabled = null,
        private readonly ?int $minCompressionSizeBytes = null
    ) {}

    /**
     * Key for the disable setting: a bool, or `"true"` / `"false"` as read
     * from a configuration source.
     *
     * @return ContextKey<bool|string>
     */
    public static function disableRequestCompressionKey(): ContextKey
    {
        return self::$disableRequestCompressionKey ??= new ContextKey(
            'DisableRequestCompression'
        );
    }

    /**
     * Key for the minimum body size to compress: an int, or a string of
     * digits as read from a configuration source.
     *
     * @return ContextKey<int|string>
     */
    public static function minCompressionSizeBytesKey(): ContextKey
    {
        return self::$minCompressionSizeBytesKey ??= new ContextKey(
            'RequestMinCompressionSizeBytes'
        );
    }

    /**
     * @inheritDoc
     *
     * @throws \InvalidArgumentException If compression settings are invalid.
     */
    public function configure(AbstractClientConfig $config): AbstractClientConfig
    {
        return $config->withInterceptor(
            new RequestCompressionInterceptor(
                disabled: $this->resolveDisableRequestCompression($config),
                minCompressionSizeBytes:
                    $this->resolveRequestMinCompressionSizeBytes($config)
            )
        );
    }

    /** @inheritDoc */
    public function getPluginPhase(): PluginPhase
    {
        return PluginPhase::BeforeApply;
    }

    private function resolveDisableRequestCompression(
        AbstractClientConfig $config
    ): bool
    {
        if ($this->disabled !== null) {
            return $this->disabled;
        }

        $value = $config->context->get(self::disableRequestCompressionKey());
        if ($value === null) {
            return false;
        }

        if ($value === true || $value === 'true') {
            return true;
        }

        if ($value === false || $value === 'false') {
            return false;
        }

        throw new \InvalidArgumentException(
            'DisableRequestCompression must be true or false.'
        );
    }

    private function resolveRequestMinCompressionSizeBytes(
        AbstractClientConfig $config
    ): int
    {
        $value = $this->minCompressionSizeBytes
            ?? $config->context->get(self::minCompressionSizeBytesKey())
            ?? self::DEFAULT_MIN_COMPRESSION_SIZE_BYTES;

        if (!\is_int($value)) {
            if ($value === '' || !\ctype_digit($value)) {
                throw new \InvalidArgumentException(
                    'RequestMinCompressionSizeBytes must be an integer'
                    . ' between 0 and '
                    . self::MAX_MIN_COMPRESSION_SIZE_BYTES
                    . '.'
                );
            }

            $value = (int) $value;
        }

        if ($value < 0 || $value > self::MAX_MIN_COMPRESSION_SIZE_BYTES) {
            throw new \InvalidArgumentException(
                'RequestMinCompressionSizeBytes must be an integer'
                . ' between 0 and '
                . self::MAX_MIN_COMPRESSION_SIZE_BYTES
                . '.'
            );
        }

        return $value;
    }
}
