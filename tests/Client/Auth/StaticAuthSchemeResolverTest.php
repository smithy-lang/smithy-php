<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Auth\{
    NoAuthScheme,
    StaticAuthSchemeResolver
};
use Smithy\Client\ClientConfig;
use Smithy\Client\Endpoint\{
    Endpoint,
    StaticEndpointResolver
};
use Smithy\Core\Shape\OperationInterface;
use Smithy\Tests\Client\Fake\{
    FakeOperation,
    FakeProtocol,
    TestAuthSchemeFixture
};
use Smithy\Tests\Utils\Http\MockHttpClient;

#[CoversClass(StaticAuthSchemeResolver::class)]
final class StaticAuthSchemeResolverTest extends TestCase
{
    public function testUsesConfiguredSchemeIdsInsteadOfModeledIds(): void
    {
        $endpoint = new Endpoint('https://example.test');
        $scheme = new TestAuthSchemeFixture('smithy.test#auth');
        $config = $this->config($endpoint, [$scheme]);
        $resolver = new StaticAuthSchemeResolver(['smithy.test#auth']);

        $resolved = $resolver->resolveAuthScheme(
            FakeOperation::instance(),
            $config
        );

        $this->assertSame($scheme, $resolved->scheme);
    }

    public function testCreatesNoAuthResolver(): void
    {
        $endpoint = new Endpoint('https://example.test');
        $config = $this->config($endpoint);

        $resolved = StaticAuthSchemeResolver::noAuth()->resolveAuthScheme(
            $this->createStub(OperationInterface::class),
            $config
        );

        $this->assertSame(NoAuthScheme::instance(), $resolved->scheme);
    }

    /**
     * @param list<TestAuthSchemeFixture> $schemes
     */
    private function config(
        Endpoint $endpoint,
        array $schemes = []
    ): ClientConfig
    {
        return new ClientConfig(
            protocol: new FakeProtocol(),
            transport: new \Smithy\Http\Client\HttpClientTransport(
                new MockHttpClient()
            ),
            endpointResolver: new StaticEndpointResolver($endpoint),
            supportedAuthSchemes: $schemes
        );
    }
}
