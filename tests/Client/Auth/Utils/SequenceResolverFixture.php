<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Auth\Utils;

use Smithy\Client\Auth\{
    IdentityInterface,
    IdentityResolverInterface
};

/**
 * Test resolver that returns each identity in the given sequence, one
 * per `resolveIdentity` call.
 */
final class SequenceResolverFixture implements IdentityResolverInterface
{
    public int $calls = 0;

    /** @param list<IdentityInterface> $sequence */
    public function __construct(private readonly array $sequence) {}

    public function resolveIdentity(array $properties = []): IdentityInterface
    {
        $identity = $this->sequence[$this->calls]
            ?? $this->sequence[\array_key_last($this->sequence)];
        $this->calls++;

        return $identity;
    }
}
