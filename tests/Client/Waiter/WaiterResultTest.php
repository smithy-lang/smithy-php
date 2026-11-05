<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Waiter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Waiter\WaiterResult;
use Smithy\Tests\Client\Pagination\Fixture\PaginationResult;
use Smithy\Tests\Client\Waiter\Fixture\WaiterModeledError;

#[CoversClass(WaiterResult::class)]
final class WaiterResultTest extends TestCase
{
    public function testPreservesSuccessfulOutput(): void
    {
        $output = new PaginationResult('done');
        $result = WaiterResult::fromOutput($output);

        self::assertSame($output, $result->output);
        self::assertNull($result->error);
    }

    public function testPreservesModeledErrorAcceptedAsSuccess(): void
    {
        $error = new WaiterModeledError();
        $result = WaiterResult::fromError($error);

        self::assertNull($result->output);
        self::assertSame($error, $result->error);
    }
}
