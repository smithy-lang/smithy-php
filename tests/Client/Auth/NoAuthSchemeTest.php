<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Auth\{
    AnonymousIdentityResolver,
    NoAuthScheme,
    NullSigner
};
use Smithy\Client\ClientConfig;
use Smithy\Client\Endpoint\{
    Endpoint,
    StaticEndpointResolver
};
use Smithy\Tests\Client\Fake\FakeProtocol;
use Smithy\Tests\Utils\Http\MockHttpClient;

#[CoversClass(NoAuthScheme::class)]
final class NoAuthSchemeTest extends TestCase
{
    public function testReturnsAnonymousAuthComponents(): void
    {
        $scheme = NoAuthScheme::instance();
        $config = new ClientConfig(
            protocol: new FakeProtocol(),
            httpClient: new MockHttpClient(),
            endpointResolver: new StaticEndpointResolver(
                new Endpoint('https://example.test')
            )
        );

        self::assertSame($scheme, NoAuthScheme::instance());
        self::assertSame(NoAuthScheme::ID, $scheme->getSchemeId());
        self::assertSame(
            AnonymousIdentityResolver::instance(),
            $scheme->getIdentityResolver($config)
        );
        self::assertSame(NullSigner::instance(), $scheme->getSigner());
    }
}
