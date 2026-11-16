<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Benchmarks\Serde;

use PHPUnit\Framework\TestCase;
use Smithy\Benchmarks\Serde\Support\BenchmarkWireComparator;

require_once __DIR__
    . '/../../../benchmarks/serde/support/BenchmarkValueComparator.php';
require_once __DIR__
    . '/../../../benchmarks/serde/support/BenchmarkWireComparator.php';

final class BenchmarkWireComparatorTest extends TestCase
{
    public function testComparesRawPayloadsByteForByte(): void
    {
        self::assertTrue(BenchmarkWireComparator::equivalent(
            'application/octet-stream',
            '5A==',
            '5A=='
        ));
        self::assertFalse(BenchmarkWireComparator::equivalent(
            'application/octet-stream',
            '5A==',
            '5A==' . "\n"
        ));
    }

    public function testAnAbsentFixtureBodyRequiresAnEmptyBody(): void
    {
        self::assertTrue(BenchmarkWireComparator::equivalent('', '', ''));
        self::assertFalse(BenchmarkWireComparator::equivalent('', '', '{}'));
    }

    public function testIgnoresJsonMemberOrder(): void
    {
        self::assertTrue(BenchmarkWireComparator::equivalent(
            'application/json',
            '{"a":1,"b":2}',
            '{"b":2,"a":1}'
        ));
    }
}
