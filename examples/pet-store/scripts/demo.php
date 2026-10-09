<?php

declare(strict_types=1);

use Smithy\Core\Shape\Utils\ArrayShapeSerializer;
use Smithy\Examples\PetStore\Example\ClientFactory;
use Smithy\Examples\PetStore\Exception\PetNotFoundErrorException;
use Smithy\Examples\PetStore\Input\{
    CreatePetInput,
    DeletePetInput,
    GetPetInput,
    ListPetsInput,
    UpdatePetInput
};

require \dirname(__DIR__) . '/vendor/autoload.php';

$client = ClientFactory::create();

/** @var CreatePetInput $createInput */
$createInput = ArrayShapeSerializer::fromArray(CreatePetInput::class, [
    'pet' => [
        'name' => 'Milo',
        'species' => 'dog',
        'ageYears' => 2,
        'description' => 'An energetic dog who loves fetch.',
        'photo' => 'demo-photo-bytes',
        'traits' => ['playful', 'good-with-children'],
        'attributes' => [
            'color' => 'brown',
            'coat' => 'medium'
        ],
        'vaccinations' => [
            [
                'name' => 'rabies',
                'administeredAt' => \time()
            ],
            null
        ],
        'details' => [
            'dog' => [
                'breed' => 'Mixed Breed',
                'favoriteToy' => 'tennis ball'
            ]
        ],
        'metrics' => [
            'houseTrained' => true,
            'temperamentScore' => 8,
            'kennelNumber' => 7,
            'eventSequence' => 4_294_967_297,
            'weightKilograms' => 18.4,
            'latitude' => 35.7796,
            'registrySequence' => '9007199254740995',
            'adoptionFee' => '175.00',
            'medicalPriority' => 1,
            'measurements' => [
                'heightCentimeters' => 54.2,
                'optionalMeasurement' => null
            ]
        ]
    ]
]);

$created = $client->createPet($createInput);
$petId = $created->pet?->petId;
echo "Created {$petId}\n";

$fetched = $client->getPet(new GetPetInput(petId: $petId));
echo "Fetched {$fetched->pet?->name}\n";

/** @var UpdatePetInput $updateInput */
$updateInput = ArrayShapeSerializer::fromArray(UpdatePetInput::class, [
    'petId' => $petId,
    'pet' => [
        'status' => 'pending',
        'description' => 'Milo has a pending adoption.'
    ]
]);
$updated = $client->updatePet($updateInput);
echo "Updated status to {$updated->pet?->status?->value}\n";

$listed = $client->listPets(new ListPetsInput(pageSize: 10));
echo 'Listed ' . \count($listed->pets ?? []) . " pet(s)\n";

$asArray = ArrayShapeSerializer::toArray($updated);
echo "Array access name: {$updated['pet']->name}\n";
echo "Array conversion status: {$asArray['pet']['status']}\n";

$client->deletePet(new DeletePetInput(petId: $petId));
echo "Deleted {$petId}\n";

try {
    $client->getPet(new GetPetInput(petId: $petId));
} catch (PetNotFoundErrorException $error) {
    echo "Confirmed deletion: {$error->getMessage()}\n";
}
