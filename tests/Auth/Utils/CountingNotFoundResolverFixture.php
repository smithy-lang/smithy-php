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
 * Test resolver that always reports an expected miss and counts calls.
 *
 * @implements IdentityResolverInterface<never>
 */
final class CountingNotFoundResolverFixture implements
    IdentityResolverInterface
{
    public int $calls = 0;

    public function __construct(private readonly string $message) {}

    public function resolveIdentity(array $properties = []): IdentityResult
    {
        $this->calls++;

        return IdentityResult::notFound(self::class, $this->message);
    }

    public function invalidate(IdentityInterface $rejectedIdentity): void {}
}
