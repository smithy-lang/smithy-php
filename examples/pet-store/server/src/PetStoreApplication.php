<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Examples\PetStore\Server;

use Smithy\Codec\Cbor\CborCodec;
use Smithy\Codec\Cbor\CborCodecSettings;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\TimestampFormat;
use Smithy\Core\Shape\EnumInterface;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\UnionInterface;
use Smithy\Core\Shape\Utils\ArrayShapeSerializer;
use Smithy\Examples\PetStore\Input\{
    CreatePetInput,
    DeletePetInput,
    GetPetInput,
    ListPetsInput,
    UpdatePetInput
};
use Smithy\Examples\PetStore\Operation\{
    CreatePetOperation,
    DeletePetOperation,
    GetPetOperation,
    ListPetsOperation,
    UpdatePetOperation
};
use Smithy\Examples\PetStore\Output\{
    CreatePetOutput,
    GetPetOutput,
    ListPetsOutput,
    UpdatePetOutput
};
use Smithy\Examples\PetStore\Shape\{
    PetRecord,
    Unit
};
use Smithy\Http\Message\StringRequestStream;

/**
 * Minimal RPCv2 CBOR application used by the PetStore quickstart.
 */
final class PetStoreApplication
{
    private const MAX_PET_NAME_LENGTH = 64;
    private const MEDIA_TYPE = 'application/cbor';
    private const MODELED_CLIENT_ERROR_SHAPES = [
        'smithy.example.petstore#PetConflictError',
        'smithy.example.petstore#PetNotFoundError',
        'smithy.example.petstore#ValidationError'
    ];
    private const PET_NAME_SHAPE_ID =
        'smithy.example.petstore#PetName';
    private const PROTOCOL = 'rpc-v2-cbor';
    private const ROUTE_PREFIX = '/service/PetStore/operation/';
    private const MAX_REQUEST_BYTES = 1_048_576;

    /**
     * Matches repository IDs in the `pet-<number>` format.
     */
    private const PET_ID_PATTERN = '/\Apet-[0-9]+\z/';

    private readonly CborCodec $codec;

    public function __construct(
        private readonly PetRepository $repository
    ) {
        $this->codec = new CborCodec(new CborCodecSettings(
            defaultTimestampFormat: TimestampFormat::EpochSeconds,
            honorTimestampFormatTrait: false
        ));
    }

    /**
     * @param array<string, string> $headers
     */
    public function handle(
        string $method,
        string $path,
        array $headers,
        string $body
    ): ServerResponse {
        try {
            if ($method !== 'POST') {
                throw new ServiceError(
                    'smithy.example.petstore#ValidationError',
                    405,
                    'PetStore operations require POST requests.'
                );
            }
            if (\array_key_exists('x-amz-target', $headers)
                || \array_key_exists('x-amzn-target', $headers)
            ) {
                throw new ServiceError(
                    'smithy.example.petstore#ValidationError',
                    400,
                    'X-Amz-Target and X-Amzn-Target headers are not allowed.'
                );
            }
            if (($headers['smithy-protocol'] ?? '') !== self::PROTOCOL) {
                throw new ServiceError(
                    'smithy.example.petstore#ValidationError',
                    400,
                    'The Smithy-Protocol header must be rpc-v2-cbor.'
                );
            }
            if (($headers['content-type'] ?? '') !== self::MEDIA_TYPE) {
                throw new ServiceError(
                    'smithy.example.petstore#ValidationError',
                    415,
                    'The Content-Type header must be application/cbor.'
                );
            }
            if (\strlen($body) > self::MAX_REQUEST_BYTES) {
                throw new ServiceError(
                    'smithy.example.petstore#ValidationError',
                    413,
                    'The request body exceeds the one MiB example limit.'
                );
            }

            $operationName = $this->operationName($path);
            $operation = $this->operation($operationName);
            $input = $this->decodeInput($operation, $body);
            $output = $this->dispatch($operationName, $input);

            return $this->success($output);
        } catch (ServiceError $error) {
            return $this->error($error);
        } catch (\Throwable $error) {
            return $this->error(new ServiceError(
                'smithy.example.petstore#InternalServerError',
                500,
                'The local PetStore server could not process the request.'
            ));
        }
    }

