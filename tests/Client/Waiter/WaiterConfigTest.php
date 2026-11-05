<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Waiter;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Client\RequestOverrideConfig;
use Smithy\Client\Waiter\WaiterConfig;
final class WaiterConfigTest extends TestCase
{
    public function testStoresOptionalDelayOverrides(): void
    {
        $overrides = RequestOverrideConfig::fromArray([
            '@http' => ['headers' => ['x-waiter' => 'value']]
        ]);
        $config = new WaiterConfig(
            minDelay: 3,
            maxDelay: 30,
            requestOverrides: $overrides
        );

        self::assertSame(3, $config->minDelay);
        self::assertSame(30, $config->maxDelay);
        self::assertSame($overrides, $config->requestOverrides);
    }

    /**
     * @return iterable<string, array{?int, ?int}>
     */
    public static function invalidDelays(): iterable
    {
        yield 'minimum below one' => [0, null];
        yield 'maximum below one' => [null, 0];
        yield 'maximum below minimum' => [3, 2];
    }

    #[DataProvider('invalidDelays')]
    public function testRejectsInvalidDelayOverrides(
        ?int $minDelay,
        ?int $maxDelay
    ): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new WaiterConfig($minDelay, $maxDelay);
    }
}
