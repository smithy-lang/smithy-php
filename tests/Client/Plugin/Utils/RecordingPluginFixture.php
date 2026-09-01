<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Plugin\Utils;

use Smithy\Client\ClientConfig;
use Smithy\Client\Plugin\PluginInterface;

class RecordingPluginFixture implements PluginInterface
{
    /** @param \ArrayObject<int, string> $calls */
    public function __construct(
        private readonly string $name,
        private readonly \ArrayObject $calls
    ) {}

    public function configure(ClientConfig $config): ClientConfig
    {
        $this->calls->append($this->name);

        return $config;
    }
}
