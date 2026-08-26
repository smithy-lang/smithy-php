<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Auth\Utils;

use Smithy\Client\Auth\{
    IdentityInterface,
    IdentityResolverInterface
};

/**
 * Test resolver that returns or throws each entry in sequence.
 */
final class FlakyResolverFixture implements IdentityResolverInterface
{
    public int $calls = 0;

    /** @param list<IdentityInterface|\Throwable> $sequence */
    public function __construct(private readonly array $sequence) {}

    public function resolveIdentity(array $properties = []): IdentityInterface
    {
        $entry = $this->sequence[$this->calls] ?? null;
        $this->calls++;
        if ($entry instanceof \Throwable) {
            throw $entry;
        }

        return $entry;
    }
}
