#!/usr/bin/env php
<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

$root = \dirname(__DIR__);
$check = ($argv[1] ?? null) === '--check';
if (\count($argv) > ($check ? 2 : 1)) {
    \fwrite(STDERR, "Usage: php tools/build-model.php [--check]\n");

    exit(2);
}

$smithy = \getenv('SMITHY_CLI');
if ($smithy === false || $smithy === '') {
    $smithy = 'smithy';
}

$process = \proc_open(
    [
        $smithy,
        'build',
        '--config',
        'smithy-build.json',
        '--output',
        'build/smithy',
        '--projection',
        'pet-store'
    ],
    [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['redirect', 1]
    ],
    $pipes,
    $root
);
if (!\is_resource($process)) {
    \fwrite(STDERR, "Unable to start the Smithy CLI.\n");

    exit(1);
}

\fclose($pipes[0]);
$output = \stream_get_contents($pipes[1]);
\fclose($pipes[1]);
$status = \proc_close($process);

if ($output !== false && $output !== '') {
    \fwrite($status === 0 ? STDOUT : STDERR, $output);
}
if ($status !== 0) {
    exit($status);
}

$generatedPath = $root . '/build/smithy/pet-store/model/model.json';
$committedPath = $root . '/model/pet-store.json';
$generated = \file_get_contents($generatedPath);
if ($generated === false) {
    \fwrite(STDERR, "Smithy did not produce {$generatedPath}.\n");

    exit(1);
}

$committed = \is_file($committedPath)
    ? \file_get_contents($committedPath)
    : false;
if ($committed === $generated) {
    \fwrite(STDOUT, "The committed PetStore model is current.\n");

    exit(0);
}
if ($check) {
    \fwrite(
        STDERR,
        "The committed PetStore model is stale. Run composer model.\n"
    );

    exit(1);
}
$written = \file_put_contents($committedPath, $generated);
if ($written === false || $written !== \strlen($generated)) {
    \fwrite(STDERR, "Unable to write {$committedPath}.\n");

    exit(1);
}

\fwrite(STDOUT, "Updated model/pet-store.json.\n");
