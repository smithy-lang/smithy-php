<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Retry;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Retry\{
    RetryState,
    TokenAcquisitionFailedException
};
use Smithy\Core\Exception\ErrorFault;

#[CoversClass(TokenAcquisitionFailedException::class)]
final class TokenAcquisitionFailedExceptionTest extends TestCase
{
    public function testDefaultsToClientFault(): void
    {
        $exception = new TokenAcquisitionFailedException('retry failed');

        self::assertSame('retry failed', $exception->getMessage());
        self::assertSame(ErrorFault::Client, $exception->fault);
        self::assertTrue($exception->isClientFault());
        self::assertNull($exception->getPrevious());
        self::assertNull($exception->state);
        self::assertSame(0.0, $exception->delay);
    }

    public function testStoresRetryFailureContext(): void
    {
        $previous = new \RuntimeException('request failed');
        $state = new RetryState('service', attempt: 3);
        $exception = new TokenAcquisitionFailedException(
            message: 'retry exhausted',
            previous: $previous,
            state: $state,
            delay: 1.25
        );

        self::assertSame($previous, $exception->getPrevious());
        self::assertSame($state, $exception->state);
        self::assertSame(1.25, $exception->delay);
    }
}
