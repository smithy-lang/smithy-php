<?php

declare(strict_types=1);

namespace Smithy\Client\Plugin;

use Smithy\Client\ClientConfig;

/**
 * Register runtime behavior selected by operation schema traits.
 */
final class ModeledBehaviorPlugin extends AbstractPlugin
{
    private static ?self $instance = null;

    private function __construct() {}

    /**
     * Access the shared modeled-behavior plugin.
     */
    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    /** @inheritDoc */
    public function configure(ClientConfig $config): ClientConfig
    {
        return $config;
    }

    /** @inheritDoc */
    public function getChildPlugins(): array
    {
        return [
            new RequestCompressionPlugin(),
            new HttpChecksumPlugin()
        ];
    }
}
