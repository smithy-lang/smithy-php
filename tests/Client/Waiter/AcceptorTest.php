<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Waiter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Waiter\{
    Acceptor,
    Matcher
};

#[CoversClass(Acceptor::class)]
final class AcceptorTest extends TestCase
{
    public function testPreservesStateAndMatcher(): void
    {
        $matcher = Matcher::success(true);
        $acceptor = new Acceptor(Acceptor::SUCCESS, $matcher);

        self::assertSame(Acceptor::SUCCESS, $acceptor->state);
        self::assertSame($matcher, $acceptor->matcher);
    }

    public function testStateConstantsHaveStableValues(): void
    {
        self::assertSame(0, Acceptor::SUCCESS);
        self::assertSame(1, Acceptor::FAILURE);
        self::assertSame(2, Acceptor::RETRY);
    }

    public function testRejectsUnknownState(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown waiter acceptor state: 3');

        new Acceptor(3, Matcher::success(true));
    }

    public function testRejectsMessageOnNonFailureState(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('messages require a failure state');

        new Acceptor(
            Acceptor::SUCCESS,
            Matcher::success(true),
            'reason'
        );
    }
}
