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
    BearerToken,
    HttpBearerAuthScheme,
    HttpBearerAuthSigner,
    StaticTokenProvider
};
use Smithy\Client\ClientConfig;
use Smithy\Client\Endpoint\{
    Endpoint,
    StaticEndpointResolver
};
use Smithy\Tests\Client\Fake\FakeProtocol;
use Smithy\Tests\Utils\Http\MockHttpClient;

#[CoversClass(HttpBearerAuthScheme::class)]
final class HttpBearerAuthSchemeTest extends TestCase
{
    public function testReturnsConfiguredProviderAndSigner(): void
    {
        $provider = new StaticTokenProvider('token', 123.0);
        $scheme = new HttpBearerAuthScheme($provider);
        $config = new ClientConfig(
            protocol: new FakeProtocol(),
            transport: new \Smithy\Http\Client\HttpClientTransport(
                new MockHttpClient()
            ),
            endpointResolver: new StaticEndpointResolver(
                new Endpoint('https://example.test')
            )
        );

        $identity = $scheme
            ->getIdentityResolver($config)
            ->resolveIdentity()
            ->unwrap();

        self::assertSame(HttpBearerAuthScheme::ID, $scheme->getSchemeId());
        self::assertInstanceOf(BearerToken::class, $identity);
        self::assertSame(123.0, $identity->getExpirationTime());
        self::assertSame(
            HttpBearerAuthSigner::instance(),
            $scheme->getSigner()
        );
    }
}
