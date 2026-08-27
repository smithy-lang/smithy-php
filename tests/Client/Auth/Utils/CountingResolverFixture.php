<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Auth\Utils;

use Smithy\Client\Auth\{
    IdentityInterface,
    IdentityResolverInterface
};
/**
 * Test resolver that always returns the same identity and counts calls.
 */
final class CountingResolverFixture implements IdentityResolverInterface
{
    public int $calls = 0;

    public function __construct(private readonly IdentityInterface $identity) {}

    public function resolveIdentity(array $properties = []): IdentityInterface
    {
        $this->calls++;

        return $this->identity;
    }
}
