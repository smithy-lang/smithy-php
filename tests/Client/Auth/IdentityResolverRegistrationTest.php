<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Auth\{
    IdentityResolverRegistration,
    ResolvedIdentityMetadata
};
use Smithy\Tests\Client\Auth\Utils\{
    CountingResolverFixture,
    TestIdentityFixture
};

#[CoversClass(IdentityResolverRegistration::class)]
final class IdentityResolverRegistrationTest extends TestCase
{
    public function testForwardsRejectedIdentityToResolver(): void
    {
        $identity = new TestIdentityFixture();
        $resolver = new CountingResolverFixture($identity);
        $registration = new IdentityResolverRegistration(
            $resolver,
            new ResolvedIdentityMetadata('provider')
        );

        $registration->invalidate($identity);

        self::assertSame([$identity], $resolver->invalidated);
    }
}
