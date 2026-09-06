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
    StaticResolverFixture,
    TestIdentityFixture
};

#[CoversClass(IdentityResolverRegistration::class)]
final class IdentityResolverRegistrationTest extends TestCase
{
    public function testRunsRegisteredInvalidator(): void
    {
        $invalidated = false;
        $registration = new IdentityResolverRegistration(
            new StaticResolverFixture(new TestIdentityFixture()),
            new ResolvedIdentityMetadata('provider'),
            invalidator: static function () use (&$invalidated): void {
                $invalidated = true;
            }
        );

        $registration->invalidate();

        self::assertTrue($invalidated);
    }

    public function testRejectsInvalidInvalidator(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be callable');

        new IdentityResolverRegistration(
            new StaticResolverFixture(new TestIdentityFixture()),
            new ResolvedIdentityMetadata('provider'),
            invalidator: 'not-callable'
        );
    }
}
