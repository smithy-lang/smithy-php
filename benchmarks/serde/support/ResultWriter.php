<?php

declare(strict_types=1);

namespace Smithy\Benchmarks\Serde\Support;

/**
 * Writes raw benchmark results and the summarized README table.
 */
final class ResultWriter
{
    private const START_MARKER = '<!-- benchmark-results:start -->';
    private const END_MARKER = '<!-- benchmark-results:end -->';
    private const CLIENT_START_MARKER =
        '<!-- client-benchmark-results:start -->';
    private const CLIENT_END_MARKER =
        '<!-- client-benchmark-results:end -->';

    /**
     * @param array<string, mixed> $results
     */
    public function writeJson(
        array $results,
        string $directory,
        string $name = 'serde-rpcv2-cbor'
    ): string {
        if (!\is_dir($directory)
            && !\mkdir($directory, 0777, true)
        ) {
            throw new \RuntimeException(
                "Unable to create benchmark result directory: {$directory}"
            );
        }

        $path = $directory . '/' . $name . '-'
            . \date('Ymd-His')
            . '.json';
        $json = \json_encode(
            $results,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
        if (\file_put_contents($path, $json . "\n") === false) {
            throw new \RuntimeException(
                "Unable to write benchmark results: {$path}"
            );
        }

        return $path;
    }

    /**
     * @param array<string, mixed> $results
     */
    public function updateReadme(array $results, string $readmePath): void
    {
        $this->replaceReadmeSection(
            $readmePath,
            self::START_MARKER,
            self::END_MARKER,
            $this->renderMarkdown($results)
        );
    }

    /**
     * @param array<string, mixed> $results
     */
    public function updateClientReadme(
        array $results,
        string $readmePath
    ): void {
        $this->replaceReadmeSection(
            $readmePath,
            self::CLIENT_START_MARKER,
            self::CLIENT_END_MARKER,
            $this->renderClientMarkdown($results)
        );
    }

    /**
     * @param array<string, mixed> $results
     */
    public function renderMarkdown(array $results): string
    {
        return "### Serde\n\n"
            . $this->renderResults(
                $results,
                $results['serde_benchmarks'] ?? [],
                true
            );
    }

    /**
     * @param array<string, mixed> $results
     */
    public function renderClientMarkdown(array $results): string
    {
        return $this->renderResults(
            $results,
            $results['client_benchmarks'] ?? [],
            false
        );
    }

    /**
     * Convert internal timing names to the shared serde result schema.
     *
     * @param array<string, int|float|null> $statistics
     *
     * @return array<string, int|float|string|null>
     */
    public static function benchmarkEntry(
        string $id,
        array $statistics
    ): array {
        return [
            'id' => $id,
            'n' => $statistics['iterations'],
            'mean' => $statistics['mean_ns'],
            'p50' => $statistics['p50_ns'],
            'p90' => $statistics['p90_ns'],
            'p95' => $statistics['p95_ns'],
            'p99' => $statistics['p99_ns'],
            'std_dev' => $statistics['stddev_ns'],
            'ops_per_cpu_sec' => $statistics['ops_per_cpu_sec'],
        ];
    }

    public static function formatDuration(int|float $nanoseconds): string
    {
        if ($nanoseconds >= 1_000_000) {
            return \number_format($nanoseconds / 1_000_000, 3) . ' ms';
        }
        if ($nanoseconds >= 1_000) {
            return \number_format($nanoseconds / 1_000, 3) . ' µs';
        }

        return \number_format($nanoseconds, 0) . ' ns';
    }

    private function replaceReadmeSection(
        string $readmePath,
        string $startMarker,
        string $endMarker,
        string $markdown
    ): void
    {
        $readme = \file_get_contents($readmePath);
        if ($readme === false) {
            throw new \RuntimeException(
                "Unable to read benchmark README: {$readmePath}"
            );
        }

        $start = \strpos($readme, $startMarker);
        $end = \strpos($readme, $endMarker);
        if ($start === false || $end === false || $end <= $start) {
            throw new \RuntimeException(
                'Benchmark result markers are missing or malformed.'
            );
        }

        $replacement = $startMarker
            . "\n"
            . $markdown
            . "\n"
            . $endMarker;
        $updated = \substr($readme, 0, $start)
            . $replacement
            . \substr($readme, $end + \strlen($endMarker));
        if (\file_put_contents($readmePath, $updated) === false) {
            throw new \RuntimeException(
                "Unable to update benchmark README: {$readmePath}"
            );
        }
    }

    /**
     * @param array<string, mixed> $results
     * @param list<array<string, mixed>> $entries
     */
    private function renderResults(
        array $results,
        array $entries,
        bool $showDirection
    ): string {
        $metadata = $results['metadata'] ?? [];
        $benchmark = $metadata['benchmark'] ?? [];
        $php = $metadata['php'] ?? [];
        $configuration = $php['configuration'] ?? [];
        $lines = [
            'Recorded: ' . ($benchmark['recorded_at'] ?? 'unknown'),
            '',
            '- Revision: `' . ($benchmark['revision'] ?? 'unknown') . '`',
            '- PHP: `' . ($php['version'] ?? 'unknown')
                . ' (' . ($php['sapi'] ?? 'unknown') . ')`',
            '- OS: `' . ($metadata['os'] ?? 'unknown') . '`',
            '- Instance: `' . ($metadata['instance'] ?? 'unknown') . '`',
            '- OPcache: `' . (($php['opcache_active'] ?? false)
                ? 'enabled'
                : 'disabled') . '`',
            '- JIT: `' . (($php['jit_active'] ?? false)
                ? 'enabled'
                : 'disabled') . '`',
            '- JIT setting: `'
                . ($configuration['opcache.jit'] ?? 'unavailable') . '`',
            '- JIT buffer: `'
                . ($configuration['opcache.jit_buffer_size']
                    ?? 'unavailable') . '`',
            '',
        ];

        if ($showDirection) {
            $lines[] = '| Direction | Case | n | Mean | p50 | p90 | p95 '
                . '| p99 | Std dev | Ops/CPU s |';
            $lines[] = '| --- | --- | ---: | ---: | ---: | ---: | ---: '
                . '| ---: | ---: | ---: |';
        } else {
            $lines[] = '| Case | n | Mean | p50 | p90 | p95 | p99 '
                . '| Std dev | Ops/CPU s |';
            $lines[] = '| --- | ---: | ---: | ---: | ---: | ---: | ---: '
                . '| ---: | ---: |';
        }

        foreach ($entries as $entry) {
            $direction = '';
            if ($showDirection) {
                $direction = '| '
                    . self::serdeDirection((string) $entry['id'])
                    . ' ';
            }
            $lines[] = $direction
                . '| `' . $entry['id'] . '`'
                . ' | ' . \number_format($entry['n'])
                . ' | ' . self::formatDuration($entry['mean'])
                . ' | ' . self::formatDuration($entry['p50'])
                . ' | ' . self::formatDuration($entry['p90'])
                . ' | ' . self::formatDuration($entry['p95'])
                . ' | ' . self::formatDuration($entry['p99'])
                . ' | ' . self::formatDuration($entry['std_dev'])
                . ' | ' . self::formatThroughput(
                    $entry['ops_per_cpu_sec']
                )
                . ' |';
        }

        return \implode("\n", $lines);
    }

    private static function serdeDirection(string $id): string
    {
        return \str_contains($id, 'Request')
            ? 'serialize'
            : 'deserialize';
    }

    private static function formatThroughput(mixed $throughput): string
    {
        return \is_int($throughput) || \is_float($throughput)
            ? \number_format($throughput, 0)
            : 'unavailable';
    }
}
