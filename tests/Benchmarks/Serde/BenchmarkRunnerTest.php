<?php

declare(strict_types=1);

namespace Smithy\Tests\Benchmarks\Serde;

use PHPUnit\Framework\TestCase;
use Smithy\Benchmarks\Serde\Support\BenchmarkRunner;

require_once __DIR__ . '/../../../benchmarks/serde/support/BenchmarkRunner.php';

final class BenchmarkRunnerTest extends TestCase
{
    public function testCalculatesTimingStatistics(): void
    {
        self::assertSame(
            [
                'iterations' => 5,
                'mean_ns' => 30.0,
                'p50_ns' => 30,
                'p90_ns' => 40,
                'p95_ns' => 40,
                'p99_ns' => 40,
                'stddev_ns' => 14.142135623730951,
            ],
            BenchmarkRunner::calculateStatistics([50, 10, 40, 20, 30])
        );
    }

    public function testRunsWarmupAndMeasuredIterations(): void
    {
        $calls = 0;
        $runner = new BenchmarkRunner(
            warmupIterations: 2,
            minIterations: 3,
            maxIterations: 3,
            maxSeconds: 1
        );

        $result = $runner->run(static function () use (&$calls): void {
            $calls++;
        });

        self::assertSame(5, $calls);
        self::assertSame(3, $result['iterations']);
        self::assertGreaterThan(0, $result['operations_per_second']);
        if ($result['cpu_time_ns'] === null
            || $result['cpu_time_ns'] === 0
        ) {
            self::assertNull($result['ops_per_cpu_sec']);
        } else {
            self::assertGreaterThan(0, $result['ops_per_cpu_sec']);
        }
    }

    public function testCalculatesCpuNormalizedThroughput(): void
    {
        self::assertSame(
            2_000_000_000.0,
            BenchmarkRunner::calculateOpsPerCpuSecond(4, 2)
        );
        self::assertNull(
            BenchmarkRunner::calculateOpsPerCpuSecond(4, null)
        );
        self::assertNull(
            BenchmarkRunner::calculateOpsPerCpuSecond(4, 0)
        );
    }
}
