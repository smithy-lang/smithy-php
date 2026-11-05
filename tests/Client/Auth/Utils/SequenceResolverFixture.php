<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Auth\Utils;

use Smithy\Client\Auth\{
    IdentityInterface,
    IdentityResolverInterface,
    IdentityResult
};

/**
 * Test resolver that returns each identity in the given sequence, one
 * per `resolveIdentity` call.
 *
 * @template TIdentity of IdentityInterface
 * @implements IdentityResolverInterface<TIdentity>
 */
final class SequenceResolverFixture implements IdentityResolverInterface
{
    public int $calls = 0;

    /** @var list<IdentityInterface> */
    public array $invalidated = [];

    /** @param list<TIdentity> $sequence */
    public function __construct(private readonly array $sequence) {}

    /** @return IdentityResult<TIdentity> */
    public function resolveIdentity(array $properties = []): IdentityResult
    {
        $identity = $this->sequence[$this->calls]
            ?? $this->sequence[\array_key_last($this->sequence)];
        $this->calls++;

        return IdentityResult::success($identity);
    }

    public function invalidate(IdentityInterface $rejectedIdentity): void
    {
        $this->invalidated[] = $rejectedIdentity;
    }
}
