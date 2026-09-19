<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Auth\Utils;

use Smithy\Client\Auth\{
    IdentityInterface,
    IdentityResolverInterface,
    IdentityResult
};
/**
 * Test resolver that always returns the same identity and counts calls.
 */
final class CountingResolverFixture implements IdentityResolverInterface
{
    public int $calls = 0;

    /** @var list<IdentityInterface> */
    public array $invalidated = [];

    public function __construct(private readonly IdentityInterface $identity) {}

    public function resolveIdentity(array $properties = []): IdentityResult
    {
        $this->calls++;

        return IdentityResult::success($this->identity);
    }

    public function invalidate(IdentityInterface $rejectedIdentity): void
    {
        $this->invalidated[] = $rejectedIdentity;
    }
}
