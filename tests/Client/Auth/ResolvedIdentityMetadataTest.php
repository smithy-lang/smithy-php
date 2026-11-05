<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Auth\ResolvedIdentityMetadata;

#[CoversClass(ResolvedIdentityMetadata::class)]
final class ResolvedIdentityMetadataTest extends TestCase
{
    public function testStoresProviderMetadata(): void
    {
        $metadata = new ResolvedIdentityMetadata(
            'provider',
            ['feature']
        );

        self::assertSame('provider', $metadata->providerName);
        self::assertSame(['feature'], $metadata->featureIds);
    }

    public function testRejectsEmptyProviderName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must not be empty');

        new ResolvedIdentityMetadata('');
    }
}
