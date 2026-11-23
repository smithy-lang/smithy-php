<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Interceptor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\ClientConfig;
use Smithy\Client\Endpoint\StaticEndpointResolver;
use Smithy\Client\Interceptor\{
    IdempotencyTokenInterceptor,
    InputContext
};
use Smithy\Core\Context\MutableContext;
use Smithy\Core\Endpoint\Endpoint;
use Smithy\Tests\Client\Fake\{
    FakeInput,
    FakeOperation
};
use Smithy\Tests\Client\Utils\{
    IdempotentTestInputFixture,
    IdempotentTestOperationFixture,
    IdempotentTestProtocolFixture
};
use Smithy\Tests\Utils\Http\MockHttpClient;

#[CoversClass(IdempotencyTokenInterceptor::class)]
final class IdempotencyTokenInterceptorTest extends TestCase
{
    public function testInjectsConfiguredTokenWhenMissing(): void
    {
        $interceptor = new IdempotencyTokenInterceptor(
            static fn(): string => 'fixed-token'
        );
        $input = new IdempotentTestInputFixture(value: 'hello');

        $output = $interceptor->modifyBeforeSerialization(
            $this->context($input)
        );

        self::assertInstanceOf(IdempotentTestInputFixture::class, $output);
        self::assertNotSame($input, $output);
        self::assertSame('hello', $output->value);
        self::assertSame('fixed-token', $output->token);
    }

    public function testPreservesExplicitToken(): void
    {
        $interceptor = new IdempotencyTokenInterceptor(
            static fn(): string => 'fixed-token'
        );
        $input = new IdempotentTestInputFixture(
            value: 'hello',
            token: 'user-supplied'
        );

        $output = $interceptor->modifyBeforeSerialization(
            $this->context($input)
        );

        self::assertSame($input, $output);
    }

    public function testTreatsEmptyTokenAsMissing(): void
    {
        $interceptor = new IdempotencyTokenInterceptor(
            static fn(): string => 'fixed-token'
        );

        $output = $interceptor->modifyBeforeSerialization(
            $this->context(new IdempotentTestInputFixture(token: ''))
        );

        self::assertSame('fixed-token', $output->token);
    }

    public function testLeavesInputsWithoutTokenMemberUnchanged(): void
    {
        $config = $this->config();
        $input = new FakeInput();
        $context = new InputContext(
            FakeOperation::instance(),
            $input,
            $config,
            new MutableContext()
        );

        $output = (new IdempotencyTokenInterceptor())
            ->modifyBeforeSerialization($context);

        self::assertSame($input, $output);
    }

    public function testAppliesOnlyToOperationsWithTokenMember(): void
    {
        $interceptor = new IdempotencyTokenInterceptor();

        self::assertTrue($interceptor->appliesTo(
            IdempotentTestOperationFixture::instance()
        ));
        self::assertFalse($interceptor->appliesTo(FakeOperation::instance()));
    }

    public function testGeneratesUuidWhenProviderIsAbsent(): void
    {
        $output = (new IdempotencyTokenInterceptor())
            ->modifyBeforeSerialization(
                $this->context(new IdempotentTestInputFixture())
            );

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-'
                . '[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $output->token
        );
    }

    public function testRejectsInvalidProviderResult(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Idempotency token provider must return a non-empty string.'
        );

        (new IdempotencyTokenInterceptor(static fn(): string => ''))
            ->modifyBeforeSerialization(
                $this->context(new IdempotentTestInputFixture())
            );
    }

    private function context(
        IdempotentTestInputFixture $input
    ): InputContext
    {
        return new InputContext(
            IdempotentTestOperationFixture::instance(),
            $input,
            $this->config(),
            new MutableContext()
        );
    }

    private function config(): ClientConfig
    {
        return new ClientConfig(
            protocol: new IdempotentTestProtocolFixture(),
            transport: new \Smithy\Http\Client\HttpClientTransport(
                new MockHttpClient()
            ),
            endpointResolver: new StaticEndpointResolver(
                new Endpoint('https://example.test')
            )
        );
    }
}
