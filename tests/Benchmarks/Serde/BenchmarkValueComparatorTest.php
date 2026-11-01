<?php

declare(strict_types=1);

namespace Smithy\Tests\Benchmarks\Serde;

use PHPUnit\Framework\TestCase;
use Smithy\Benchmarks\Serde\Support\BenchmarkValueComparator;

require_once __DIR__
    . '/../../../benchmarks/serde/support/BenchmarkValueComparator.php';

final class BenchmarkValueComparatorTest extends TestCase
{
    public function testRequiresMatchingTypesAndKeyOrder(): void
    {
        self::assertFalse(BenchmarkValueComparator::equivalent(true, 1));
        self::assertFalse(BenchmarkValueComparator::equivalent(
            ['a' => 1, 'b' => 2],
            ['b' => 2, 'a' => 1]
        ));
    }

    public function testAllowsEquivalentNumericRepresentations(): void
    {
        self::assertTrue(BenchmarkValueComparator::equivalent(
            ['value' => '123.5'],
            ['value' => 123.5]
        ));
        self::assertTrue(BenchmarkValueComparator::equivalent(
            ['value' => '123.5000'],
            ['value' => '1.235e2']
        ));
    }

    public function testComparesArbitraryPrecisionValuesLosslessly(): void
    {
        self::assertFalse(BenchmarkValueComparator::equivalent(
            '123456789012345678901',
            '123456789012345678902'
        ));
        self::assertFalse(BenchmarkValueComparator::equivalent(
            '1234567890.123456789',
            '1234567890.123456788'
        ));
    }
}
