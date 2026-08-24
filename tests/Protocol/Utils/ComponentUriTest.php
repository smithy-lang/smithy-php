<?php

declare(strict_types=1);

namespace Smithy\Tests\Protocol\Utils;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Protocol\Utils\ComponentUri;

#[CoversClass(ComponentUri::class)]
final class ComponentUriTest extends TestCase
{
    public function testReturnsConstructedComponents(): void
    {
        $uri = new ComponentUri(
            scheme: 'https',
            host: 'example.com',
            port: 8443,
            path: '/path',
            query: 'key=value',
            fragment: 'fragment',
            userInfo: 'user:password'
        );

        $this->assertSame('https', $uri->getScheme());
        $this->assertSame(
            'user:password@example.com:8443',
            $uri->getAuthority()
        );
        $this->assertSame('user:password', $uri->getUserInfo());
        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame(8443, $uri->getPort());
        $this->assertSame('/path', $uri->getPath());
        $this->assertSame('key=value', $uri->getQuery());
        $this->assertSame('fragment', $uri->getFragment());
        $this->assertSame(
            'https://user:password@example.com:8443/path?key=value#fragment',
            (string) $uri
        );
    }

    public function testOmitsAuthorityWithoutHost(): void
    {
        $uri = new ComponentUri(
            path: '/path',
            userInfo: 'user'
        );

        $this->assertSame('', $uri->getAuthority());
        $this->assertSame('/path', (string) $uri);
    }

    public function testPrefixesRelativePathAfterAuthority(): void
    {
        $uri = new ComponentUri(
            scheme: 'https',
            host: 'example.com',
            path: 'path'
        );

        $this->assertSame('https://example.com/path', (string) $uri);
    }

    public function testCollapsesLeadingSlashesWithoutAuthority(): void
    {
        $uri = new ComponentUri(path: '///path');

        $this->assertSame('/path', (string) $uri);
    }

    public function testWithSchemeNormalizesCaseAndDefaultPort(): void
    {
        $uri = new ComponentUri(
            scheme: 'http',
            host: 'example.com',
            port: 443
        );

        $updated = $uri->withScheme('HTTPS');

        $this->assertNotSame($uri, $updated);
        $this->assertSame('https', $updated->getScheme());
        $this->assertNull($updated->getPort());
        $this->assertSame('http://example.com:443', (string) $uri);
        $this->assertSame('https://example.com', (string) $updated);
    }

    public function testWithUserInfoBuildsCredentials(): void
    {
        $uri = new ComponentUri(
            scheme: 'https',
            host: 'example.com'
        );

        $withPassword = $uri->withUserInfo('user', 'password');
        $withoutPassword = $uri->withUserInfo('user', '');

        $this->assertSame('user:password', $withPassword->getUserInfo());
        $this->assertSame('user', $withoutPassword->getUserInfo());
        $this->assertSame(
            $withoutPassword,
            $withoutPassword->withUserInfo('user')
        );
    }

    public function testWithHostNormalizesCase(): void
    {
        $uri = new ComponentUri(host: 'example.com');
        $updated = $uri->withHost('EXAMPLE.ORG');

        $this->assertSame('example.org', $updated->getHost());
        $this->assertSame($updated, $updated->withHost('EXAMPLE.ORG'));
    }

    #[DataProvider('validPortProvider')]
    public function testWithPortAcceptsValidPorts(
        string $scheme,
        ?int $port,
        ?int $expected
    ): void
    {
        $uri = new ComponentUri(
            scheme: $scheme,
            host: 'example.com'
        );

        $this->assertSame($expected, $uri->withPort($port)->getPort());
    }

    /** @return iterable<string, array{string, int|null, int|null}> */
    public static function validPortProvider(): iterable
    {
        yield 'none' => ['https', null, null];
        yield 'minimum' => ['https', 0, 0];
        yield 'maximum' => ['https', 65535, 65535];
        yield 'http default' => ['http', 80, null];
        yield 'https default' => ['https', 443, null];
        yield 'non-default' => ['https', 8443, 8443];
    }

    #[DataProvider('invalidPortProvider')]
    public function testWithPortRejectsInvalidPort(int $port): void
    {
        $uri = new ComponentUri();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Invalid port: {$port}. Must be between 0 and 65535."
        );

        $uri->withPort($port);
    }

    /** @return iterable<string, array{int}> */
    public static function invalidPortProvider(): iterable
    {
        yield 'negative' => [-1];
        yield 'too large' => [65536];
    }

    public function testComponentMutationsPreserveOriginal(): void
    {
        $uri = new ComponentUri(
            scheme: 'https',
            host: 'example.com',
            path: '/original',
            query: 'old=1',
            fragment: 'old'
        );

        $updated = $uri
            ->withPath('/updated')
            ->withQuery('new=2')
            ->withFragment('new');

        $this->assertSame(
            'https://example.com/original?old=1#old',
            (string) $uri
        );
        $this->assertSame(
            'https://example.com/updated?new=2#new',
            (string) $updated
        );
    }

    public function testUnchangedComponentsReturnSameInstance(): void
    {
        $uri = new ComponentUri(
            scheme: 'https',
            host: 'example.com',
            port: 8443,
            path: '/path',
            query: 'key=value',
            fragment: 'fragment',
            userInfo: 'user'
        );

        $this->assertSame($uri, $uri->withScheme('HTTPS'));
        $this->assertSame($uri, $uri->withHost('EXAMPLE.COM'));
        $this->assertSame($uri, $uri->withPort(8443));
        $this->assertSame($uri, $uri->withPath('/path'));
        $this->assertSame($uri, $uri->withQuery('key=value'));
        $this->assertSame($uri, $uri->withFragment('fragment'));
        $this->assertSame($uri, $uri->withUserInfo('user'));
    }
}
