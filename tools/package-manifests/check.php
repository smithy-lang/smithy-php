#!/usr/bin/env php
<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

use Smithy\Tools\PackageManifests\PackageManifests;

require __DIR__ . '/PackageManifests.php';

if (\array_slice($argv, 1) !== []) {
    \fwrite(STDERR, "Usage: php tools/package-manifests/check.php\n");

    exit(2);
}

$root = \dirname(__DIR__, 2);
$fail = static function (string $message): never {
    \fwrite(STDERR, $message . "\n");

    exit(2);
};
$read = static function (string $file) use ($root, $fail): array {
    try {
        $manifest = \json_decode(
            (string) \file_get_contents($root . '/' . $file),
            true,
            512,
            \JSON_THROW_ON_ERROR
        );
    } catch (\JsonException $exception) {
        $fail("{$file}: {$exception->getMessage()}");
    }

    if (!\is_array($manifest)) {
        $fail("{$file}: is not a JSON object");
    }

    foreach (['require', 'replace', 'suggest'] as $section) {
        $entries = $manifest[$section] ?? [];
        if (!\is_array($entries) || ($entries !== [] && \array_is_list($entries))) {
            $fail("{$file}: {$section} is not a JSON object");
        }

        foreach ($entries as $key => $value) {
            if (!\is_string($value)) {
                $fail("{$file}: {$section} {$key} is not a string");
            }
        }
    }

    return $manifest;
};

$paths = \glob($root . '/src/*/composer.json') ?: [];
\sort($paths);

$packages = [];
foreach ($paths as $path) {
    $file = \substr($path, \strlen($root) + 1);
    $packages[$file] = $read($file);
}

$differences = PackageManifests::compare($read('composer.json'), $packages);
if ($differences === []) {
    $count = \count($packages);
    \fwrite(
        STDOUT,
        "{$count} package composer.json files match the root composer.json.\n"
    );

    exit(0);
}

foreach ($differences as $difference) {
    \fwrite(STDOUT, "{$difference}\n");
}

\fwrite(
    STDERR,
    "The package composer.json files do not match the root composer.json.\n"
);

exit(1);
