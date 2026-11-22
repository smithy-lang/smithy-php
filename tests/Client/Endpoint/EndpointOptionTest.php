<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Endpoint;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\ClientConfig;
use Smithy\Client\Endpoint\{
    EndpointOption,
    StaticEndpointResolver
};
use Smithy\Core\Endpoint\Endpoint;
use Smithy\Http\Client\HttpClientTransport;
use Smithy\Tests\Client\Fake\{
    FakeInput,
    FakeOperation,
    FakeProtocol
};
use Smithy\Tests\Utils\Http\MockHttpClient;

#[CoversClass(EndpointOption::class)]
final class EndpointOptionTest extends TestCase
{
    public function testReturnsConfiguredResolver(): void
    {
        $resolver = new StaticEndpointResolver(
            new Endpoint('https://resolver.test')
        );

        self::assertSame(
            $resolver,
            EndpointOption::resolve($resolver, null)
        );
    }

    public function testWrapsEndpointInStaticResolver(): void
    {
        $resolver = EndpointOption::resolve(null, 'https://static.test');

        self::assertInstanceOf(StaticEndpointResolver::class, $resolver);
        $config = new ClientConfig(
            transport: new HttpClientTransport(new MockHttpClient()),
            endpointResolver: $resolver,
            protocol: new FakeProtocol()
        );
        self::assertSame(
            'https://static.test',
            $resolver->resolveEndpoint(
                FakeOperation::instance(),
                new FakeInput(),
                $config
            )->url
        );
    }

    public function testReturnsNullWithoutResolverOrEndpoint(): void
    {
        self::assertNull(EndpointOption::resolve(null, null));
        self::assertNull(EndpointOption::resolve(null, ''));
    }

    public function testRejectsResolverWithEndpoint(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Provide either an endpoint resolver or a static endpoint, '
            . 'not both.'
        );

        EndpointOption::resolve(
            new StaticEndpointResolver(new Endpoint('https://a.test')),
            'https://b.test'
        );
    }
}
