<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Benchmarks\Serde\Support;

/**
 * Captures the runtime details needed to reproduce benchmark results.
 */
final class BenchmarkEnvironment
{
    /**
     * @return array<string, mixed>
     */
    public static function metadata(
        string $root,
        BenchmarkRunner $runner,
        string $suite
    ): array
    {
        $revision = self::revision($root);
        $opcache = \function_exists('opcache_get_status')
            ? \opcache_get_status(false)
            : false;
        $jitEnabled = \is_array($opcache)
            && !empty($opcache['jit']['enabled'])
            && !empty($opcache['jit']['on']);

        return [
            'lang' => 'PHP',
            'software' => [['smithy-php', $revision]],
            'os' => \php_uname('s')
                . ' '
                . \php_uname('r')
                . ' '
                . \php_uname('m'),
            'instance' => self::instanceDescription(),
            'precision' => '-9',
            'php' => [
                'version' => PHP_VERSION,
                'sapi' => PHP_SAPI,
                'architecture_bits' => PHP_INT_SIZE * 8,
                'platform' => \php_uname(),
                'configuration' => [
                    'memory_limit' => self::iniValue('memory_limit'),
                    'opcache.enable' => self::iniValue('opcache.enable'),
                    'opcache.enable_cli' =>
                        self::iniValue('opcache.enable_cli'),
                    'opcache.jit' => self::iniValue('opcache.jit'),
                    'opcache.jit_buffer_size' =>
                        self::iniValue('opcache.jit_buffer_size'),
                    'zend.assertions' => self::iniValue('zend.assertions'),
                ],
                'opcache_active' => \is_array($opcache)
                    && !empty($opcache['opcache_enabled']),
                'jit_active' => $jitEnabled,
            ],
            'benchmark' => [
                'suite' => $suite,
                'recorded_at' => \date(DATE_ATOM),
                'revision' => $revision,
                'runtime_codegen' => false,
                'runner' => [
                    'class' => $runner::class,
                    ...$runner->settings(),
                ],
            ],
        ];
    }

    private static function revision(string $root): string
    {
        $output = [];
        $status = 0;
        \exec(
            'git -C ' . \escapeshellarg($root)
                . ' rev-parse --short HEAD 2>/dev/null',
            $output,
            $status
        );
        if ($status !== 0 || $output === []) {
            return 'unknown';
        }

        return \trim($output[0]);
    }

    private static function instanceDescription(): string
    {
        foreach ([
            'SMITHY_BENCHMARK_INSTANCE',
            'EC2_INSTANCE_TYPE',
            'PROCESSOR_IDENTIFIER',
        ] as $variable) {
            $value = \getenv($variable);
            if (\is_string($value) && $value !== '') {
                return $value;
            }
        }

        if (PHP_OS_FAMILY === 'Darwin') {
            $output = [];
            $status = 0;
            \exec(
                'sysctl -n machdep.cpu.brand_string 2>/dev/null',
                $output,
                $status
            );
            if ($status === 0 && $output !== []) {
                return \trim($output[0]);
            }
        }

        if (PHP_OS_FAMILY === 'Linux' && \is_file('/proc/cpuinfo')) {
            $cpuInfo = \file_get_contents('/proc/cpuinfo');
            if (\is_string($cpuInfo)
                && \preg_match(
                    '/^model name\s*:\s*(.+)$/m',
                    $cpuInfo,
                    $match
                )
            ) {
                return \trim($match[1]);
            }
        }

        return \php_uname('m');
    }

    private static function iniValue(string $name): string
    {
        $value = \ini_get($name);

        return $value === false ? 'unavailable' : $value;
    }
}
