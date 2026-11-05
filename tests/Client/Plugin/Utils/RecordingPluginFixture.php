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
    PluginInterface,
    PluginPhase
};

class RecordingPluginFixture extends AbstractPlugin
{
    /**
     * @param \ArrayObject<int, string> $calls
     * @param list<PluginInterface> $children
     */
    public function __construct(
        private readonly string $name,
        private readonly \ArrayObject $calls,
        private readonly PluginPhase $phase = PluginPhase::Apply,
        private array $children = []
    ) {}

    public function configure(ClientConfig $config): ClientConfig
    {
        $this->calls->append($this->name);

        return $config;
    }

    /** @inheritDoc */
    public function getChildPlugins(): array
    {
        return $this->children;
    }

    /** @inheritDoc */
    public function getPluginPhase(): PluginPhase
    {
        return $this->phase;
    }

    /**
     * Replace child plugins for recursive-resolution tests.
     *
     * @param list<PluginInterface> $children
     */
    public function setChildPlugins(array $children): void
    {
        $this->children = $children;
    }
}
