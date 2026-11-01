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
$outputDirectory = $buildRoot . '/serde/rpcv2-cbor/src';
$stagingDirectory = $buildRoot . '/staging/serde-rpcv2-cbor-'
    . \bin2hex(\random_bytes(8))
    . '/src';

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

$modelPath = __DIR__ . '/model/rpcv2-cbor.json';
if (!\is_file($modelPath)) {
    \fwrite(
        STDERR,
        "Benchmark model not found: {$modelPath}\n"
    );

    exit(1);
}

$settings = new CodegenSettings(
    namespace: 'Smithy\\Benchmarks\\RpcV2Cbor',
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
        CodegenOutput::removeDirectory($stagingDirectory, $buildRoot);
    }
    \fwrite(STDERR, $error->getMessage() . "\n");

    exit(1);
}

\fwrite(
    STDOUT,
    "rpcv2-cbor: wrote {$count} generated files to "
        . "build/benchmarks/serde/rpcv2-cbor/src.\n"
);
