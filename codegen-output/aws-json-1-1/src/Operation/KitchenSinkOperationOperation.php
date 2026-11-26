<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\AwsJson1_1\Operation;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\AwsJson1_1\Exception\AwsJson1_1Exception;
use Smithy\ProtocolTests\AwsJson1_1\Exception\ErrorWithMembersException;
use Smithy\ProtocolTests\AwsJson1_1\Exception\ErrorWithoutMembersException;
use Smithy\ProtocolTests\AwsJson1_1\Shape\KitchenSink;

final class KitchenSinkOperationOperation implements OperationInterface
{
    public const NAME = 'KitchenSinkOperation';
    public const ID = 'aws.protocoltests.json#KitchenSinkOperation';
    public const SERVICE_ID = 'aws.protocoltests.json#JsonProtocol';
    public const INPUT_CLASS = KitchenSink::class;
    public const OUTPUT_CLASS = KitchenSink::class;
    public const AUTH_SCHEMES = [
        'aws.auth#sigv4'
    ];
    public const ERRORS = [
        'aws.protocoltests.json#ErrorWithMembers' => 'aws.protocoltests.json#ErrorWithMembers',
        'ErrorWithMembers' => 'aws.protocoltests.json#ErrorWithMembers',
        'aws.protocoltests.json#ErrorWithoutMembers' => 'aws.protocoltests.json#ErrorWithoutMembers',
        'ErrorWithoutMembers' => 'aws.protocoltests.json#ErrorWithoutMembers'
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
        return AwsJson1_1Exception::class;
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
            id: 'aws.protocoltests.json#KitchenSinkOperation',
            type: ShapeType::Operation,
            traits: [
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
        return KitchenSink::schema();
    }

    public function getInputSchema(): Schema
    {
        return self::inputSchema();
    }

    public static function outputSchema(): Schema
    {
        return KitchenSink::schema();
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
            ErrorWithMembersException::schema(),
            ErrorWithoutMembersException::schema()
        ];
    }

    public function createInput(array $members): SerializableStructInterface
    {
        return KitchenSink::fromMembers($members);
    }

    public function createOutput(array $members): SerializableStructInterface
    {
        return KitchenSink::fromMembers($members);
    }

    public function getRequestTarget(): ?string
    {
        return null;
    }

    public function resolveErrorSchema(string $discriminator): ?Schema
    {
        return match ($discriminator) {
            'aws.protocoltests.json#ErrorWithMembers' => ErrorWithMembersException::schema(),
            'ErrorWithMembers' => ErrorWithMembersException::schema(),
            'aws.protocoltests.json#ErrorWithoutMembers' => ErrorWithoutMembersException::schema(),
            'ErrorWithoutMembers' => ErrorWithoutMembersException::schema(),
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
