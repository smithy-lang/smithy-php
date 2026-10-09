#!/usr/bin/env php
<?php

declare(strict_types=1);

use Smithy\Async\Async;
use Smithy\Codec\Cbor\CborCodec;
use Smithy\Codec\Cbor\CborCodecSettings;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\TimestampFormat;
use Smithy\Core\Shape\Utils\ArrayShapeSerializer;
use Smithy\Examples\PetStore\Example\ClientFactory;
use Smithy\Examples\PetStore\Exception\PetConflictErrorException;
use Smithy\Examples\PetStore\Exception\PetNotFoundErrorException;
use Smithy\Examples\PetStore\Exception\ValidationErrorException;
use Smithy\Examples\PetStore\Input\{
    CreatePetInput,
    DeletePetInput,
    GetPetInput,
    ListPetsInput,
    UpdatePetInput
};
use Smithy\Examples\PetStore\Server\{
    PetRepository,
    PetStoreApplication
};

$root = \dirname(__DIR__);
require $root . '/vendor/autoload.php';

$dataFile = $root . '/build/smoke-test/pets.json';
$protocolDataFile = $root . '/build/smoke-test/protocol-pets.json';
foreach ([$dataFile, $protocolDataFile] as $smokeDataFile) {
    if (\is_file($smokeDataFile) && !\unlink($smokeDataFile)) {
        throw new \RuntimeException(
            "Unable to reset smoke-test data {$smokeDataFile}."
        );
    }
}
[$process, $pipes, $port] = startServer($root, $dataFile);
$endpoint = "http://127.0.0.1:{$port}";

