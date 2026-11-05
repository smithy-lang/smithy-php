<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Auth\AnonymousIdentity;

#[CoversClass(AnonymousIdentity::class)]
final class AnonymousIdentityTest extends TestCase
{
    public function testReturnsSharedNonExpiringIdentity(): void
    {
        $identity = AnonymousIdentity::instance();

        self::assertSame($identity, AnonymousIdentity::instance());
        self::assertNull($identity->getExpirationTime());
    }
}
