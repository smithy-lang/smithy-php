<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Retry;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Retry\RetryContextKeys;

#[CoversClass(RetryContextKeys::class)]
final class RetryContextKeysTest extends TestCase
{
    public function testRequestFlagResolverKeyIsShared(): void
    {
        $this->assertSame(
            RetryContextKeys::requestFlagResolver(),
            RetryContextKeys::requestFlagResolver()
        );
        $this->assertSame(
            'smithy.client.retryRequestFlagResolver',
            RetryContextKeys::requestFlagResolver()->name
        );
    }
}
