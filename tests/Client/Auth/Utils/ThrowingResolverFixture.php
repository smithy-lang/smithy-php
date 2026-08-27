<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Auth\Utils;

use Smithy\Client\Auth\{
    IdentityInterface,
    IdentityResolverInterface
};
final class ThrowingResolverFixture implements IdentityResolverInterface
{
    public function __construct(private readonly string $message) {}

    public function resolveIdentity(array $properties = []): IdentityInterface
    {
        throw new \RuntimeException($this->message);
    }
}
