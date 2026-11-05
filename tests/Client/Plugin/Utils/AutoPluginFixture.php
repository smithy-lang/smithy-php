<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Plugin\Utils;

use Smithy\Client\ClientConfig;
use Smithy\Client\Plugin\{
    AbstractPlugin,
    AutoPluginInterface
};

final class AutoPluginFixture extends AbstractPlugin implements
    AutoPluginInterface
{
    public const CONTEXT_KEY = 'smithy.tests.auto_plugins';

    public static int $constructionCount = 0;
    public static int $configurationCount = 0;

    public function __construct(
        private readonly string $name = 'auto'
    ) {
        ++self::$constructionCount;
    }

    public function configure(ClientConfig $config): ClientConfig
    {
        ++self::$configurationCount;

        return $config->withContext(
            self::CONTEXT_KEY,
            [
                ...($config->context[self::CONTEXT_KEY] ?? []),
                $this->name
            ]
        );
    }

    public static function reset(): void
    {
        self::$constructionCount = 0;
        self::$configurationCount = 0;
    }
}
