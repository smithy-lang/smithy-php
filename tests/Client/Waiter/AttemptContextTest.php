<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Waiter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Waiter\AttemptContext;
use Smithy\Tests\Client\Pagination\Fixture\PaginationResult;
use Smithy\Tests\Client\Waiter\Fixture\WaiterModeledError;

#[CoversClass(AttemptContext::class)]
final class AttemptContextTest extends TestCase
{
    public function testRepresentsSuccessfulAttemptData(): void
    {
        $attempt = new AttemptContext(
            input: new PaginationResult('request'),
            output: new PaginationResult('done', ['ready'])
        );

        self::assertTrue($attempt->isSuccess());
        self::assertSame('request', $attempt->inputData()['nextToken']);
        self::assertSame('done', $attempt->outputData()['nextToken']);
        self::assertSame(
            ['input', 'output'],
            \array_keys($attempt->inputOutputData())
        );
    }

    public function testRepresentsModeledErrorAttempt(): void
    {
        $error = new WaiterModeledError();
        $attempt = new AttemptContext(
            input: new PaginationResult('request'),
            error: $error
        );

        self::assertFalse($attempt->isSuccess());
        self::assertSame($error, $attempt->error);
        self::assertNull($attempt->outputData());
        self::assertNull($attempt->inputOutputData());
    }

    public function testRequiresOutputOrError(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('exactly one output or error');

        new AttemptContext(input: new PaginationResult('request'));
    }

    public function testRejectsOutputAndErrorTogether(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('exactly one output or error');

        new AttemptContext(
            input: new PaginationResult('request'),
            output: new PaginationResult('done'),
            error: new WaiterModeledError()
        );
    }
}
