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
 * Test resolver that returns or throws the next queued response on each call.
 *
 * @template TIdentity of IdentityInterface
 * @implements IdentityResolverInterface<TIdentity>
 */
final class ScriptedResolverFixture implements IdentityResolverInterface
{
    public int $calls = 0;

    /** @var list<IdentityResult<TIdentity>|\Throwable> */
    private array $queue = [];

    /** @param IdentityResult<TIdentity>|\Throwable $response */
    public function push(IdentityResult|\Throwable $response): void
    {
        $this->queue[] = $response;
    }

    public function clear(): void
    {
        $this->queue = [];
    }

    /** @return IdentityResult<TIdentity> */
    public function resolveIdentity(array $properties = []): IdentityResult
    {
        $this->calls++;
        $response = \array_shift($this->queue)
            ?? throw new \LogicException('No scripted response queued.');
        if ($response instanceof \Throwable) {
            throw $response;
        }

        return $response;
    }

    public function invalidate(IdentityInterface $rejectedIdentity): void {}
}