    private function operationName(string $path): string
    {
        $routeOffset = \strrpos($path, self::ROUTE_PREFIX);
        if ($routeOffset === false) {
            throw new ServiceError(
                'smithy.example.petstore#ValidationError',
                404,
                'Unknown PetStore operation path.'
            );
        }

        $operationName = \substr(
            $path,
            $routeOffset + \strlen(self::ROUTE_PREFIX)
        );
        if ($operationName === '' || \str_contains($operationName, '/')) {
            throw new ServiceError(
                'smithy.example.petstore#ValidationError',
                404,
                'Unknown PetStore operation path.'
            );
        }

        return $operationName;
    }

    private function operation(string $name): OperationInterface
    {
        return match ($name) {
            'CreatePet' => CreatePetOperation::instance(),
            'DeletePet' => DeletePetOperation::instance(),
            'GetPet' => GetPetOperation::instance(),
            'ListPets' => ListPetsOperation::instance(),
            'UpdatePet' => UpdatePetOperation::instance(),
            default => throw new ServiceError(
                'smithy.example.petstore#ValidationError',
                404,
                "Unknown PetStore operation {$name}."
            )
        };
    }

    private function decodeInput(
        OperationInterface $operation,
        string $body
    ): SerializableStructInterface {
        $schema = $operation->getInputSchema()->resolveTarget();
        $members = $this->codec->createMembersBuffer($schema);

        try {
            $this->codec
                ->createDeserializer(new StringRequestStream($body))
                ->deserialize($schema, $members);

            return $operation->createInput($members);
        } catch (\Throwable $error) {
            throw new ServiceError(
                'smithy.example.petstore#ValidationError',
                400,
                'The request body is not valid RPCv2 CBOR.'
            );
        }
    }

    private function dispatch(
        string $operationName,
        SerializableStructInterface $input
    ): SerializableStructInterface {
        return match ($operationName) {
            'CreatePet' => $this->createPet($input),
            'DeletePet' => $this->deletePet($input),
            'GetPet' => $this->getPet($input),
            'ListPets' => $this->listPets($input),
            'UpdatePet' => $this->updatePet($input),
            default => throw new \LogicException(
                "No handler for {$operationName}."
            )
        };
    }

    private function createPet(
        SerializableStructInterface $input
    ): CreatePetOutput {
        if (!$input instanceof CreatePetInput || $input->pet === null) {
            throw $this->validation('pet', 'Pet details are required.');
        }
        $this->validateModeledValue(
            $input->pet->getSchema(),
            $input->pet,
            'pet'
        );

        return new CreatePetOutput(
            pet: $this->petRecord($this->repository->create(
                ArrayShapeSerializer::toArray($input->pet)
            ))
        );
    }

    private function getPet(
        SerializableStructInterface $input
    ): GetPetOutput {
        if (!$input instanceof GetPetInput) {
            throw $this->validation('input', 'Get input is invalid.');
        }
        $petId = $this->requirePetId($input->petId);

        return new GetPetOutput(
            pet: $this->petRecord($this->repository->get($petId))
        );
    }

    private function updatePet(
        SerializableStructInterface $input
    ): UpdatePetOutput {
        if (!$input instanceof UpdatePetInput) {
            throw $this->validation('input', 'Update input is invalid.');
        }
        $petId = $this->requirePetId($input->petId);
        if ($input->pet === null) {
            throw $this->validation('pet', 'Pet changes are required.');
        }
        $this->validateModeledValue(
            $input->pet->getSchema(),
            $input->pet,
            'pet'
        );

        return new UpdatePetOutput(
            pet: $this->petRecord($this->repository->update(
                $petId,
                ArrayShapeSerializer::toArray($input->pet)
            ))
        );
    }

