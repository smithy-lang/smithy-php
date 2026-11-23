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
    AutoPluginInterface
};
use Smithy\Core\Context\ContextKey;

final class AutoPluginFixture extends AbstractPlugin implements
    AutoPluginInterface
{
    /** @var ContextKey<list<string>>|null */
    private static ?ContextKey $contextKey = null;

    public static int $constructionCount = 0;
    public static int $configurationCount = 0;

    public function __construct(
        private readonly string $name = 'auto'
    ) {
        ++self::$constructionCount;
    }

    /**
     * Key for the names of the auto plugins applied, in order.
     *
     * @return ContextKey<list<string>>
     */
    public static function contextKey(): ContextKey
    {
        return self::$contextKey ??= new ContextKey(
            'smithy.tests.auto_plugins'
        );
    }

    public function configure(AbstractClientConfig $config): AbstractClientConfig
    {
        ++self::$configurationCount;

        return $config->withContext(
            self::contextKey(),
            [
                ...$config->context->get(self::contextKey()) ?? [],
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
