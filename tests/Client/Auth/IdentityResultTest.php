<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Auth\{
    AnonymousIdentity,
    IdentityNotFoundException,
    IdentityResult
};

#[CoversClass(IdentityResult::class)]
final class IdentityResultTest extends TestCase
{
    public function testCreatesSuccessfulResult(): void
    {
        $identity = AnonymousIdentity::instance();
        $result = IdentityResult::success($identity);

        $this->assertSame($identity, $result->identity);
        $this->assertNull($result->error);
        $this->assertNull($result->resolver);
        $this->assertSame($identity, $result->unwrap());
    }

    public function testCreatesExpectedMiss(): void
    {
        $result = IdentityResult::notFound(self::class, 'not configured');

        $this->assertNull($result->identity);
        $this->assertSame('not configured', $result->error);
        $this->assertSame(self::class, $result->resolver);

        $this->expectException(IdentityNotFoundException::class);
        $this->expectExceptionMessage(
            'Unable to resolve an identity: not configured ('
                . self::class
                . ')'
        );

        $result->unwrap();
    }
}
