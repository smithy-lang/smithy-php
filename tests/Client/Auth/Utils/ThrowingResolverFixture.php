<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Auth\Utils;

use Smithy\Client\Auth\{
    IdentityInterface,
    IdentityResolverInterface,
    IdentityResult
};

/**
 * @implements IdentityResolverInterface<never>
 */
final class ThrowingResolverFixture implements IdentityResolverInterface
{
    public function __construct(private readonly string $message) {}

    public function resolveIdentity(array $properties = []): IdentityResult
    {
        throw new \RuntimeException($this->message);
    }

    public function invalidate(IdentityInterface $rejectedIdentity): void {}
}
