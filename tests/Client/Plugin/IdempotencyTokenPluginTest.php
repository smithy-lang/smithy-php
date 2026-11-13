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
    IdempotencyTokenInterceptor,
    InputContext
};
use Smithy\Client\Plugin\IdempotencyTokenPlugin;
use Smithy\Core\Endpoint\Endpoint;
use Smithy\Tests\Client\Utils\{
    IdempotentTestInputFixture,
    IdempotentTestOperationFixture,
    IdempotentTestProtocolFixture
};
use Smithy\Tests\Utils\Http\MockHttpClient;

#[CoversClass(IdempotencyTokenPlugin::class)]
final class IdempotencyTokenPluginTest extends TestCase
{
    public function testReturnsSharedInstance(): void
    {
        self::assertSame(
            IdempotencyTokenPlugin::instance(),
            IdempotencyTokenPlugin::instance()
        );
    }

    public function testRegistersInterceptorOnce(): void
    {
        $config = IdempotencyTokenPlugin::instance()->configure(
            $this->config()
        );
        $configuredAgain =
            IdempotencyTokenPlugin::instance()->configure($config);

        self::assertSame($config, $configuredAgain);
        self::assertTrue(
            $config->interceptorChain->contains(
                IdempotencyTokenInterceptor::class
            )
        );
    }

    public function testResolvesStringProvider(): void
    {
        $config = IdempotencyTokenPlugin::instance()->configure(
            $this->config([
                IdempotencyTokenPlugin::CONFIG_IDEMPOTENCY_TOKEN_PROVIDER
                    => 'fixed-token'
            ])
        );
        $output = $config->interceptorChain->modifyBeforeSerialization(
            new InputContext(
                IdempotentTestOperationFixture::instance(),
                new IdempotentTestInputFixture(),
                $config
            )
        );

        self::assertSame('fixed-token', $output->token);
    }

    public function testRejectsInvalidProvider(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'IdempotencyTokenProvider must be a non-empty string or callable.'
        );

        IdempotencyTokenPlugin::instance()->configure(
            $this->config([
                IdempotencyTokenPlugin::CONFIG_IDEMPOTENCY_TOKEN_PROVIDER
                    => 123
            ])
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    private function config(array $context = []): ClientConfig
    {
        return new ClientConfig(
            protocol: new IdempotentTestProtocolFixture(),
            transport: new \Smithy\Http\Client\HttpClientTransport(
                new MockHttpClient()
            ),
            endpointResolver: new StaticEndpointResolver(
                new Endpoint('https://example.test')
            ),
            context: $context
        );
    }
}
