<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Examples\PetStore\Server;

/**
 * JSON-backed storage used by the local PetStore server.
 */
final class PetRepository
{
    private const NON_FINITE_FLOAT_KEY = '__smithyNonFiniteFloat';

    public function __construct(
        private readonly string $dataFile
    ) {}

    /**
     * Reset the store to its initial seeded state.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->withState(
            static function (array &$state): void {
                $state = self::initialState();
            }
        );
    }

    /**
     * @param array<string, mixed> $pet
     *
     * @return array<string, mixed>
     */
    public function create(array $pet): array
    {
        return $this->withState(
            static function (array &$state) use ($pet): array {
                foreach ($state['pets'] as $stored) {
                    if (self::foldName(
                        (string) ($stored['name'] ?? '')
                    ) === self::foldName(
                        (string) ($pet['name'] ?? '')
                    )) {
                        throw new ServiceError(
                            'smithy.example.petstore#PetConflictError',
                            409,
                            "A pet named {$pet['name']} already exists."
                        );
                    }
                }

                $petId = 'pet-' . $state['nextId']++;
                $now = \microtime(true);
                $record = $pet;
                $record['petId'] = $petId;
                $record['status'] = 'available';
                $record['createdAt'] = $now;
                $record['updatedAt'] = $now;
                $state['pets'][$petId] = self::encodeForStorage($record);

                return $record;
            }
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $petId): array
    {
        return $this->withState(
            static function (array &$state) use ($petId): array {
                if (!isset($state['pets'][$petId])) {
                    throw self::notFound($petId);
                }

                return self::decodeFromStorage($state['pets'][$petId]);
            }
        );
    }

    /**
     * @param array<string, mixed> $changes
     *
     * @return array<string, mixed>
     */
    public function update(string $petId, array $changes): array
    {
        return $this->withState(
            static function (array &$state) use ($petId, $changes): array {
                if (!isset($state['pets'][$petId])) {
                    throw self::notFound($petId);
                }

                if (isset($changes['name'])) {
                    foreach ($state['pets'] as $storedPetId => $stored) {
                        if ($storedPetId === $petId) {
                            continue;
                        }
                        if (self::foldName(
                            (string) ($stored['name'] ?? '')
                        ) === self::foldName(
                            (string) $changes['name']
                        )) {
                            throw new ServiceError(
                                'smithy.example.petstore#ValidationError',
                                400,
                                "A pet named {$changes['name']} already exists.",
                                ['field' => 'pet.name']
                            );
                        }
                    }
                }

                $original = $state['pets'][$petId];
                $record = self::decodeFromStorage($original);
                foreach ($changes as $name => $value) {
                    $record[$name] = $value;
                }
                $encoded = self::encodeForStorage($record);
                if ($encoded !== $original) {
                    $record['updatedAt'] = \microtime(true);
                    $encoded = self::encodeForStorage($record);
                }
                $state['pets'][$petId] = $encoded;

                return $record;
            }
        );
    }

    /**
     * @return void
     */
    public function delete(string $petId): void
    {
        $this->withState(
            static function (array &$state) use ($petId): void {
                if (!isset($state['pets'][$petId])) {
                    throw self::notFound($petId);
                }

                unset($state['pets'][$petId]);
            }
        );
    }

    /**
     * @return array{
     *     pets: list<array<string, mixed>>,
     *     nextToken: ?string
     * }
     */
    public function list(?string $nextToken, int $pageSize): array
    {
        return $this->withState(
            static function (array &$state) use (
                $nextToken,
                $pageSize
            ): array {
                $offset = self::decodeNextToken($nextToken);
                $pets = $state['pets'];
                \uksort(
                    $pets,
                    static fn(string $left, string $right): int =>
                        self::petNumber($left) <=> self::petNumber($right)
                );

                $page = \array_slice($pets, $offset, $pageSize, true);
                $nextOffset = $offset + \count($page);

                return [
                    'pets' => \array_values(\array_map(
                        self::decodeFromStorage(...),
                        $page
                    )),
                    'nextToken' => $nextOffset < \count($pets)
                        ? self::encodeNextToken($nextOffset)
                        : null
                ];
            }
        );
    }

    /**
     * @template T
     *
     * @param callable(array<string, mixed>&): T $callback
     *
     * @return T
     */
    private function withState(callable $callback): mixed
    {
        $directory = \dirname($this->dataFile);
        if (!\is_dir($directory)
            && !\mkdir($directory, 0777, true)
            && !\is_dir($directory)
        ) {
            throw new \RuntimeException(
                "Unable to create PetStore data directory {$directory}."
            );
        }

        $handle = \fopen($this->dataFile, 'c+');
        if ($handle === false) {
            throw new \RuntimeException(
                "Unable to open PetStore data file {$this->dataFile}."
            );
        }

        try {
            if (!\flock($handle, LOCK_EX)) {
                throw new \RuntimeException(
                    "Unable to lock PetStore data file {$this->dataFile}."
                );
            }

            $contents = \stream_get_contents($handle);
            if ($contents === false) {
                throw new \RuntimeException(
                    "Unable to read PetStore data file {$this->dataFile}."
                );
            }
            $state = $contents === ''
                ? self::initialState()
                : \json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
            if (!\is_array($state)) {
                throw new \RuntimeException('Invalid PetStore data file.');
            }

            $result = $callback($state);
            $encoded = \json_encode(
                $state,
                JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR
            );
            $payload = $encoded . PHP_EOL;
            if (!\rewind($handle) || !\ftruncate($handle, 0)) {
                throw new \RuntimeException(
                    "Unable to write PetStore data file {$this->dataFile}."
                );
            }
            $written = \fwrite($handle, $payload);
            if ($written === false
                || $written !== \strlen($payload)
                || !\fflush($handle)
            ) {
                throw new \RuntimeException(
                    "Unable to write PetStore data file {$this->dataFile}."
                );
            }

            return $result;
        } finally {
            \flock($handle, LOCK_UN);
            \fclose($handle);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function initialState(): array
    {
        $now = 1_767_225_600.0;

        return [
            'nextId' => 2,
            'pets' => [
                'pet-1' => self::encodeForStorage([
                    'petId' => 'pet-1',
                    'name' => 'Scully',
                    'species' => 'dog',
                    'status' => 'available',
                    'ageYears' => 3,
                    'description' => 'A clever dog who loves the water.',
                    'photo' => 'sample-photo-bytes',
                    'traits' => ['bright', 'energetic', 'friendly'],
                    'attributes' => [
                        'color' => 'red',
                        'coat' => 'medium'
                    ],
                    'vaccinations' => [
                        [
                            'name' => 'rabies',
                            'administeredAt' => $now
                        ],
                        null
                    ],
                    'details' => [
                        'dog' => [
                            'breed' => 'Nova Scotia Duck Tolling Retriever',
                            'favoriteToy' => 'flying disc'
                        ]
                    ],
                    'metrics' => [
                        'houseTrained' => true,
                        'temperamentScore' => 9,
                        'kennelNumber' => 12,
                        'eventSequence' => 4_294_967_296,
                        'weightKilograms' => 18.1,
                        'latitude' => 35.7796,
                        'registrySequence' => '9007199254740993',
                        'adoptionFee' => '125.50',
                        'medicalPriority' => 1,
                        'measurements' => [
                            'heightCentimeters' => 48.0,
                            'tailLengthCentimeters' => null
                        ]
                    ],
                    'createdAt' => $now,
                    'updatedAt' => $now
                ])
            ]
        ];
    }

    /**
     * @param array<string, mixed> $record
     *
     * @return array<string, mixed>
     */
    private static function encodeForStorage(array $record): array
    {
        $record = self::transformMetricFloats(
            $record,
            self::encodeFloatForStorage(...)
        );

        if (isset($record['photo'])) {
            $record['photo'] = [
                'base64' => \base64_encode((string) $record['photo'])
            ];
        }

        return $record;
    }

    /**
     * @param array<string, mixed> $record
     *
     * @return array<string, mixed>
     */
    private static function decodeFromStorage(array $record): array
    {
        if (isset($record['photo']['base64'])
            && \is_string($record['photo']['base64'])
        ) {
            $decoded = \base64_decode($record['photo']['base64'], true);
            if ($decoded === false) {
                throw new \RuntimeException(
                    'Invalid base64 photo in PetStore data file.'
                );
            }
            $record['photo'] = $decoded;
        }

        return self::transformMetricFloats(
            $record,
            self::decodeFloatFromStorage(...)
        );
    }

    /**
     * @param array<string, mixed> $record
     * @param callable(mixed): mixed $transform
     *
     * @return array<string, mixed>
     */
    private static function transformMetricFloats(
        array $record,
        callable $transform
    ): array {
        if (!isset($record['metrics']) || !\is_array($record['metrics'])) {
            return $record;
        }

        foreach (['weightKilograms', 'latitude'] as $name) {
            if (\array_key_exists($name, $record['metrics'])) {
                $record['metrics'][$name] = $transform(
                    $record['metrics'][$name]
                );
            }
        }

        if (isset($record['metrics']['measurements'])
            && \is_array($record['metrics']['measurements'])
        ) {
            foreach (
                $record['metrics']['measurements'] as $name => $value
            ) {
                $record['metrics']['measurements'][$name] = $transform(
                    $value
                );
            }
        }

        return $record;
    }

    private static function encodeFloatForStorage(mixed $value): mixed
    {
        if (!\is_float($value) || \is_finite($value)) {
            return $value;
        }

        return [
            self::NON_FINITE_FLOAT_KEY => \is_nan($value)
                ? 'NaN'
                : ($value > 0 ? 'Infinity' : '-Infinity')
        ];
    }

    private static function decodeFloatFromStorage(mixed $value): mixed
    {
        if (!\is_array($value)
            || !\array_key_exists(self::NON_FINITE_FLOAT_KEY, $value)
        ) {
            return $value;
        }

        $encoded = $value[self::NON_FINITE_FLOAT_KEY];
        if (\count($value) !== 1 || !\is_string($encoded)) {
            throw new \RuntimeException(
                'Invalid non-finite float in PetStore data file.'
            );
        }

        return match ($encoded) {
            'NaN' => \NAN,
            'Infinity' => \INF,
            '-Infinity' => -\INF,
            default => throw new \RuntimeException(
                'Invalid non-finite float in PetStore data file.'
            )
        };
    }

    private static function notFound(string $petId): ServiceError
    {
        return new ServiceError(
            'smithy.example.petstore#PetNotFoundError',
            404,
            "Pet {$petId} was not found.",
            ['petId' => $petId]
        );
    }

    private static function foldName(string $name): string
    {
        return \mb_convert_case($name, \MB_CASE_FOLD, 'UTF-8');
    }

    private static function encodeNextToken(int $offset): string
    {
        return \rtrim(
            \strtr(\base64_encode((string) $offset), '+/', '-_'),
            '='
        );
    }

    private static function decodeNextToken(?string $nextToken): int
    {
        if ($nextToken === null || $nextToken === '') {
            return 0;
        }

        $padding = (4 - \strlen($nextToken) % 4) % 4;
        $decoded = \base64_decode(
            \strtr($nextToken, '-_', '+/') . \str_repeat('=', $padding),
            true
        );
        if ($decoded === false
            || !\ctype_digit($decoded)
        ) {
            throw new ServiceError(
                'smithy.example.petstore#ValidationError',
                400,
                'The pagination token is invalid.',
                ['field' => 'nextToken']
            );
        }

        return (int) $decoded;
    }

    private static function petNumber(string $petId): int
    {
        return (int) \substr($petId, 4);
    }
}
