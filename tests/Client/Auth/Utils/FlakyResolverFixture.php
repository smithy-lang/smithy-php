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
 * Test resolver that returns or throws each entry in sequence.
 *
 * @template TIdentity of IdentityInterface
 * @implements IdentityResolverInterface<TIdentity>
 */
final class FlakyResolverFixture implements IdentityResolverInterface
{
    public int $calls = 0;

    /**
     * @param list<TIdentity|IdentityResult<TIdentity>|\Throwable> $sequence
     */
    public function __construct(private readonly array $sequence) {}

    /** @return IdentityResult<TIdentity> */
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

    public function invalidate(IdentityInterface $rejectedIdentity): void {}
}
