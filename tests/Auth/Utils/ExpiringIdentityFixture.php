<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Auth\Utils;

use Smithy\Auth\IdentityInterface;

/**
 * Test identity with an access key ID and an expiration time.
 */
final class ExpiringIdentityFixture implements IdentityInterface
{
    public function __construct(
        public readonly string $accessKeyId,
        private readonly ?float $expiration
    ) {}

    public function getExpirationTime(): ?float
    {
        return $this->expiration;
    }
}
