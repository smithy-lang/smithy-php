<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

use Smithy\Examples\PetStore\Server\PetRepository;

require \dirname(__DIR__) . '/vendor/autoload.php';

$dataFile = \getenv('PET_STORE_DATA_FILE');
if ($dataFile === false || $dataFile === '') {
    $dataFile = \dirname(__DIR__) . '/server/data/pets.json';
}

(new PetRepository($dataFile))->reset();

echo "PetStore data reset.\n";
