<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Auth\BearerToken;

#[CoversClass(BearerToken::class)]
final class BearerTokenTest extends TestCase
{
    public function testStoresTokenAndExpiration(): void
    {
        $token = new BearerToken('token', 123.0);

        self::assertSame('token', $token->token);
        self::assertSame(123.0, $token->getExpirationTime());
    }

    public function testRejectsEmptyToken(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must not be empty');

        new BearerToken('');
    }
}