try {
    $client = ClientFactory::create($endpoint);
    $prefixedClient = ClientFactory::create($endpoint . '/v1');

    assertProtocolBehavior($protocolDataFile);
    $prefixedList = $prefixedClient->listPets(
        new ListPetsInput(pageSize: 1)
    );
    assertTrue(
        \count($prefixedList->pets ?? []) === 1,
        'A client endpoint path prefix produced an invalid RPCv2 route.'
    );

    /** @var CreatePetInput $createInput */
    $createInput = ArrayShapeSerializer::fromArray(
        CreatePetInput::class,
        [
            'pet' => [
                'name' => 'Smoke Test Pet',
                'species' => 'dog',
                'details' => [
                    'dog' => [
                        'breed' => 'Mixed Breed',
                        'favoriteToy' => 'rope'
                    ]
                ],
                'metrics' => [
                    'weightKilograms' => \NAN,
                    'latitude' => \INF,
                    'registrySequence' => '9007199254740997',
                    'adoptionFee' => '99.95',
                    'measurements' => [
                        'positiveInfinity' => \INF,
                        'negativeInfinity' => -\INF,
                        'notANumber' => \NAN
                    ]
                ]
            ]
        ]
    );
    $created = $client->createPet($createInput);
    $petId = $created->pet?->petId;
    assertTrue($petId !== null, 'CreatePet did not return a pet ID.');

    $fetched = $client->getPet(new GetPetInput(petId: $petId));
    assertTrue(
        $fetched->pet?->name === 'Smoke Test Pet',
        'GetPet returned the wrong pet.'
    );
    assertNonFiniteMetrics(
        $fetched->pet?->metrics,
        'GetPet did not round-trip non-finite metric values.'
    );

    /** @var CreatePetInput $unicodeNameInput */
    $unicodeNameInput = ArrayShapeSerializer::fromArray(
        CreatePetInput::class,
        [
            'pet' => [
                'name' => 'Éclair',
                'species' => 'cat'
            ]
        ]
    );
    $unicodeNamePet = $client->createPet($unicodeNameInput);
    $unicodeNamePetId = $unicodeNamePet->pet?->petId;
    assertTrue(
        $unicodeNamePetId !== null,
        'CreatePet did not return a Unicode-named pet ID.'
    );

    /** @var CreatePetInput $unicodeDuplicateInput */
    $unicodeDuplicateInput = ArrayShapeSerializer::fromArray(
        CreatePetInput::class,
        [
            'pet' => [
                'name' => 'éCLAIR',
                'species' => 'cat'
            ]
        ]
    );
    assertPetConflict(
        static fn() => $client->createPet($unicodeDuplicateInput),
        'CreatePet allowed a Unicode case-insensitive duplicate name.'
    );

    /** @var UpdatePetInput $unicodeRenameInput */
    $unicodeRenameInput = ArrayShapeSerializer::fromArray(
        UpdatePetInput::class,
        [
            'petId' => $petId,
            'pet' => [
                'name' => 'éCLAIR'
            ]
        ]
    );
    assertValidationError(
        static fn() => $client->updatePet($unicodeRenameInput),
        'pet.name',
        'UpdatePet allowed a Unicode case-insensitive duplicate name.'
    );
    assertTrue(
        $client->getPet(new GetPetInput(petId: $petId))->pet?->name
            === 'Smoke Test Pet',
        'A rejected Unicode duplicate rename mutated the stored pet.'
    );
    $client->deletePet(new DeletePetInput(petId: $unicodeNamePetId));

    /** @var CreatePetInput $forwardCompatibleInput */
    $forwardCompatibleInput = ArrayShapeSerializer::fromArray(
        CreatePetInput::class,
        [
            'pet' => [
                'name' => 'Forward Compatible Pet',
                'species' => 'future-species',
                'metrics' => [
                    'medicalPriority' => 99
                ]
            ]
        ]
    );
    $forwardCompatible = $client->createPet($forwardCompatibleInput);
    assertTrue(
        $forwardCompatible->pet?->species?->getValue()
                === 'future-species'
            && $forwardCompatible->pet?->metrics?->medicalPriority
                ?->getValue() === 99,
        'CreatePet rejected or changed unknown enum values.'
    );
    $client->deletePet(new DeletePetInput(
        petId: (string) $forwardCompatible->pet?->petId
    ));

    assertValidationError(
        static fn() => $client->getPet(
            new GetPetInput(petId: 'invalid')
        ),
        'petId',
        'GetPet accepted an invalid pet ID.'
    );

    /** @var CreatePetInput $invalidCreateInput */
    $invalidCreateInput = ArrayShapeSerializer::fromArray(
        CreatePetInput::class,
        [
            'pet' => [
                'name' => \str_repeat('x', 65),
                'species' => 'dog'
            ]
        ]
    );
    assertValidationError(
        static fn() => $client->createPet($invalidCreateInput),
        'pet.name',
        'CreatePet accepted a name longer than 64 characters.'
    );

    /** @var CreatePetInput $missingBreedInput */
    $missingBreedInput = ArrayShapeSerializer::fromArray(
        CreatePetInput::class,
        [
            'pet' => [
                'name' => 'Missing Breed',
                'species' => 'dog',
                'details' => [
                    'dog' => []
                ]
            ]
        ]
    );
    assertValidationError(
        static fn() => $client->createPet($missingBreedInput),
        'pet.details.dog.breed',
        'CreatePet accepted dog details without a breed.'
    );

    /** @var CreatePetInput $missingVaccinationNameInput */
    $missingVaccinationNameInput = ArrayShapeSerializer::fromArray(
        CreatePetInput::class,
        [
            'pet' => [
                'name' => 'Missing Vaccination Name',
                'species' => 'dog',
                'vaccinations' => [
                    ['administeredAt' => 1_760_000_000.0]
                ]
            ]
        ]
    );
    assertValidationError(
        static fn() => $client->createPet(
            $missingVaccinationNameInput
        ),
        'pet.vaccinations[0].name',
        'CreatePet accepted a vaccination without a name.'
    );

    /** @var CreatePetInput $invalidByteInput */
    $invalidByteInput = ArrayShapeSerializer::fromArray(
        CreatePetInput::class,
        [
            'pet' => [
                'name' => 'Invalid Byte',
                'species' => 'dog',
                'metrics' => [
                    'temperamentScore' => 128
                ]
            ]
        ]
    );
    assertValidationError(
        static fn() => $client->createPet($invalidByteInput),
        'pet.metrics.temperamentScore',
        'CreatePet accepted a Byte above 127.'
    );

    /** @var CreatePetInput $invalidShortInput */
    $invalidShortInput = ArrayShapeSerializer::fromArray(
        CreatePetInput::class,
        [
            'pet' => [
                'name' => 'Invalid Short',
                'species' => 'dog',
                'metrics' => [
                    'kennelNumber' => 32_768
                ]
            ]
        ]
    );
    assertValidationError(
        static fn() => $client->createPet($invalidShortInput),
        'pet.metrics.kennelNumber',
        'CreatePet accepted a Short above 32767.'
    );

    /** @var CreatePetInput $invalidIntegerInput */
    $invalidIntegerInput = ArrayShapeSerializer::fromArray(
        CreatePetInput::class,
        [
            'pet' => [
                'name' => 'Invalid Integer',
                'species' => 'dog',
                'ageYears' => 2_147_483_648
            ]
        ]
    );
    assertValidationError(
        static fn() => $client->createPet($invalidIntegerInput),
        'pet.ageYears',
        'CreatePet accepted an Integer above 2147483647.'
    );

    /** @var UpdatePetInput $updateInput */
    $updateInput = ArrayShapeSerializer::fromArray(
        UpdatePetInput::class,
        [
            'petId' => $petId,
            'pet' => [
                'status' => 'pending'
            ]
        ]
    );
    $updated = $client->updatePet($updateInput);
    assertTrue(
        $updated->pet?->status?->value === 'pending',
        'UpdatePet did not persist the status.'
    );
    assertNonFiniteMetrics(
        $updated->pet?->metrics,
        'UpdatePet did not preserve non-finite metric values.'
    );
    $updatedAt = $updated->pet?->updatedAt;
    assertTrue(
        $updatedAt !== null,
        'UpdatePet did not return an updated timestamp.'
    );
    \usleep(1_000);
    $retriedUpdate = $client->updatePet($updateInput);
    assertTrue(
        $retriedUpdate->pet?->updatedAt === $updatedAt,
        'Retrying an identical UpdatePet changed updatedAt.'
    );
    assertNonFiniteMetrics(
        $retriedUpdate->pet?->metrics,
        'An idempotent UpdatePet retry changed non-finite metric values.'
    );

    /** @var UpdatePetInput $duplicateNameInput */
    $duplicateNameInput = ArrayShapeSerializer::fromArray(
        UpdatePetInput::class,
        [
            'petId' => $petId,
            'pet' => [
                'name' => 'sCuLlY'
            ]
        ]
    );
    assertValidationError(
        static fn() => $client->updatePet($duplicateNameInput),
        'pet.name',
        'UpdatePet allowed a case-insensitive duplicate pet name.'
    );
    $afterDuplicateRename = $client->getPet(
        new GetPetInput(petId: $petId)
    );
    assertTrue(
        $afterDuplicateRename->pet?->name === 'Smoke Test Pet',
        'A rejected duplicate rename mutated the stored pet.'
    );

    /** @var UpdatePetInput $invalidUpdateInput */
    $invalidUpdateInput = ArrayShapeSerializer::fromArray(
        UpdatePetInput::class,
        [
            'petId' => $petId,
            'pet' => [
                'name' => ''
            ]
        ]
    );
    assertValidationError(
        static fn() => $client->updatePet($invalidUpdateInput),
        'pet.name',
        'UpdatePet accepted an empty pet name.'
    );

    /** @var UpdatePetInput $invalidNestedUpdateInput */
    $invalidNestedUpdateInput = ArrayShapeSerializer::fromArray(
        UpdatePetInput::class,
        [
            'petId' => $petId,
            'pet' => [
                'details' => [
                    'dog' => []
                ]
            ]
        ]
    );
    assertValidationError(
        static fn() => $client->updatePet($invalidNestedUpdateInput),
        'pet.details.dog.breed',
        'UpdatePet accepted dog details without a breed.'
    );

    $listed = $client->listPets(new ListPetsInput(pageSize: 1));
    assertTrue(
        \count($listed->pets ?? []) === 1,
        'ListPets did not honor the page size.'
    );
    assertTrue(
        $listed->nextToken !== null,
        'ListPets did not return a pagination token.'
    );
    $nextPage = $client->listPets(new ListPetsInput(
        nextToken: $listed->nextToken,
        pageSize: 1
    ));
    assertTrue(
        \count($nextPage->pets ?? []) === 1
            && $nextPage->pets[0]->petId === $petId
            && $nextPage->nextToken === null,
        'ListPets did not consume the pagination token correctly.'
    );
    assertValidationError(
        static fn() => $client->listPets(
            new ListPetsInput(pageSize: 0)
        ),
        'pageSize',
        'ListPets accepted an invalid page size.'
    );
    assertValidationError(
        static fn() => $client->deletePet(
            new DeletePetInput(petId: 'invalid')
        ),
        'petId',
        'DeletePet accepted an invalid pet ID.'
    );

    $concurrent = Async::all(
        static fn() => $client->getPet(new GetPetInput(petId: 'pet-1')),
        static fn() => $client->getPet(new GetPetInput(petId: $petId))
    );
    assertTrue(
        \count($concurrent) === 2,
        'Concurrent requests did not both complete.'
    );

    $deleteResponse = sendHttpRequest(
        $port,
        '/service/PetStore/operation/DeletePet',
        encodeCbor(new DeletePetInput(petId: $petId))
    );
    assertTrue(
        $deleteResponse['statusCode'] === 200
            && $deleteResponse['body'] === ''
            && !\array_key_exists(
                'content-type',
                $deleteResponse['headers']
            )
            && ($deleteResponse['headers']['smithy-protocol'] ?? null)
                === 'rpc-v2-cbor',
        'The DeletePet Unit response included a Content-Type header.'
    );
    try {
        $client->getPet(new GetPetInput(petId: $petId));
        throw new \RuntimeException(
            'GetPet succeeded after DeletePet.'
        );
    } catch (PetNotFoundErrorException $error) {
        assertTrue(
            $error->petId === $petId,
            'The modeled not-found error omitted the pet ID.'
        );
    }

    \fwrite(STDOUT, "PetStore smoke test passed.\n");
} finally {
    stopServer($process, $pipes);
}

