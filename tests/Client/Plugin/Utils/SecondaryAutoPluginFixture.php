<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Plugin\Utils;

use Smithy\Client\AbstractClientConfig;
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

    public function configure(AbstractClientConfig $config): AbstractClientConfig
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
