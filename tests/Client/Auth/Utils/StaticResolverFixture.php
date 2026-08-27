<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Auth\Utils;

use Smithy\Client\Auth\{
    IdentityInterface,
    IdentityResolverInterface
};
final class StaticResolverFixture implements IdentityResolverInterface
{
    public function __construct(private readonly IdentityInterface $identity) {}

    public function resolveIdentity(array $properties = []): IdentityInterface
    {
        return $this->identity;
    }
}