    private function deletePet(
        SerializableStructInterface $input
    ): Unit {
        if (!$input instanceof DeletePetInput) {
            throw $this->validation('input', 'Delete input is invalid.');
        }
        $petId = $this->requirePetId($input->petId);

        $this->repository->delete($petId);

        return new Unit();
    }

    private function listPets(
        SerializableStructInterface $input
    ): ListPetsOutput {
        if (!$input instanceof ListPetsInput) {
            throw $this->validation('input', 'List input is invalid.');
        }

        $pageSize = $input->getPaginationPageSize() ?? 10;
        if ($pageSize < 1 || $pageSize > 25) {
            throw $this->validation(
                'pageSize',
                'Page size must be between 1 and 25.'
            );
        }

        $page = $this->repository->list(
            $input->getPaginationToken(),
            $pageSize
        );

        return new ListPetsOutput(
            pets: \array_map(
                $this->petRecord(...),
                $page['pets']
            ),
            nextToken: $page['nextToken']
        );
    }

    private function requirePetId(?string $petId): string
    {
        if ($petId === null
            || \preg_match(self::PET_ID_PATTERN, $petId) !== 1
        ) {
            throw $this->validation(
                'petId',
                'Pet ID must use the pet-<number> format.'
            );
        }

        return $petId;
    }

    private function validateModeledValue(
        Schema $schema,
        mixed $value,
        string $path
    ): void {
        $schema = $schema->resolveTarget();
        if ($schema->id === self::PET_NAME_SHAPE_ID) {
            $this->validatePetName($value, $path);

            return;
        }

        switch ($schema->type) {
            case ShapeType::Structure->value:
                $this->validateStructure($schema, $value, $path);
                break;
            case ShapeType::Union->value:
                $this->validateUnion($schema, $value, $path);
                break;
            case ShapeType::List_->value:
                $this->validateList($schema, $value, $path);
                break;
            case ShapeType::Map->value:
                $this->validateMap($schema, $value, $path);
                break;
            case ShapeType::Byte->value:
            case ShapeType::Short->value:
            case ShapeType::Integer->value:
                [$min, $max] = match ($schema->type) {
                    ShapeType::Byte->value => [-128, 127],
                    ShapeType::Short->value => [-32_768, 32_767],
                    default => [-2_147_483_648, 2_147_483_647]
                };
                if (!\is_int($value) || $value < $min || $value > $max) {
                    throw $this->validation(
                        $path,
                        "{$path} must be between {$min} and {$max}."
                    );
                }
                break;
            case ShapeType::Enum->value:
            case ShapeType::IntEnum->value:
                $this->validateEnum($value, $path);
                break;
        }
    }

    private function validateStructure(
        Schema $schema,
        mixed $value,
        string $path
    ): void {
        if (!$value instanceof SerializableStructInterface) {
            throw $this->validation($path, "{$path} must be an object.");
        }

        foreach ($schema->getMembersByIndex() as $member) {
            $memberPath = "{$path}.{$member->memberName}";
            $memberValue = $value->getMemberValue($member);
            if ($memberValue === null) {
                if ($member->isRequired()) {
                    throw $this->validation(
                        $memberPath,
                        "{$memberPath} is required."
                    );
                }

                continue;
            }

            $this->validateModeledValue(
                $member,
                $memberValue,
                $memberPath
            );
        }
    }

    private function validateUnion(
        Schema $schema,
        mixed $value,
        string $path
    ): void {
        if (!$value instanceof UnionInterface) {
            throw $this->validation($path, "{$path} must be a union.");
        }

        $member = $schema->getMemberByIndex($value->getMemberIndex());
        if ($member === null) {
            throw $this->validation(
                $path,
                "{$path} contains an unsupported variant."
            );
        }

        $this->validateModeledValue(
            $member,
            $value->value(),
            "{$path}.{$member->memberName}"
        );
    }

