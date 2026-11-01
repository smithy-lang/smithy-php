<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

$files = [];
foreach (\array_slice($argv, 1) as $path) {
    if (\is_file($path)) {
        $files[] = $path;
        continue;
    }

    if (!\is_dir($path)) {
        \fwrite(STDERR, "Path does not exist: {$path}\n");
        exit(1);
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $path,
            FilesystemIterator::SKIP_DOTS
        )
    );
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
}

\sort($files);
$failed = false;
foreach ($files as $file) {
    $output = [];
    $status = 0;
    \exec(
        \escapeshellarg(PHP_BINARY)
            . ' -l '
            . \escapeshellarg($file)
            . ' 2>&1',
        $output,
        $status
    );
    if ($status !== 0) {
        \fwrite(STDERR, \implode("\n", $output) . "\n");
        $failed = true;
    }
}

if ($failed) {
    exit(1);
}

\printf("Checked %d PHP files.\n", \count($files));
