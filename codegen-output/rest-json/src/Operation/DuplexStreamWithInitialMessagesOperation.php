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
use Smithy\ProtocolTests\RestJson\Exception\ServiceUnavailableErrorException;
use Smithy\ProtocolTests\RestJson\Input\DuplexStreamWithInitialMessagesInput;
use Smithy\ProtocolTests\RestJson\Output\DuplexStreamWithInitialMessagesOutput;

final class DuplexStreamWithInitialMessagesOperation implements OperationInterface
{
    public const NAME = 'DuplexStreamWithInitialMessages';
    public const ID = 'aws.protocoltests.restjson#DuplexStreamWithInitialMessages';
    public const SERVICE_ID = 'aws.protocoltests.restjson#RestJson';
    public const INPUT_CLASS = DuplexStreamWithInitialMessagesInput::class;
    public const OUTPUT_CLASS = DuplexStreamWithInitialMessagesOutput::class;
    public const AUTH_SCHEMES = [
        'aws.auth#sigv4'
    ];
    public const ERRORS = [
        'aws.protocoltests.restjson#ServiceUnavailableError' => 'aws.protocoltests.restjson#ServiceUnavailableError',
        'ServiceUnavailableError' => 'aws.protocoltests.restjson#ServiceUnavailableError'
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

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: 'aws.protocoltests.restjson#DuplexStreamWithInitialMessages',
            type: ShapeType::Operation,
            traits: [
                Schema::T_HTTP => [
                    'method' => 'POST',
                    'uri' => '/DuplexStreamWithInitialMessages'
                ],
                Schema::T_ERRORS => self::ERRORS
            ]
        );
    }

    public function getSchema(): Schema
    {
        return self::schema();
    }

    public static function inputSchema(): Schema
    {
        return DuplexStreamWithInitialMessagesInput::schema();
    }

    public function getInputSchema(): Schema
    {
        return self::inputSchema();
    }

    public static function outputSchema(): Schema
    {
        return DuplexStreamWithInitialMessagesOutput::schema();
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
            ServiceUnavailableErrorException::schema()
        ];
    }

    public function createInput(array $members): SerializableStructInterface
    {
        return DuplexStreamWithInitialMessagesInput::fromMembers($members);
    }

    public function createOutput(array $members): SerializableStructInterface
    {
        return DuplexStreamWithInitialMessagesOutput::fromMembers($members);
    }

    public function getRequestTarget(): ?string
    {
        return null;
    }

    public function resolveErrorSchema(string $discriminator): ?Schema
    {
        return match ($discriminator) {
            'aws.protocoltests.restjson#ServiceUnavailableError' => ServiceUnavailableErrorException::schema(),
            'ServiceUnavailableError' => ServiceUnavailableErrorException::schema(),
            default => null
        };
    }

    public function getInputStreamMember(): ?Schema
    {
        return DuplexStreamWithInitialMessagesInput::schema()->getMembersByIndex()[1];
    }

    public function getOutputStreamMember(): ?Schema
    {
        return DuplexStreamWithInitialMessagesOutput::schema()->getMembersByIndex()[1];
    }
}
