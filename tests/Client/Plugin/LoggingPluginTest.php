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
use Smithy\Client\Interceptor\{
    InputContext,
    LoggingInterceptor
};
use Smithy\Client\Plugin\{
    LoggingPlugin,
    PluginPhase
};
use Smithy\Core\Endpoint\Endpoint;
use Smithy\Http\Client\HttpClientTransport;
use Smithy\Tests\Client\Fake\{
    FakeInput,
    FakeOperation,
    FakeProtocol
};
use Smithy\Tests\Client\Utils\{
    InputLogContextProviderFixture,
    RecordingLoggerFixture
};
use Smithy\Tests\Utils\Http\MockHttpClient;

#[CoversClass(LoggingPlugin::class)]
final class LoggingPluginTest extends TestCase
{
    public function testLeavesConfigUnchangedWithoutLogger(): void
    {
        $config = $this->config();

        self::assertSame($config, (new LoggingPlugin())->configure($config));
    }

    public function testRegistersInterceptorOnceWhenLoggerIsSet(): void
    {
        $plugin = new LoggingPlugin();
        $config = $plugin->configure(
            $this->config()->withLogger(new RecordingLoggerFixture())
        );

        self::assertTrue(
            $config->interceptorChain->contains(LoggingInterceptor::class)
        );
        self::assertSame($config, $plugin->configure($config));
    }

    public function testPassesContextProvidersToInterceptor(): void
    {
        $logger = new RecordingLoggerFixture();
        $config = (new LoggingPlugin(
            contextProviders: [
                new InputLogContextProviderFixture(['source' => 'plugin'])
            ]
        ))->configure($this->config()->withLogger($logger));

        $config->interceptorChain->readBeforeExecution(new InputContext(
            FakeOperation::instance(),
            new FakeInput('hello'),
            $config
        ));

        self::assertSame('plugin', $logger->records[0]['context']['source']);
    }

    public function testRunsInLastPhase(): void
    {
        self::assertSame(
            PluginPhase::Last,
            (new LoggingPlugin())->getPluginPhase()
        );
    }

    private function config(): ClientConfig
    {
        return new ClientConfig(
            protocol: new FakeProtocol(),
            transport: new HttpClientTransport(new MockHttpClient()),
            endpointResolver: new StaticEndpointResolver(
                new Endpoint('https://example.test')
            )
        );
    }
}
