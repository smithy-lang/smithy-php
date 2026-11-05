<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Async;

use PHPUnit\Framework\TestCase;
use Smithy\Async\Async;
use Smithy\Tests\Async\Utils\RecordingEventLoopFixture;

final class AsyncTest extends TestCase
{
    protected function tearDown(): void
    {
        $reset = \Closure::bind(
            static function (): void {
                Async::$eventLoop = null;
            },
            null,
            Async::class
        );
        $reset();
    }

    public function testAllReturnsResultsInInputOrder(): void
    {
        $results = Async::all(
            static function (): string {
                Async::delay(0.002);

                return 'first';
            },
            static function (): string {
                Async::delay(0.001);

                return 'second';
            }
        );

        $this->assertSame(['first', 'second'], $results);
    }

    public function testAllReturnsEmptyArrayWhenNoOperationsAreProvided(): void
    {
        $this->assertSame([], Async::all());
    }

    public function testAllPropagatesFirstExceptionByInputOrder(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('first failure');

        Async::all(
            static function (): never {
                Async::delay(0.002);
                throw new \RuntimeException('first failure');
            },
            static function (): never {
                throw new \RuntimeException('second failure');
            }
        );
    }

    public function testMapDefaultsToTwentyFiveWorkers(): void
    {
        $method = new \ReflectionMethod(Async::class, 'map');
        $concurrency = $method->getParameters()[2];

        $this->assertSame(25, $concurrency->getDefaultValue());
    }

    public function testMapPreservesInputOrderWithIndexCursor(): void
    {
        $results = Async::map(
            [
                'first' => 3,
                'second' => 1,
                'third' => 2
            ],
            static fn(int $value, string $key): string =>
                "{$key}:{$value}",
            concurrency: 2
        );

        $this->assertSame(
            ['first:3', 'second:1', 'third:2'],
            $results
        );
    }

    public function testMapPullsGeneratorItemsLazily(): void
    {
        $pulled = 0;
        $pulledBeforeFirstCallback = null;
        $items = static function () use (&$pulled): \Generator {
            for ($i = 0; $i < 10; $i++) {
                $pulled++;
                yield $i => $i;
            }
        };

        $results = Async::map(
            $items(),
            static function (int $value) use (
                &$pulled,
                &$pulledBeforeFirstCallback
            ): int {
                $pulledBeforeFirstCallback ??= $pulled;
                Async::delay(0.001);

                return $value * 2;
            },
            concurrency: 3
        );

        $this->assertLessThan(10, $pulledBeforeFirstCallback);
        $this->assertSame(10, $pulled);
        $this->assertSame(
            \array_map(
                static fn(int $value): int => $value * 2,
                \range(0, 9)
            ),
            $results
        );
    }

    public function testMapSupportsSuspendingGeneratorInput(): void
    {
        $items = static function (): \Generator {
            for ($i = 0; $i < 6; $i++) {
                Async::delay(0.001);
                yield $i => $i + 1;
            }
        };

        $results = Async::map(
            $items(),
            static fn(int $value): int => $value * 2,
            concurrency: 3
        );

        $this->assertSame([2, 4, 6, 8, 10, 12], $results);
    }

    public function testMapSharesCursorAcrossSuspendingWorkers(): void
    {
        $active = 0;
        $maxActive = 0;
        $items = \range(1, 20);

        $results = Async::map(
            $items,
            static function (int $value) use (
                &$active,
                &$maxActive
            ): int {
                $active++;
                $maxActive = \max($maxActive, $active);
                Async::delay(0.001);
                $active--;

                return $value * 2;
            },
            concurrency: 4
        );

        $this->assertSame(4, $maxActive);
        $this->assertSame(
            \array_map(
                static fn(int $value): int => $value * 2,
                $items
            ),
            $results
        );
    }

    public function testMapReturnsEmptyArrayForEmptyInput(): void
    {
        $loop = new RecordingEventLoopFixture();
        Async::configure($loop);

        $this->assertSame(
            [],
            Async::map(
                [],
                static fn(mixed $value): mixed => $value
            )
        );
        $this->assertSame(0, $loop->runCalls);
    }

    public function testMapRejectsInvalidConcurrency(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Concurrency must be at least 1.');

        Async::map(
            [1],
            static fn(int $value): int => $value,
            concurrency: 0
        );
    }

    public function testMapRetainsEveryResultAcrossRepeatedSuspensions(): void
    {
        $items = \range(0, 99);

        $results = Async::map(
            $items,
            static function (int $value): array {
                Async::delay(0.001);
                Async::delay(0.001);

                return [$value, $value * 2];
            },
            concurrency: 10
        );

        $this->assertCount(\count($items), $results);
        $this->assertSame(
            \array_map(
                static fn(int $value): array => [
                    $value,
                    $value * 2
                ],
                $items
            ),
            $results
        );
    }

    public function testConfigureUsesCustomEventLoop(): void
    {
        $loop = new RecordingEventLoopFixture();
        Async::configure($loop);

        $results = Async::all(
            static fn(): string => 'first',
            static fn(): string => 'second'
        );

        $this->assertSame(['first', 'second'], $results);
        $this->assertSame(1, $loop->runCalls);
        $this->assertCount(2, $loop->receivedCallbacks);
    }

    public function testDelayUsesConfiguredLoopForPositiveDelay(): void
    {
        $loop = new RecordingEventLoopFixture();
        Async::configure($loop);

        Async::delay(1.25);

        $this->assertSame([1.25], $loop->delays);
    }

    public function testDelayIgnoresZeroAndNegativeValues(): void
    {
        $loop = new RecordingEventLoopFixture();
        Async::configure($loop);

        Async::delay(0.0);
        Async::delay(-1.0);

        $this->assertSame([], $loop->delays);
    }
}
