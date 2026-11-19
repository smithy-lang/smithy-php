<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Auth\{
    AnonymousIdentity,
    AnonymousIdentityResolver
};

#[CoversClass(AnonymousIdentityResolver::class)]
final class AnonymousIdentityResolverTest extends TestCase
{
    public function testResolvesSharedAnonymousIdentity(): void
    {
        $resolver = AnonymousIdentityResolver::instance();

        self::assertSame($resolver, AnonymousIdentityResolver::instance());
        self::assertSame(
            AnonymousIdentity::instance(),
            $resolver->resolveIdentity()->unwrap()
        );
    }
}
