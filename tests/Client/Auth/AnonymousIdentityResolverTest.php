<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Auth\{
    AnonymousIdentity,
    AnonymousIdentityResolver
};

#[CoversClass(AnonymousIdentityResolver::class)]
final class AnonymousIdentityResolverTest extends TestCase
{
    public function testResolvesSharedAnonymousIdentity(): void
    {
        $resolver = AnonymousIdentityResolver::instance();

        self::assertSame($resolver, AnonymousIdentityResolver::instance());
        self::assertSame(
            AnonymousIdentity::instance(),
            $resolver->resolveIdentity()->unwrap()
        );
    }
}
