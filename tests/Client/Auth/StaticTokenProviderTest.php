<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Auth\{
    BearerToken,
    StaticTokenProvider
};

#[CoversClass(StaticTokenProvider::class)]
final class StaticTokenProviderTest extends TestCase
{
    public function testCreatesTokenFromString(): void
    {
        $identity = (new StaticTokenProvider(
            'token',
            123.0
        ))->resolveIdentity();

        self::assertInstanceOf(BearerToken::class, $identity);
        self::assertSame('token', $identity->token);
        self::assertSame(123.0, $identity->getExpirationTime());
    }

    public function testReturnsProvidedToken(): void
    {
        $token = new BearerToken('token');

        self::assertSame(
            $token,
            (new StaticTokenProvider($token))->resolveIdentity()
        );
    }
}
