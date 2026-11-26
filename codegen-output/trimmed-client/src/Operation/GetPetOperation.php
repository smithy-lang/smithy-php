<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\CodegenOutput\TrimmedPetStore\Operation;

use Smithy\CodegenOutput\TrimmedPetStore\Exception\CommonErrorException;
use Smithy\CodegenOutput\TrimmedPetStore\Exception\ServiceErrorException;
use Smithy\CodegenOutput\TrimmedPetStore\Exception\TrimmedPetStoreException;
use Smithy\CodegenOutput\TrimmedPetStore\Input\GetPetInput;
use Smithy\CodegenOutput\TrimmedPetStore\Output\GetPetOutput;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Core\Shape\ShapeType;

final class GetPetOperation implements OperationInterface
{
    public const NAME = 'GetPet';
    public const ID = 'smithy.example#GetPet';
    public const SERVICE_ID = 'smithy.example#PetStore';
    public const INPUT_CLASS = GetPetInput::class;
    public const OUTPUT_CLASS = GetPetOutput::class;
    public const AUTH_SCHEMES = [
        'smithy.api#noAuth'
    ];
    public const ERRORS = [
        'smithy.example#ServiceError' => 'smithy.example#ServiceError',
        'ServiceError' => 'smithy.example#ServiceError',
        'smithy.example#CommonError' => 'smithy.example#CommonError',
        'CommonError' => 'smithy.example#CommonError'
    ];

    private static ?self $INSTANCE = null;
    private static ?Schema $SCHEMA = null;

    public static function instance(): self
    {
        return self::$INSTANCE ??= new self();
    }

    private function __construct() {}

    public function getName(): string
    {
        return self::NAME;
    }

    public function getId(): string
    {
        return self::ID;
    }

    public function getServiceId(): string
    {
        return self::SERVICE_ID;
    }

    public function getServiceExceptionClass(): string
    {
        return TrimmedPetStoreException::class;
    }

    public function getRuntimeIdentity(): string
    {
        return self::class;
    }

    public function getEffectiveAuthSchemes(): array
    {
        return self::AUTH_SCHEMES;
    }

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: 'smithy.example#GetPet',
            type: ShapeType::Operation,
            traits: [
                Schema::T_WAITABLE => [
                    'PetExists' => [
                        'minDelay' => 1,
                        'maxDelay' => 2,
                        'acceptors' => [
                            [
                                'state' => 'success',
                                'matcher' => [
                                    'success' => true
                                ]
                            ]
                        ]
                    ]
                ],
                Schema::T_ERRORS => self::ERRORS,
                Schema::T_REQUEST_COMPRESSION => ['gzip']
            ]
        );
    }

    public function getSchema(): Schema
    {
        return self::schema();
    }

    public static function inputSchema(): Schema
    {
        return GetPetInput::schema();
    }

    public function getInputSchema(): Schema
    {
        return self::inputSchema();
    }

    public static function outputSchema(): Schema
    {
        return GetPetOutput::schema();
    }

    public function getOutputSchema(): Schema
    {
        return self::outputSchema();
    }

    /**
     * @return list<Schema> Modeled error schemas.
     */
    public function getErrorSchemas(): array
    {
        return [
            ServiceErrorException::schema(),
            CommonErrorException::schema()
        ];
    }

    public function createInput(array $members): SerializableStructInterface
    {
        return GetPetInput::fromMembers($members);
    }

    public function createOutput(array $members): SerializableStructInterface
    {
        return GetPetOutput::fromMembers($members);
    }

    public function getRequestTarget(): ?string
    {
        return null;
    }

    public function resolveErrorSchema(string $discriminator): ?Schema
    {
        return match ($discriminator) {
            'smithy.example#ServiceError' => ServiceErrorException::schema(),
            'ServiceError' => ServiceErrorException::schema(),
            'smithy.example#CommonError' => CommonErrorException::schema(),
            'CommonError' => CommonErrorException::schema(),
            default => null
        };
    }

    public function getInputStreamMember(): ?Schema
    {
        return null;
    }

    public function getOutputStreamMember(): ?Schema
    {
        return null;
    }
}
