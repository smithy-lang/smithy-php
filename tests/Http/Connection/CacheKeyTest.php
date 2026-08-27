<?php

declare(strict_types=1);

namespace Smithy\Tests\Http\Connection;

use PHPUnit\Framework\TestCase;
use Smithy\Http\Connection\CacheKey;
use Smithy\Protocol\Utils\ComponentUri;

final class CacheKeyTest extends TestCase
{
    public function testDefaultsSchemeAndPortFromMissingScheme(): void
    {
        $key = CacheKey::fromUri(new ComponentUri(host: 'example.com'));

        self::assertSame('https://example.com:443', (string) $key);
    }

    public function testDefaultsHttpPort(): void
    {
        $key = CacheKey::fromUri(
            new ComponentUri(scheme: 'http', host: 'example.com')
        );

        self::assertSame('http://example.com:80', (string) $key);
    }

    public function testPreservesExplicitPortForUnknownScheme(): void
    {
        $key = CacheKey::fromUri(
            new ComponentUri(
                scheme: 'ftp',
                host: 'example.com',
                port: 2121
            )
        );

        self::assertSame('ftp://example.com:2121', (string) $key);
    }

    public function testRejectsUnknownSchemeWithoutPort(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'URI scheme ftp requires an explicit port.'
        );

        CacheKey::fromUri(
            new ComponentUri(scheme: 'ftp', host: 'example.com')
        );
    }
}
