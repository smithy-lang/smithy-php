<?php

declare(strict_types=1);

namespace Smithy\Tests\Benchmarks\Serde;

use PHPUnit\Framework\TestCase;
use Smithy\Benchmarks\Serde\Support\ResultWriter;

require_once __DIR__ . '/../../../benchmarks/serde/support/ResultWriter.php';

final class ResultWriterTest extends TestCase
{
    private string $testRoot;

    public function testWritesJsonResults(): void
    {
        $path = (new ResultWriter())->writeJson(
            [
                'metadata' => ['lang' => 'PHP'],
                'serde_benchmarks' => [],
            ],
            $this->testRoot . '/results'
        );

        self::assertFileExists($path);
        self::assertStringContainsString(
            '"lang": "PHP"',
            (string) \file_get_contents($path)
        );
    }

    public function testReplacesOnlyTheReadmeResultSection(): void
    {
        $path = $this->testRoot . '/README.md';
        \file_put_contents(
            $path,
            <<<'MARKDOWN'
# Before

<!-- benchmark-results:start -->
old
<!-- benchmark-results:end -->

After
MARKDOWN
        );

        (new ResultWriter())->updateReadme($this->results(), $path);
        $updated = (string) \file_get_contents($path);

        self::assertStringContainsString('# Before', $updated);
        self::assertStringContainsString('After', $updated);
        self::assertStringNotContainsString("\nold\n", $updated);
        self::assertStringContainsString('### Serde', $updated);
        self::assertStringContainsString(
            '| serialize | `exampleRequest` | 10 | 1.500 µs | 1.400 µs | 1.700 µs | 1.900 µs | 2.000 µs | 100 ns | 625 |',
            $updated
        );
    }

    public function testRendersSharedSerdeResultSchema(): void
    {
        $entry = ResultWriter::benchmarkEntry('example', [
            'iterations' => 10,
            'mean_ns' => 1_500,
            'p50_ns' => 1_400,
            'p90_ns' => 1_700,
            'p95_ns' => 1_900,
            'p99_ns' => 2_000,
            'stddev_ns' => 100,
            'ops_per_cpu_sec' => 625.0,
        ]);

        self::assertSame([
            'id' => 'example',
            'n' => 10,
            'mean' => 1_500,
            'p50' => 1_400,
            'p90' => 1_700,
            'p95' => 1_900,
            'p99' => 2_000,
            'std_dev' => 100,
            'ops_per_cpu_sec' => 625.0,
        ], $entry);
    }

    protected function setUp(): void
    {
        $this->testRoot = \sys_get_temp_dir()
            . '/smithy-php-benchmark-results-'
            . \bin2hex(\random_bytes(8));
        \mkdir($this->testRoot);
    }

    protected function tearDown(): void
    {
        if (!\is_dir($this->testRoot)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $this->testRoot,
                \FilesystemIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $entry) {
            $entry->isDir()
                ? \rmdir($entry->getPathname())
                : \unlink($entry->getPathname());
        }
        \rmdir($this->testRoot);
    }

    /**
     * @return array<string, mixed>
     */
    private function results(): array
    {
        return [
            'metadata' => [
                'lang' => 'PHP',
                'os' => 'Test OS',
                'instance' => 'Test CPU',
                'precision' => '-9',
                'php' => [
                    'version' => '8.2.0',
                    'sapi' => 'cli',
                    'opcache_active' => false,
                    'jit_active' => false,
                    'configuration' => [
                        'opcache.jit' => 'disable',
                        'opcache.jit_buffer_size' => '0',
                    ],
                ],
                'benchmark' => [
                    'recorded_at' => '2026-10-05T12:00:00-04:00',
                    'revision' => 'abcdef0',
                ],
            ],
            'serde_benchmarks' => [[
                'id' => 'exampleRequest',
                'n' => 10,
                'mean' => 1_500,
                'p50' => 1_400,
                'p90' => 1_700,
                'p95' => 1_900,
                'p99' => 2_000,
                'std_dev' => 100,
                'ops_per_cpu_sec' => 625.0,
            ]],
        ];
    }
}
