<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Auth\Utils;

use Smithy\Client\Auth\{
    IdentityInterface,
    IdentityResolverInterface,
    IdentityResult
};

/**
 * Test resolver that returns or throws each entry in sequence.
 */
final class FlakyResolverFixture implements IdentityResolverInterface
{
    public int $calls = 0;

    /** @param list<IdentityInterface|IdentityResult|\Throwable> $sequence */
    public function __construct(private readonly array $sequence) {}

    public function resolveIdentity(array $properties = []): IdentityResult
    {
        $entry = $this->sequence[$this->calls] ?? null;
        $this->calls++;
        if ($entry instanceof \Throwable) {
            throw $entry;
        }

        return $entry instanceof IdentityResult
            ? $entry
            : IdentityResult::success($entry);
    }
}
