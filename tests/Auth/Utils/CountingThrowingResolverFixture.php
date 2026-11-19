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
 * @implements IdentityResolverInterface<never>
 */
final class CountingThrowingResolverFixture implements IdentityResolverInterface
{
    public int $calls = 0;

    public function __construct(private readonly string $message) {}

    public function resolveIdentity(array $properties = []): IdentityResult
    {
        $this->calls++;

        throw new \RuntimeException($this->message);
    }

    public function invalidate(IdentityInterface $rejectedIdentity): void {}
}
