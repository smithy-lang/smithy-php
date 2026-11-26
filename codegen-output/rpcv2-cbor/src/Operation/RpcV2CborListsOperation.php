<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RpcV2Cbor\Operation;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RpcV2Cbor\Exception\RpcV2CborException;
use Smithy\ProtocolTests\RpcV2Cbor\Exception\ValidationException;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\RpcV2CborListInputOutput;

/**
 * This test case serializes JSON lists for the following cases for both input and output: 1. Normal lists. 2. Normal
 * sets. 3. Lists of lists. 4. Lists of structures.
 */
final class RpcV2CborListsOperation implements OperationInterface
{
    public const NAME = 'RpcV2CborLists';
    public const ID = 'smithy.protocoltests.rpcv2Cbor#RpcV2CborLists';
    public const SERVICE_ID = 'smithy.protocoltests.rpcv2Cbor#RpcV2Protocol';
    public const INPUT_CLASS = RpcV2CborListInputOutput::class;
    public const OUTPUT_CLASS = RpcV2CborListInputOutput::class;
    public const AUTH_SCHEMES = [
        'smithy.api#noAuth'
    ];
    public const ERRORS = [
        'smithy.framework#ValidationException' => 'smithy.framework#ValidationException',
        'ValidationException' => 'smithy.framework#ValidationException'
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
        return RpcV2CborException::class;
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
            id: 'smithy.protocoltests.rpcv2Cbor#RpcV2CborLists',
            type: ShapeType::Operation,
            traits: [
                Schema::T_ERRORS => self::ERRORS,
                Schema::T_IDEMPOTENT => true
            ]
        );
    }

    public function getSchema(): Schema
    {
        return self::schema();
    }

    public static function inputSchema(): Schema
    {
        return RpcV2CborListInputOutput::schema();
    }

    public function getInputSchema(): Schema
    {
        return self::inputSchema();
    }

    public static function outputSchema(): Schema
    {
        return RpcV2CborListInputOutput::schema();
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
            ValidationException::schema()
        ];
    }

    public function createInput(array $members): SerializableStructInterface
    {
        return RpcV2CborListInputOutput::fromMembers($members);
    }

    public function createOutput(array $members): SerializableStructInterface
    {
        return RpcV2CborListInputOutput::fromMembers($members);
    }

    public function getRequestTarget(): ?string
    {
        return null;
    }

    public function resolveErrorSchema(string $discriminator): ?Schema
    {
        return match ($discriminator) {
            'smithy.framework#ValidationException' => ValidationException::schema(),
            'ValidationException' => ValidationException::schema(),
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
