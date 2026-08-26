<?php

declare(strict_types=1);

namespace Smithy\Tests\Http\Connection;

use PHPUnit\Framework\TestCase;
use Smithy\Http\Connection\RequestContext;
use Smithy\Protocol\Utils\ComponentUri;

final class RequestContextTest extends TestCase
{
    public function testDefaultsSchemeAndPortFromMissingScheme(): void
    {
        $context = RequestContext::fromUri(
            new ComponentUri(host: 'example.com')
        );

        self::assertSame('https', $context->scheme);
        self::assertSame(443, $context->port);
        self::assertSame(
            'https://example.com:443',
            (string) $context->cacheKey()
        );
    }
}
