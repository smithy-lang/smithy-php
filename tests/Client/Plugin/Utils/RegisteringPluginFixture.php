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
    PluginInterface
};

/**
 * Record application and register another plugin during configuration.
 */
final class RegisteringPluginFixture extends AbstractPlugin
{
    /** @param \ArrayObject<int, string> $calls */
    public function __construct(
        private readonly string $name,
        private readonly \ArrayObject $calls,
        private readonly PluginInterface $plugin
    ) {}

    public function configure(ClientConfig $config): ClientConfig
    {
        $this->calls->append($this->name);

        return $config->withPlugin($this->plugin);
    }
}
