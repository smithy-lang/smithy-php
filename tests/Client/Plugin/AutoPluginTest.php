<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Plugin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\ClientConfig;
use Smithy\Client\Endpoint\StaticEndpointResolver;
use Smithy\Client\Plugin\{
    AutoPlugin,
    ComposerAutoPluginRegistry,
    DefaultPlugin,
    PluginCollection
};
use Smithy\Core\Endpoint\Endpoint;
use Smithy\Tests\Client\Fake\{
    FakeProtocol,
    TestClientFixture
};
use Smithy\Tests\Client\Plugin\Utils\{
    AutoPluginFixture,
    ComposerAutoPluginRegistryFixture,
    SecondaryAutoPluginFixture
};
use Smithy\Tests\Utils\Http\MockHttpClient;

#[CoversClass(AutoPlugin::class)]
#[CoversClass(DefaultPlugin::class)]
final class AutoPluginTest extends TestCase
{
    protected function setUp(): void
    {
        ComposerAutoPluginRegistryFixture::reset();
        AutoPluginFixture::reset();
        SecondaryAutoPluginFixture::reset();
    }

    protected function tearDown(): void
    {
        ComposerAutoPluginRegistryFixture::reset();
    }

    public function testDefaultPluginAppliesComposerAutoPlugins(): void
    {
        ComposerAutoPluginRegistry::register(
            AutoPluginFixture::class
        );

        $client = new TestClientFixture($this->config());

        self::assertSame(
            ['auto'],
            $client->getConfig()->context->get(
                AutoPluginFixture::contextKey()
            )
        );
        self::assertSame(1, AutoPluginFixture::$constructionCount);
        self::assertSame(1, AutoPluginFixture::$configurationCount);
    }

    public function testAutomaticPluginsUseTheirOwnPhases(): void
    {
        ComposerAutoPluginRegistry::register(
            AutoPluginFixture::class
        );
        ComposerAutoPluginRegistry::register(
            SecondaryAutoPluginFixture::class
        );

        $client = new TestClientFixture($this->config());

        self::assertSame(
            ['secondary', 'auto'],
            $client->getConfig()->context->get(
                AutoPluginFixture::contextKey()
            )
        );
    }

    public function testRegistryInstancesAreSharedAcrossClients(): void
    {
        ComposerAutoPluginRegistry::register(
            AutoPluginFixture::class
        );

        new TestClientFixture($this->config());
        new TestClientFixture($this->config());

        self::assertSame(1, AutoPluginFixture::$constructionCount);
        self::assertSame(2, AutoPluginFixture::$configurationCount);
    }

    public function testExplicitDuplicateDoesNotReplaceAutoPlugin(): void
    {
        ComposerAutoPluginRegistry::register(
            AutoPluginFixture::class
        );
        $explicit = new AutoPluginFixture('explicit');

        $client = new TestClientFixture(
            $this->config(),
            new PluginCollection($explicit)
        );

        self::assertSame(
            ['auto'],
            $client->getConfig()->context->get(
                AutoPluginFixture::contextKey()
            )
        );
        self::assertSame(1, AutoPluginFixture::$configurationCount);
    }

    public function testWithoutAutoPluginsSkipsDiscovery(): void
    {
        ComposerAutoPluginRegistry::register(
            AutoPluginFixture::class
        );

        $client = new TestClientFixture(
            $this->config()->withoutAutoPlugins()
        );

        self::assertFalse(
            $client->getConfig()->context->has(
                AutoPluginFixture::contextKey()
            )
        );
        self::assertSame(0, AutoPluginFixture::$constructionCount);
        self::assertSame(0, AutoPluginFixture::$configurationCount);
    }

    private function config(): ClientConfig
    {
        return new ClientConfig(
            protocol: new FakeProtocol(),
            transport: new \Smithy\Http\Client\HttpClientTransport(
                new MockHttpClient()
            ),
            endpointResolver: new StaticEndpointResolver(
                new Endpoint('https://example.test')
            )
        );
    }
}
