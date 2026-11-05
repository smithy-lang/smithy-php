<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

$root = \dirname(__DIR__, 2);
$autoload = $root . '/vendor/autoload.php';

if (!\is_file($autoload)) {
    throw new \RuntimeException(
        'Composer autoloader not found. Run composer install.'
    );
}

require_once $autoload;
require_once __DIR__ . '/support/BenchmarkCaseRepository.php';
require_once __DIR__ . '/support/BenchmarkEnvironment.php';
require_once __DIR__ . '/support/BenchmarkRunner.php';
require_once __DIR__ . '/support/BenchmarkValueComparator.php';
require_once __DIR__ . '/support/GeneratedCodeLoader.php';
require_once __DIR__ . '/support/FixedResponseHttpClient.php';
require_once __DIR__ . '/support/PipelineBenchmarkPlugin.php';
require_once __DIR__ . '/support/ResultWriter.php';
