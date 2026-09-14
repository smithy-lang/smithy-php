<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Auth\Utils;

use Smithy\Client\Auth\{
    IdentityInterface,
    IdentityResolverInterface,
    IdentityResult
};

/**
 * Test resolver that always reports an expected identity miss.
 */
final class NotFoundResolverFixture implements IdentityResolverInterface
{
    public function __construct(private readonly string $message) {}

    public function resolveIdentity(array $properties = []): IdentityResult
    {
        return IdentityResult::notFound(self::class, $this->message);
    }

    public function invalidate(IdentityInterface $rejectedIdentity): void {}
}