function assertNonFiniteMetrics(
    ?\Smithy\Examples\PetStore\Shape\PetMetrics $metrics,
    string $message
): void {
    $measurements = $metrics?->measurements;
    $notANumber = $measurements['notANumber'] ?? null;
    assertTrue(
        $metrics !== null
            && \is_nan($metrics->weightKilograms)
            && $metrics->latitude === \INF
            && ($measurements['positiveInfinity'] ?? null) === \INF
            && ($measurements['negativeInfinity'] ?? null) === -\INF
            && \is_float($notANumber)
            && \is_nan($notANumber),
        $message
    );
}

function assertProtocolBehavior(string $dataFile): void
{
    $repository = new PetRepository($dataFile);
    $application = new PetStoreApplication($repository);
    $headers = [
        'smithy-protocol' => 'rpc-v2-cbor',
        'content-type' => 'application/cbor'
    ];
    $listPath = '/service/PetStore/operation/ListPets';
    $listBody = encodeCbor(new ListPetsInput());

    $missingRequired = $application->handle(
        'POST',
        '/service/PetStore/operation/CreatePet',
        $headers,
        encodeUntypedCbor([])
    );
    assertTrue(
        $missingRequired->statusCode === 400
            && \str_contains(
                $missingRequired->body,
                'smithy.example.petstore#ValidationError'
            ),
        'A missing required input member did not return ValidationError.'
    );

    $petCount = \count($repository->list(null, 25)['pets']);
    $unsupportedUnion = $application->handle(
        'POST',
        '/service/PetStore/operation/CreatePet',
        $headers,
        encodeUntypedCbor([
            'pet' => [
                'name' => 'Future Variant Pet',
                'species' => 'dog',
                'details' => [
                    'futureVariant' => 'opaque'
                ]
            ]
        ])
    );
    assertTrue(
        $unsupportedUnion->statusCode === 400
            && \str_contains(
                $unsupportedUnion->body,
                'smithy.example.petstore#ValidationError'
            )
            && \count($repository->list(null, 25)['pets']) === $petCount,
        'An unsupported union variant mutated the repository.'
    );

    foreach (['x-amz-target', 'x-amzn-target'] as $targetHeader) {
        $requestHeaders = $headers;
        $requestHeaders[$targetHeader] = 'Legacy.Target';
        $response = $application->handle(
            'POST',
            $listPath,
            $requestHeaders,
            $listBody
        );
        assertTrue(
            $response->statusCode === 400,
            "The {$targetHeader} header was not rejected."
        );
    }

    $wrongMethod = $application->handle(
        'GET',
        $listPath,
        $headers,
        $listBody
    );
    assertTrue(
        $wrongMethod->statusCode === 400,
        'An unannotated ValidationError did not use HTTP 400.'
    );

    $notFound = $application->handle(
        'POST',
        '/service/PetStore/operation/GetPet',
        $headers,
        encodeCbor(new GetPetInput(petId: 'pet-999999'))
    );
    assertTrue(
        $notFound->statusCode === 400,
        'An unannotated PetNotFoundError did not use HTTP 400.'
    );

    $temporaryPet = $repository->create([
        'name' => 'Protocol Test Pet',
        'species' => 'dog'
    ]);
    $deleteResponse = $application->handle(
        'POST',
        '/service/PetStore/operation/DeletePet',
        $headers,
        encodeCbor(new DeletePetInput(
            petId: (string) $temporaryPet['petId']
        ))
    );
    assertTrue(
        $deleteResponse->statusCode === 200
            && $deleteResponse->body === ''
            && !\array_key_exists(
                'Content-Type',
                $deleteResponse->headers
            )
            && ($deleteResponse->headers['Smithy-Protocol'] ?? null)
                === 'rpc-v2-cbor',
        'A Unit response included serialization content or lost its protocol.'
    );
}

