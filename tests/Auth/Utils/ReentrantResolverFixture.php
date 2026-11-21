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
 * Test resolver that calls another resolver while it resolves, standing in
 * for a second caller that arrives during a refresh.
 *
 * @template TIdentity of IdentityInterface
 * @implements IdentityResolverInterface<TIdentity>
 */
final class ReentrantResolverFixture implements IdentityResolverInterface
{
    public int $calls = 0;

    /** @var IdentityResult<IdentityInterface>|null */
    public ?IdentityResult $reentrantResult = null;

    public ?IdentityResolverInterface $caller = null;

    /** @param TIdentity $identity */
    public function __construct(private readonly IdentityInterface $identity) {}

    /** @return IdentityResult<TIdentity> */
    public function resolveIdentity(array $properties = []): IdentityResult
    {
        $this->calls++;
        if ($this->caller !== null) {
            $caller = $this->caller;
            $this->caller = null;
            $this->reentrantResult = $caller->resolveIdentity($properties);
        }

        return IdentityResult::success($this->identity);
    }

    public function invalidate(IdentityInterface $rejectedIdentity): void {}
}
