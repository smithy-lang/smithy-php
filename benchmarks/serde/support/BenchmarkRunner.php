<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Benchmarks\Serde\Support;

/**
 * Runs warmup and measured benchmark iterations and summarizes timings.
 */
final class BenchmarkRunner
{
    public function __construct(
        private readonly int $warmupIterations = 500,
        private readonly int $minIterations = 1_000,
        private readonly int $maxIterations = 5_000,
        private readonly float $maxSeconds = 5.0
    ) {
        if ($warmupIterations < 0
            || $minIterations < 1
            || $maxIterations < $minIterations
            || $maxSeconds <= 0
        ) {
            throw new \InvalidArgumentException(
                'Invalid benchmark iteration settings.'
            );
        }
    }

    /**
     * @return array<string, int|float|null>
     */
    public function run(callable $benchmark): array
    {
        for ($i = 0; $i < $this->warmupIterations; $i++) {
            $benchmark();
        }

        $timings = [];
        $wallStart = \hrtime(true);
        $cpuStart = self::cpuTimeNanoseconds();
        $maxNanoseconds = (int) ($this->maxSeconds * 1_000_000_000);

        for ($i = 0; $i < $this->maxIterations; $i++) {
            $start = \hrtime(true);
            $benchmark();
            $timings[] = \hrtime(true) - $start;

            if (\count($timings) >= $this->minIterations
                && \hrtime(true) - $wallStart >= $maxNanoseconds
            ) {
                break;
            }
        }

        $wallNanoseconds = \hrtime(true) - $wallStart;
        $cpuEnd = self::cpuTimeNanoseconds();
        $statistics = self::calculateStatistics($timings);
        $statistics['wall_time_ns'] = $wallNanoseconds;
        $statistics['cpu_time_ns'] = $cpuStart === null || $cpuEnd === null
            ? null
            : $cpuEnd - $cpuStart;
        $statistics['operations_per_second'] = $wallNanoseconds === 0
            ? 0.0
            : \count($timings) * 1_000_000_000 / $wallNanoseconds;
        $statistics['ops_per_cpu_sec'] =
            self::calculateOpsPerCpuSecond(
                \count($timings),
                $statistics['cpu_time_ns']
            );

        return $statistics;
    }

    /**
     * @return array{
     *     warmup_iterations: int,
     *     min_iterations: int,
     *     max_iterations: int,
     *     max_seconds: float
     * }
     */
    public function settings(): array
    {
        return [
            'warmup_iterations' => $this->warmupIterations,
            'min_iterations' => $this->minIterations,
            'max_iterations' => $this->maxIterations,
            'max_seconds' => $this->maxSeconds,
        ];
    }

    /**
     * @param list<int|float> $timings
     *
     * @return array{
     *     iterations: int,
     *     mean_ns: float,
     *     p50_ns: int|float,
     *     p90_ns: int|float,
     *     p95_ns: int|float,
     *     p99_ns: int|float,
     *     stddev_ns: float
     * }
     */
    public static function calculateStatistics(array $timings): array
    {
        if ($timings === []) {
            throw new \InvalidArgumentException(
                'At least one timing sample is required.'
            );
        }

        \sort($timings, SORT_NUMERIC);
        $count = \count($timings);
        $mean = (float) (\array_sum($timings) / $count);
        $squaredDifference = 0.0;
        foreach ($timings as $timing) {
            $squaredDifference += ($timing - $mean) ** 2;
        }

        return [
            'iterations' => $count,
            'mean_ns' => $mean,
            'p50_ns' => self::percentile($timings, 0.50),
            'p90_ns' => self::percentile($timings, 0.90),
            'p95_ns' => self::percentile($timings, 0.95),
            'p99_ns' => self::percentile($timings, 0.99),
            'stddev_ns' => \sqrt($squaredDifference / $count),
        ];
    }

    public static function calculateOpsPerCpuSecond(
        int $operations,
        ?int $cpuNanoseconds
    ): ?float
    {
        if ($operations < 0) {
            throw new \InvalidArgumentException(
                'Operation count cannot be negative.'
            );
        }
        if ($cpuNanoseconds === null || $cpuNanoseconds <= 0) {
            return null;
        }

        return $operations * 1_000_000_000 / $cpuNanoseconds;
    }

    /**
     * @param list<int|float> $sorted
     */
    private static function percentile(array $sorted, float $percentile): int|float
    {
        $index = (int) \floor((\count($sorted) - 1) * $percentile);

        return $sorted[$index];
    }

    private static function cpuTimeNanoseconds(): ?int
    {
        if (!\function_exists('getrusage')) {
            return null;
        }

        $usage = \getrusage();
        if (!\is_array($usage)) {
            return null;
        }

        return (($usage['ru_utime.tv_sec'] ?? 0)
                + ($usage['ru_stime.tv_sec'] ?? 0)) * 1_000_000_000
            + (($usage['ru_utime.tv_usec'] ?? 0)
                + ($usage['ru_stime.tv_usec'] ?? 0)) * 1_000;
    }
}
