<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Auth\Utils;

use Smithy\Client\Auth\{
    IdentityResolverInterface,
    IdentityResult
};
final class CountingThrowingResolverFixture implements IdentityResolverInterface
{
    public int $calls = 0;

    public function __construct(private readonly string $message) {}

    public function resolveIdentity(array $properties = []): IdentityResult
    {
        $this->calls++;

        throw new \RuntimeException($this->message);
    }
}