    private function validateList(
        Schema $schema,
        mixed $value,
        string $path
    ): void {
        if (!\is_array($value)) {
            throw $this->validation($path, "{$path} must be a list.");
        }

        $member = $schema->getListMember();
        if ($member === null) {
            throw new \LogicException("List schema {$schema->id} has no member.");
        }

        foreach ($value as $index => $item) {
            $itemPath = "{$path}[{$index}]";
            if ($item === null) {
                if (!$schema->hasTrait(Schema::T_SPARSE)) {
                    throw $this->validation(
                        $itemPath,
                        "{$itemPath} must not be null."
                    );
                }

                continue;
            }

            $this->validateModeledValue($member, $item, $itemPath);
        }
    }

    private function validateMap(
        Schema $schema,
        mixed $value,
        string $path
    ): void {
        if (!\is_array($value)) {
            throw $this->validation($path, "{$path} must be a map.");
        }

        $mapValue = $schema->getMapValue();
        if ($mapValue === null) {
            throw new \LogicException(
                "Map schema {$schema->id} has no value member."
            );
        }

        foreach ($value as $key => $item) {
            $itemPath = "{$path}[{$key}]";
            if ($item === null) {
                if (!$schema->hasTrait(Schema::T_SPARSE)) {
                    throw $this->validation(
                        $itemPath,
                        "{$itemPath} must not be null."
                    );
                }

                continue;
            }

            $this->validateModeledValue($mapValue, $item, $itemPath);
        }
    }

    private function validateEnum(mixed $value, string $path): void
    {
        if (!$value instanceof EnumInterface) {
            throw $this->validation(
                $path,
                "{$path} must be a modeled enum value."
            );
        }
    }

    private function validatePetName(mixed $name, string $path): void
    {
        $length = \is_string($name)
            ? \mb_strlen($name, 'UTF-8')
            : 0;
        if ($length < 1 || $length > self::MAX_PET_NAME_LENGTH) {
            throw $this->validation(
                $path,
                'Pet name must contain between 1 and 64 characters.'
            );
        }
    }

    /**
     * @param array<string, mixed> $record
     */
    private function petRecord(array $record): PetRecord
    {
        $pet = ArrayShapeSerializer::fromArray(PetRecord::class, $record);
        if (!$pet instanceof PetRecord) {
            throw new \LogicException('Unable to hydrate PetRecord.');
        }

        return $pet;
    }

    private function validation(
        string $field,
        string $message
    ): ServiceError {
        return new ServiceError(
            'smithy.example.petstore#ValidationError',
            400,
            $message,
            ['field' => $field]
        );
    }

    private function success(
        SerializableStructInterface $output
    ): ServerResponse {
        $body = '';
        if (!$output instanceof Unit) {
            $serializer = $this->codec->acquireSerializer();
            try {
                $output->serialize($serializer, 0);
                $body = $serializer->flush();
            } finally {
                $this->codec->releaseSerializer($serializer);
            }
        }

        $headers = $this->responseHeaders($body);
        if ($output instanceof Unit) {
            unset($headers['Content-Type']);
        }

        return new ServerResponse(
            200,
            $headers,
            $body
        );
    }

    private function error(ServiceError $error): ServerResponse
    {
        $serializer = $this->codec->acquireSerializer();
        try {
            (new RpcV2ErrorEnvelope(
                $error->shapeId,
                $error->getMessage(),
                $error->fields
            ))->serialize($serializer, 0);
            $body = $serializer->flush();
        } finally {
            $this->codec->releaseSerializer($serializer);
        }

        $statusCode = \in_array(
            $error->shapeId,
            self::MODELED_CLIENT_ERROR_SHAPES,
            true
        ) ? 400 : $error->statusCode;

        return new ServerResponse(
            $statusCode,
            $this->responseHeaders($body),
            $body
        );
    }

    /**
     * @return array<string, string>
     */
    private function responseHeaders(string $body): array
    {
        return [
            'Content-Type' => self::MEDIA_TYPE,
            'Content-Length' => (string) \strlen($body),
            'Smithy-Protocol' => self::PROTOCOL
        ];
    }
}
