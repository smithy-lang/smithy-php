<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Plugin\Utils;

use Smithy\Client\Plugin\ComposerAutoPluginRegistry;

final class ComposerAutoPluginRegistryFixture
{
    public static function reset(): void
    {
        $classes = new \ReflectionProperty(
            ComposerAutoPluginRegistry::class,
            'pluginClasses'
        );
        $classes->setValue(null, []);

        $plugins = new \ReflectionProperty(
            ComposerAutoPluginRegistry::class,
            'plugins'
        );
        $plugins->setValue(null, []);

        $loaded = new \ReflectionProperty(
            ComposerAutoPluginRegistry::class,
            'loaded'
        );
        $loaded->setValue(null, false);

        $loadError = new \ReflectionProperty(
            ComposerAutoPluginRegistry::class,
            'loadError'
        );
        $loadError->setValue(null, null);
    }
}
