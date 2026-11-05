<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Async;

use PHPUnit\Framework\TestCase;
use Smithy\Async\RevoltEventLoop;

final class RevoltEventLoopTest extends TestCase
{
    public function testRunsScheduledCallback(): void
    {
        $loop = new RevoltEventLoop();
        $fired = false;

        $loop->suspend(
            static function (
                callable $resume,
                callable $throw
            ) use ($loop, &$fired): void {
                $loop->schedule(
                    0.001,
                    static function () use (&$fired, $resume): void {
                        $fired = true;
                        $resume(null);
                    }
                );
            }
        );

        $this->assertTrue($fired);
    }

    public function testCancelsScheduledCallback(): void
    {
        $loop = new RevoltEventLoop();
        $cancelledCallbackFired = false;
        $callbackId = $loop->schedule(
            0.001,
            static function () use (
                &$cancelledCallbackFired
            ): void {
                $cancelledCallbackFired = true;
            }
        );
        $loop->cancel($callbackId);
        $loop->suspend(
            static function (
                callable $resume,
                callable $throw
            ) use ($loop): void {
                $loop->schedule(
                    0.005,
                    static function () use ($resume): void {
                        $resume(null);
                    }
                );
            }
        );

        $this->assertFalse($cancelledCallbackFired);
    }

    public function testRunRetainsResultsFromSuspendingCallbacks(): void
    {
        $loop = new RevoltEventLoop();
        $callbacks = [];

        foreach (\range(0, 19) as $index) {
            $callbacks[] = static function () use (
                $loop,
                $index
            ): int {
                $loop->delay(0.001);

                return $index;
            };
        }

        $this->assertSame(\range(0, 19), $loop->run($callbacks));
    }

    public function testRunPreservesAssociativeInputOrder(): void
    {
        $loop = new RevoltEventLoop();

        $this->assertSame(
            ['second', 'first'],
            $loop->run([
                'second' => static fn(): string => 'second',
                'first' => static fn(): string => 'first'
            ])
        );
    }

    public function testRunThrowsFirstExceptionByInputOrder(): void
    {
        $loop = new RevoltEventLoop();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('second');

        $loop->run([
            'second' => static function (): never {
                throw new \RuntimeException('second');
            },
            'first' => static function (): never {
                throw new \RuntimeException('first');
            }
        ]);
    }

    public function testSuspendResumesWithValue(): void
    {
        $loop = new RevoltEventLoop();

        $this->assertSame(
            ['done'],
            $loop->run([
                static fn(): mixed => $loop->suspend(
                    static function (
                        callable $resume,
                        callable $throw
                    ): void {
                        $resume('done');
                    }
                )
            ])
        );
    }

    public function testSuspendInvokesSetupCallbackPositionally(): void
    {
        $loop = new RevoltEventLoop();

        $this->assertSame(
            ['done'],
            $loop->run([
                static fn(): mixed => $loop->suspend(
                    static function (
                        callable $resolve,
                        callable $reject
                    ): void {
                        $resolve('done');
                    }
                )
            ])
        );
    }

    public function testSuspendThrowsIntoFiber(): void
    {
        $loop = new RevoltEventLoop();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('boom');

        $loop->run([
            static fn(): mixed => $loop->suspend(
                static function (
                    callable $resume,
                    callable $throw
                ): void {
                    $throw(new \RuntimeException('boom'));
                }
            )
        ]);
    }

    public function testSuspendPropagatesSetupFailure(): void
    {
        $loop = new RevoltEventLoop();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('setup failed');

        $loop->run([
            static fn(): mixed => $loop->suspend(
                static function (
                    callable $resume,
                    callable $throw
                ): void {
                    throw new \RuntimeException('setup failed');
                }
            )
        ]);
    }

    public function testDeferRunsCallback(): void
    {
        $loop = new RevoltEventLoop();
        $ran = false;

        $loop->suspend(
            static function (
                callable $resume,
                callable $throw
            ) use ($loop, &$ran): void {
                $loop->defer(
                    static function () use (&$ran, $resume): void {
                        $ran = true;
                        $resume(null);
                    }
                );
            }
        );

        $this->assertTrue($ran);
    }
}
