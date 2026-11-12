#!/usr/bin/env php
<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

use Smithy\Codegen\CodeGenerator;
use Smithy\Codegen\CodegenSettings;
use Smithy\Codegen\Model\SmithyModel;
use Smithy\Protocol\Aws\AwsProtocolException;
use Smithy\Tools\CodegenOutput\CodegenOutput;

$root = \dirname(__DIR__, 2);
$autoload = $root . '/vendor/autoload.php';

if (!\is_file($autoload)) {
    \fwrite(STDERR, "Composer autoloader not found. Run composer install.\n");

    exit(1);
}

require $autoload;
require __DIR__ . '/CodegenOutput.php';

$arguments = \array_slice($argv, 1);
$check = $arguments === ['--check'];

if ($arguments !== [] && !$check) {
    \fwrite(STDERR, "Usage: php tools/codegen-output/generate.php [--check]\n");

    exit(2);
}

$buildRoot = $root . '/build/codegen-output';
$outputRoot = $root . '/codegen-output';
$cases = [
    'rpcv2-cbor' => [
        'model' => $root . '/tools/codegen-output/models/rpcv2-cbor.json',
        'settings' => static fn(string $outputDir): CodegenSettings =>
            new CodegenSettings(
                namespace: 'Smithy\\ProtocolTests\\RpcV2Cbor',
                outputDir: $outputDir,
                organization: 'categorized',
                preserveExistingMemberOrder: false
            )
    ],
    'rpcv2-json' => [
        'model' => $root . '/tools/codegen-output/models/rpcv2-json.json',
        'settings' => static fn(string $outputDir): CodegenSettings =>
            new CodegenSettings(
                namespace: 'Smithy\\ProtocolTests\\RpcV2Json',
                outputDir: $outputDir,
                organization: 'categorized',
                preserveExistingMemberOrder: false
            )
    ],
    'rest-xml' => [
        'model' => $root . '/tools/codegen-output/models/rest-xml.json',
        'settings' => static fn(string $outputDir): CodegenSettings =>
            new CodegenSettings(
                namespace: 'Smithy\\ProtocolTests\\RestXml',
                outputDir: $outputDir,
                organization: 'categorized',
                preserveExistingMemberOrder: false,
                baseServiceException: AwsProtocolException::class
            )
    ],
    'trimmed-client' => [
        'model' => $root . '/tools/codegen-output/models/trimmed-client.json',
        'settings' => static fn(string $outputDir): CodegenSettings =>
            new CodegenSettings(
                namespace: 'Smithy\\CodegenOutput\\TrimmedPetStore',
                outputDir: $outputDir,
                organization: 'categorized',
                preserveExistingMemberOrder: false,
                operationSet: ['GetPet']
            )
    ]
];

$stale = false;
foreach ($cases as $name => $case) {
    $stagingDir = $buildRoot . '/staging/' . $name
        . '/src-' . \bin2hex(\random_bytes(8));
    $outputDir = $outputRoot . '/' . $name . '/src';

    CodegenOutput::removeDirectory($stagingDir, $buildRoot);
    CodegenOutput::validatePath($outputDir, $outputRoot);

    $count = (new CodeGenerator(
        $case['settings']($stagingDir),
        SmithyModel::fromFile($case['model'])
    ))->generateAndWrite();

    CodegenOutput::lintPhpFiles($stagingDir);

    $differences = CodegenOutput::compareDirectories(
        $stagingDir,
        $outputDir
    );
    if ($differences === []) {
        CodegenOutput::removeDirectory($stagingDir, $buildRoot);
        \fwrite(
            STDOUT,
            "{$name}: {$count} generated files are current.\n"
        );
        continue;
    }

    foreach ($differences as $difference) {
        \fwrite(STDOUT, "{$name}: {$difference}\n");
    }

    if ($check) {
        CodegenOutput::removeDirectory($stagingDir, $buildRoot);
        $stale = true;
        continue;
    }

    CodegenOutput::publishDirectory(
        $stagingDir,
        $outputDir,
        $buildRoot,
        $outputRoot
    );

    \fwrite(STDOUT, "{$name}: wrote {$count} generated files.\n");
}

if ($stale) {
    \fwrite(
        STDERR,
        "Generated output is stale. Run composer codegen-output.\n"
    );

    exit(1);
}