function encodeCbor(SerializableStructInterface $value): string
{
    $codec = new CborCodec(new CborCodecSettings(
        defaultTimestampFormat: TimestampFormat::EpochSeconds,
        honorTimestampFormatTrait: false
    ));
    $serializer = $codec->acquireSerializer();
    try {
        $value->serialize($serializer, 0);

        return $serializer->flush();
    } finally {
        $codec->releaseSerializer($serializer);
    }
}

function encodeUntypedCbor(mixed $value): string
{
    if ($value === null) {
        return "\xF6";
    }
    if (\is_string($value)) {
        return encodeCborHeader(3, \strlen($value)) . $value;
    }
    if (\is_array($value)) {
        $encoded = encodeCborHeader(5, \count($value));
        foreach ($value as $key => $item) {
            if (!\is_string($key)) {
                throw new \InvalidArgumentException(
                    'The smoke-test CBOR helper only supports string map keys.'
                );
            }
            $encoded .= encodeUntypedCbor($key);
            $encoded .= encodeUntypedCbor($item);
        }

        return $encoded;
    }

    throw new \InvalidArgumentException(
        'Unsupported value for the smoke-test CBOR helper.'
    );
}

function encodeCborHeader(int $majorType, int $length): string
{
    $prefix = $majorType << 5;
    if ($length < 24) {
        return \chr($prefix | $length);
    }
    if ($length <= 0xFF) {
        return \chr($prefix | 24) . \pack('C', $length);
    }
    if ($length <= 0xFFFF) {
        return \chr($prefix | 25) . \pack('n', $length);
    }

    return \chr($prefix | 26) . \pack('N', $length);
}

