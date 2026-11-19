<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Auth\Utils;

use Smithy\Auth\{
    IdentityInterface,
    IdentityResolverInterface,
    IdentityResult
};
/**
 * Test resolver that always returns the same identity and counts calls.
 *
 * @template TIdentity of IdentityInterface
 * @implements IdentityResolverInterface<TIdentity>
 */
final class CountingResolverFixture implements IdentityResolverInterface
{
    public int $calls = 0;

    /** @var list<IdentityInterface> */
    public array $invalidated = [];

    /** @param TIdentity $identity */
    public function __construct(private readonly IdentityInterface $identity) {}

    /** @return IdentityResult<TIdentity> */
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
