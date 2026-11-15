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
use Smithy\ProtocolTests\AwsJson1_1\Exception\ComplexErrorException;
use Smithy\ProtocolTests\AwsJson1_1\Exception\FooErrorException;
use Smithy\ProtocolTests\AwsJson1_1\Exception\InvalidGreetingException;
use Smithy\ProtocolTests\AwsJson1_1\Output\GreetingWithErrorsOutput;
use Smithy\ProtocolTests\AwsJson1_1\Shape\Unit;

/**
 * This operation has three possible return values: 1. A successful response in the form of GreetingWithErrorsOutput 2.
 * An InvalidGreeting error. 3. A ComplexError error. Implementations must be able to successfully take a response and
 * properly deserialize successful and error responses.
 */
final class GreetingWithErrorsOperation implements OperationInterface
{
    public const NAME = 'GreetingWithErrors';
    public const ID = 'aws.protocoltests.json#GreetingWithErrors';
    public const SERVICE_ID = 'aws.protocoltests.json#JsonProtocol';
    public const INPUT_CLASS = Unit::class;
    public const OUTPUT_CLASS = GreetingWithErrorsOutput::class;
    public const AUTH_SCHEMES = [
        'aws.auth#sigv4'
    ];
    public const ERRORS = [
        'aws.protocoltests.json#ComplexError' => 'aws.protocoltests.json#ComplexError',
        'ComplexError' => 'aws.protocoltests.json#ComplexError',
        'aws.protocoltests.json#FooError' => 'aws.protocoltests.json#FooError',
        'FooError' => 'aws.protocoltests.json#FooError',
        'aws.protocoltests.json#InvalidGreeting' => 'aws.protocoltests.json#InvalidGreeting',
        'InvalidGreeting' => 'aws.protocoltests.json#InvalidGreeting'
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
            id: 'aws.protocoltests.json#GreetingWithErrors',
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
        return Unit::schema();
    }

    public function getInputSchema(): Schema
    {
        return self::inputSchema();
    }

    public static function outputSchema(): Schema
    {
        return GreetingWithErrorsOutput::schema();
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
            ComplexErrorException::schema(),
            FooErrorException::schema(),
            InvalidGreetingException::schema()
        ];
    }

    public function createInput(array $members): SerializableStructInterface
    {
        return Unit::fromMembers($members);
    }

    public function createOutput(array $members): SerializableStructInterface
    {
        return GreetingWithErrorsOutput::fromMembers($members);
    }

    public function getRequestTarget(): ?string
    {
        return null;
    }

    public function resolveErrorSchema(string $discriminator): ?Schema
    {
        return match ($discriminator) {
            'aws.protocoltests.json#ComplexError' => ComplexErrorException::schema(),
            'ComplexError' => ComplexErrorException::schema(),
            'aws.protocoltests.json#FooError' => FooErrorException::schema(),
            'FooError' => FooErrorException::schema(),
            'aws.protocoltests.json#InvalidGreeting' => InvalidGreetingException::schema(),
            'InvalidGreeting' => InvalidGreetingException::schema(),
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