/**
 * @return array{
 *     statusCode: int,
 *     headers: array<string, string>,
 *     body: string
 * }
 */
function sendHttpRequest(int $port, string $path, string $body): array
{
    $context = \stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => [
                'Smithy-Protocol: rpc-v2-cbor',
                'Content-Type: application/cbor',
                'Content-Length: ' . \strlen($body)
            ],
            'content' => $body,
            'ignore_errors' => true,
            'timeout' => 5
        ]
    ]);
    $responseBody = \file_get_contents(
        "http://127.0.0.1:{$port}{$path}",
        false,
        $context
    );
    if ($responseBody === false) {
        throw new \RuntimeException('The raw HTTP request failed.');
    }

    $statusCode = 0;
    $headers = [];
    foreach ($http_response_header ?? [] as $index => $header) {
        if ($index === 0
            && \preg_match(
                '/\AHTTP\/\S+\s+([0-9]{3})\b/',
                $header,
                $matches
            ) === 1
        ) {
            $statusCode = (int) $matches[1];
            continue;
        }

        $separator = \strpos($header, ':');
        if ($separator !== false) {
            $headers[\strtolower(\substr($header, 0, $separator))] =
                \trim(\substr($header, $separator + 1));
        }
    }

    return [
        'statusCode' => $statusCode,
        'headers' => $headers,
        'body' => $responseBody
    ];
}

