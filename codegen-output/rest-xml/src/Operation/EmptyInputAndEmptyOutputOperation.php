<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestXml\Operation;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RestXml\Exception\RestXmlException;
use Smithy\ProtocolTests\RestXml\Input\EmptyInputAndEmptyOutputInput;
use Smithy\ProtocolTests\RestXml\Output\EmptyInputAndEmptyOutputOutput;

/**
 * The example tests how requests and responses are serialized when there's no request or response payload because the
 * operation has an empty input and empty output structure that reuses the same shape. While this should be rare, code
 * generators must support this.
 */
final class EmptyInputAndEmptyOutputOperation implements OperationInterface
{
    public const NAME = 'EmptyInputAndEmptyOutput';
    public const ID = 'aws.protocoltests.restxml#EmptyInputAndEmptyOutput';
    public const SERVICE_ID = 'aws.protocoltests.restxml#RestXml';
    public const INPUT_CLASS = EmptyInputAndEmptyOutputInput::class;
    public const OUTPUT_CLASS = EmptyInputAndEmptyOutputOutput::class;
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
        return RestXmlException::class;
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
            id: 'aws.protocoltests.restxml#EmptyInputAndEmptyOutput',
            type: ShapeType::Operation,
            traits: [
                Schema::T_HTTP => [
                    'method' => 'POST',
                    'uri' => '/EmptyInputAndEmptyOutput'
                ]
            ]
        );
    }

    public function getSchema(): Schema
    {
        return self::schema();
    }

    public static function inputSchema(): Schema
    {
        return EmptyInputAndEmptyOutputInput::schema();
    }

    public function getInputSchema(): Schema
    {
        return self::inputSchema();
    }

    public static function outputSchema(): Schema
    {
        return EmptyInputAndEmptyOutputOutput::schema();
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
        return EmptyInputAndEmptyOutputInput::fromMembers($members);
    }

    public function createOutput(array $members): SerializableStructInterface
    {
        return EmptyInputAndEmptyOutputOutput::fromMembers($members);
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
