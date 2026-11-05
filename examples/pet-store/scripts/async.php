<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

use Smithy\Async\Async;
use Smithy\Examples\PetStore\Example\ClientFactory;
use Smithy\Examples\PetStore\Input\GetPetInput;

require \dirname(__DIR__) . '/vendor/autoload.php';

$client = ClientFactory::create();

$pets = Async::all(
    static fn() => $client->getPet(new GetPetInput(petId: 'pet-1')),
    static fn() => $client->getPet(new GetPetInput(petId: 'pet-1'))
);

foreach ($pets as $index => $output) {
    echo 'Request ' . ($index + 1) . ": {$output->pet?->name}\n";
}
