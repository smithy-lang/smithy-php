<?php

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
use Smithy\Http\Message\{
    Request,
    Uri
};
use Smithy\Tests\Client\Fake\FakeProtocol;
use Smithy\Tests\Utils\Http\MockHttpClient;

#[CoversClass(BearerToken::class)]
#[CoversClass(HttpBearerAuthScheme::class)]
#[CoversClass(HttpBearerAuthSigner::class)]
#[CoversClass(StaticTokenProvider::class)]
final class HttpBearerAuthTest extends TestCase
{
    public function testSignsHttpsRequestWithBearerToken(): void
    {
        $request = new Request('GET', new Uri('https://example.test'));

        $signed = HttpBearerAuthSigner::instance()->sign(
            $request,
            new BearerToken('mF_9.B5f-4.1JqM')
        );

        self::assertSame(
            'Bearer mF_9.B5f-4.1JqM',
            $signed->getHeaderLine('Authorization')
        );
    }

    public function testSignerReplacesExistingAuthorizationHeader(): void
    {
        $request = new Request(
            'GET',
            new Uri('https://example.test'),
            ['Authorization' => 'Bearer old']
        );

        $signed = HttpBearerAuthSigner::instance()->sign(
            $request,
            new BearerToken('new')
        );

        self::assertSame(
            'Bearer new',
            $signed->getHeaderLine('Authorization')
        );
    }

    public function testSignerRejectsHttpEndpoint(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('HTTPS');

        HttpBearerAuthSigner::instance()->sign(
            new Request('GET', new Uri('http://example.test')),
            new BearerToken('token')
        );
    }

    public function testBearerTokenRejectsEmptyValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must not be empty');

        new BearerToken('');
    }

    public function testSchemeReturnsConfiguredTokenProvider(): void
    {
        $provider = new StaticTokenProvider('token', 123.0);
        $scheme = new HttpBearerAuthScheme($provider);
        $config = new ClientConfig(
            protocol: new FakeProtocol(),
            httpClient: new MockHttpClient(),
            endpointResolver: new StaticEndpointResolver(
                new Endpoint('https://example.test')
            )
        );

        $identity = $scheme->getIdentityResolver($config)->resolveIdentity();

        self::assertSame(HttpBearerAuthScheme::ID, $scheme->getSchemeId());
        self::assertInstanceOf(BearerToken::class, $identity);
        self::assertSame(123.0, $identity->getExpirationTime());
        self::assertSame(
            HttpBearerAuthSigner::instance(),
            $scheme->getSigner()
        );
    }
}
