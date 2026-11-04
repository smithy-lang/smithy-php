#!/usr/bin/env php
<?php

declare(strict_types=1);

use Smithy\Codegen\CodeGenerator;
use Smithy\Codegen\CodegenSettings;
use Smithy\Codegen\Model\SmithyModel;
use Smithy\Tools\CodegenOutput\CodegenOutput;

$root = \dirname(__DIR__, 2);
$autoload = $root . '/vendor/autoload.php';

if (!\is_file($autoload)) {
    \fwrite(STDERR, "Composer autoloader not found. Run composer install.\n");

    exit(1);
}

require $autoload;
require $root . '/tools/codegen-output/CodegenOutput.php';

$arguments = \array_slice($argv, 1);
$clean = $arguments === ['--clean'];

if ($arguments !== [] && !$clean) {
    \fwrite(
        STDERR,
        "Usage: php benchmarks/serde/generate.php [--clean]\n"
    );

    exit(2);
}

$buildRoot = $root . '/build/benchmarks';
if ($clean) {
    CodegenOutput::removeDirectory(
        $buildRoot . '/serde',
        $buildRoot
    );
    CodegenOutput::removeDirectory(
        $buildRoot . '/results',
        $buildRoot
    );
    CodegenOutput::removeDirectory(
        $buildRoot . '/staging',
        $buildRoot
    );
    \fwrite(STDOUT, "Removed generated benchmark files and results.\n");

    exit(0);
}

$cases = [
    'rpcv2-cbor' => [
        'model' => __DIR__ . '/model/rpcv2-cbor.json',
        'namespace' => 'Smithy\\Benchmarks\\RpcV2Cbor'
    ],
    'rpcv2-json' => [
        'model' => __DIR__ . '/model/rpcv2-json.json',
        'namespace' => 'Smithy\\Benchmarks\\RpcV2Json'
    ]
];

foreach ($cases as $name => $case) {
    $modelPath = $case['model'];
    if (!\is_file($modelPath)) {
        \fwrite(
            STDERR,
            "Benchmark model not found: {$modelPath}\n"
        );

        exit(1);
    }

    $outputDirectory = $buildRoot . "/serde/{$name}/src";
    $stagingDirectory = $buildRoot . "/staging/serde-{$name}-"
        . \bin2hex(\random_bytes(8))
        . '/src';
    $settings = new CodegenSettings(
        namespace: $case['namespace'],
        outputDir: $stagingDirectory,
        organization: 'categorized',
        preserveExistingMemberOrder: false,
        generateClient: true,
        generateClientConfig: false
    );

    try {
        $count = (new CodeGenerator(
            $settings,
            SmithyModel::fromFile($modelPath)
        ))->generateAndWrite();
        CodegenOutput::lintPhpFiles($stagingDirectory);
        CodegenOutput::publishDirectory(
            $stagingDirectory,
            $outputDirectory,
            $buildRoot,
            $buildRoot
        );
    } catch (\Throwable $error) {
        if (\is_dir($stagingDirectory)) {
            CodegenOutput::removeDirectory(
                $stagingDirectory,
                $buildRoot
            );
        }
        \fwrite(STDERR, $error->getMessage() . "\n");

        exit(1);
    }

    \fwrite(
        STDOUT,
        "{$name}: wrote {$count} generated files to "
            . "build/benchmarks/serde/{$name}/src.\n"
    );
}
