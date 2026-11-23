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
use Smithy\Core\Context\{
    Context,
    MutableContext
};
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
            $this->config('fixed-token')
        );
        $output = $config->interceptorChain->modifyBeforeSerialization(
            new InputContext(
                IdempotentTestOperationFixture::instance(),
                new IdempotentTestInputFixture(),
                $config,
                new MutableContext()
            )
        );

        self::assertSame('fixed-token', $output->token);
    }

    public function testResolvesCallableProvider(): void
    {
        $config = IdempotencyTokenPlugin::instance()->configure(
            $this->config(static fn(): string => 'callable-token')
        );
        $output = $config->interceptorChain->modifyBeforeSerialization(
            new InputContext(
                IdempotentTestOperationFixture::instance(),
                new IdempotentTestInputFixture(),
                $config,
                new MutableContext()
            )
        );

        self::assertSame('callable-token', $output->token);
    }

    public function testRejectsEmptyStringProvider(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'IdempotencyTokenProvider must not be an empty string.'
        );

        IdempotencyTokenPlugin::instance()->configure($this->config(''));
    }

    /**
     * @param string|callable(): string|null $provider
     */
    private function config(string|callable|null $provider = null): ClientConfig
    {
        $context = new Context();
        if ($provider !== null) {
            $context = $context->with(
                IdempotencyTokenPlugin::tokenProviderKey(),
                $provider
            );
        }

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
