<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Plugin\Utils;

use Smithy\Client\ClientConfig;
use Smithy\Client\Plugin\{
    AbstractPlugin,
    AutoPluginInterface,
    PluginPhase
};

final class SecondaryAutoPluginFixture extends AbstractPlugin implements
    AutoPluginInterface
{
    public static int $constructionCount = 0;
    public static int $configurationCount = 0;

    public function __construct()
    {
        ++self::$constructionCount;
    }

    public function configure(ClientConfig $config): ClientConfig
    {
        ++self::$configurationCount;

        return $config->withContext(
            AutoPluginFixture::CONTEXT_KEY,
            [
                ...(
                    $config->context[AutoPluginFixture::CONTEXT_KEY]
                    ?? []
                ),
                'secondary'
            ]
        );
    }

    public function getPluginPhase(): PluginPhase
    {
        return PluginPhase::BeforeApply;
    }

    public static function reset(): void
    {
        self::$constructionCount = 0;
        self::$configurationCount = 0;
    }
}
