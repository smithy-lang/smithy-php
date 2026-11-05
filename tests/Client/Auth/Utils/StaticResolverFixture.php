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
 * @template TIdentity of IdentityInterface
 * @implements IdentityResolverInterface<TIdentity>
 */
final class StaticResolverFixture implements IdentityResolverInterface
{
    /** @param TIdentity $identity */
    public function __construct(private readonly IdentityInterface $identity) {}

    /** @return IdentityResult<TIdentity> */
    public function resolveIdentity(array $properties = []): IdentityResult
    {
        return IdentityResult::success($this->identity);
    }

    public function invalidate(IdentityInterface $rejectedIdentity): void {}
}