/**
 * @return array{0: resource, 1: array<int, resource>, 2: int}
 */
function startServer(string $root, string $dataFile): array
{
    $lastError = null;
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $port = reservePort();
        $environment = \getenv();
        if (!\is_array($environment)) {
            $environment = [];
        }
        $environment['PET_STORE_DATA_FILE'] = $dataFile;
        $process = \proc_open(
            [
                PHP_BINARY,
                '-S',
                "127.0.0.1:{$port}",
                'server/router.php'
            ],
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w']
            ],
            $pipes,
            $root,
            $environment
        );
        if (!\is_resource($process)) {
            throw new \RuntimeException(
                'Unable to start the PetStore server.'
            );
        }

        \fclose($pipes[0]);
        \stream_set_blocking($pipes[1], false);
        \stream_set_blocking($pipes[2], false);

        try {
            waitForServer($port, $process, $pipes);

            return [$process, $pipes, $port];
        } catch (\RuntimeException $error) {
            $lastError = $error;
            stopServer($process, $pipes);
        }
    }

    throw new \RuntimeException(
        'Unable to start the PetStore server after five attempts.',
        0,
        $lastError
    );
}

function reservePort(): int
{
    $listener = \stream_socket_server('tcp://127.0.0.1:0');
    if ($listener === false) {
        throw new \RuntimeException(
            'Unable to reserve a local test port.'
        );
    }

    $address = \stream_socket_get_name($listener, false);
    \fclose($listener);
    if (!\is_string($address)) {
        throw new \RuntimeException(
            'Unable to resolve the local test port.'
        );
    }

    return (int) \substr($address, \strrpos($address, ':') + 1);
}

/**
 * @param resource $process
 * @param array<int, resource> $pipes
 */
function stopServer($process, array $pipes): void
{
    $status = \proc_get_status($process);
    if ($status['running'] ?? false) {
        \proc_terminate($process);
    }
    foreach ($pipes as $pipe) {
        if (\is_resource($pipe)) {
            \fclose($pipe);
        }
    }
    \proc_close($process);
}

/**
 * @param resource $process
 * @param array<int, resource> $pipes
 */
function waitForServer(int $port, $process, array $pipes): void
{
    $deadline = \microtime(true) + 5.0;
    do {
        $status = \proc_get_status($process);
        if (!($status['running'] ?? false)) {
            $stderr = \stream_get_contents($pipes[2]);
            throw new \RuntimeException(
                'PetStore server exited before startup: '
                    . ($stderr === false ? '' : $stderr)
            );
        }

        $connection = @\fsockopen('127.0.0.1', $port);
        if (\is_resource($connection)) {
            \fclose($connection);
            \usleep(20_000);
            $status = \proc_get_status($process);
            if ($status['running'] ?? false) {
                return;
            }
        }

        \usleep(20_000);
    } while (\microtime(true) < $deadline);

    throw new \RuntimeException('Timed out starting the PetStore server.');
}

/**
 * @param callable(): mixed $operation
 */
function assertValidationError(
    callable $operation,
    string $field,
    string $message
): void {
    try {
        $operation();
    } catch (ValidationErrorException $error) {
        assertTrue($error->field === $field, $message);

        return;
    }

    throw new \RuntimeException($message);
}

/**
 * @param callable(): mixed $operation
 */
function assertPetConflict(callable $operation, string $message): void
{
    try {
        $operation();
    } catch (PetConflictErrorException) {
        return;
    }

    throw new \RuntimeException($message);
}

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new \RuntimeException($message);
    }
}
