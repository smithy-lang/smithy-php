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

$count = \count($packages);
$status = 0;

$differences = PackageManifests::compare($read('composer.json'), $packages);
if ($differences === []) {
    \fwrite(
        STDOUT,
        "{$count} package composer.json files match the root composer.json.\n"
    );
} else {
    foreach ($differences as $difference) {
        \fwrite(STDOUT, "{$difference}\n");
    }

    \fwrite(
        STDERR,
        "The package composer.json files do not match the root composer.json.\n"
    );
    $status = 1;
}

$contents = static function (string $file) use ($root): ?string {
    $path = $root . '/' . $file;

    return \is_file($path) ? (string) \file_get_contents($path) : null;
};

$legal = [];
foreach (['LICENSE', 'NOTICE'] as $name) {
    $text = $contents($name);
    if ($text === null) {
        $fail("{$name}: is missing");
    }

    $legal[$name] = $text;
}

$copies = [];
foreach (\array_keys($packages) as $file) {
    $directory = \dirname($file);
    foreach (\array_keys($legal) as $name) {
        $copies[$directory][$name] = $contents("{$directory}/{$name}");
    }
}

$differences = PackageManifests::compareLegalFiles($legal, $copies);
if ($differences === []) {
    \fwrite(
        STDOUT,
        "{$count} package directories carry the root LICENSE and NOTICE.\n"
    );
} else {
    foreach ($differences as $difference) {
        \fwrite(STDOUT, "{$difference}\n");
    }

    \fwrite(
        STDERR,
        "The package LICENSE and NOTICE files do not match the root files.\n"
    );
    $status = 1;
}

exit($status);
