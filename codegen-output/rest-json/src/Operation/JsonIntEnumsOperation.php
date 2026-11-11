<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestJson\Operation;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RestJson\Exception\RestJsonException;
use Smithy\ProtocolTests\RestJson\Shape\JsonIntEnumsInputOutput;

/**
 * This example serializes intEnums as top level properties, in lists, sets, and maps.
 */
final class JsonIntEnumsOperation implements OperationInterface
{
    public const NAME = 'JsonIntEnums';
    public const ID = 'aws.protocoltests.restjson#JsonIntEnums';
    public const SERVICE_ID = 'aws.protocoltests.restjson#RestJson';
    public const INPUT_CLASS = JsonIntEnumsInputOutput::class;
    public const OUTPUT_CLASS = JsonIntEnumsInputOutput::class;
    public const AUTH_SCHEMES = [
        'aws.auth#sigv4'
    ];
    public const ERRORS = [];

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

    public function getServiceId(): ?string
    {
        return self::SERVICE_ID;
    }

    public function getServiceExceptionClass(): string
    {
        return RestJsonException::class;
    }

    public function getRuntimeIdentity(): string
    {
        return self::class;
    }

    public function getEffectiveAuthSchemes(): array
    {
        return self::AUTH_SCHEMES;
    }

    public function getInputClass(): string
    {
        return self::INPUT_CLASS;
    }

    public function getOutputClass(): string
    {
        return self::OUTPUT_CLASS;
    }

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: 'aws.protocoltests.restjson#JsonIntEnums',
            type: ShapeType::Operation,
            traits: [
                Schema::T_HTTP => [
                    'method' => 'PUT',
                    'uri' => '/JsonIntEnums'
                ],
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
        return JsonIntEnumsInputOutput::schema();
    }

    public function getInputSchema(): Schema
    {
        return self::inputSchema();
    }

    public static function outputSchema(): Schema
    {
        return JsonIntEnumsInputOutput::schema();
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
        return [];
    }

    public function createInput(array $members): SerializableStructInterface
    {
        return JsonIntEnumsInputOutput::fromMembers($members);
    }

    public function createOutput(array $members): SerializableStructInterface
    {
        return JsonIntEnumsInputOutput::fromMembers($members);
    }

    public function getRequestTarget(): ?string
    {
        return null;
    }

    public function resolveErrorSchema(string $discriminator): ?Schema
    {
        return null;
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
