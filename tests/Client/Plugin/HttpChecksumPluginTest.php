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
use Smithy\Client\Endpoint\{
    Endpoint,
    StaticEndpointResolver
};
use Smithy\Client\Interceptor\HttpChecksumInterceptor;
use Smithy\Client\Plugin\HttpChecksumPlugin;
use Smithy\Tests\Client\Fake\FakeProtocol;
use Smithy\Tests\Utils\Http\MockHttpClient;

#[CoversClass(HttpChecksumPlugin::class)]
final class HttpChecksumPluginTest extends TestCase
{
    public function testRegistersHttpChecksumInterceptor(): void
    {
        $config = (new HttpChecksumPlugin())->configure($this->config());

        self::assertTrue(
            $config->interceptorChain->contains(
                HttpChecksumInterceptor::class
            )
        );
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
