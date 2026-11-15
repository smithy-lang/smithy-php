<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\AwsQuery\Operation;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\AwsQuery\Exception\AwsQueryException;
use Smithy\ProtocolTests\AwsQuery\Exception\ComplexErrorException;
use Smithy\ProtocolTests\AwsQuery\Exception\CustomCodeErrorException;
use Smithy\ProtocolTests\AwsQuery\Exception\InvalidGreetingException;
use Smithy\ProtocolTests\AwsQuery\Output\GreetingWithErrorsOutput;
use Smithy\ProtocolTests\AwsQuery\Shape\Unit;

/**
 * This operation has three possible return values: 1. A successful response in the form of GreetingWithErrorsOutput 2.
 * An InvalidGreeting error. 3. A BadRequest error.
 */
final class GreetingWithErrorsOperation implements OperationInterface
{
    public const NAME = 'GreetingWithErrors';
    public const ID = 'aws.protocoltests.query#GreetingWithErrors';
    public const SERVICE_ID = 'aws.protocoltests.query#AwsQuery';
    public const INPUT_CLASS = Unit::class;
    public const OUTPUT_CLASS = GreetingWithErrorsOutput::class;
    public const AUTH_SCHEMES = [
        'aws.auth#sigv4'
    ];
    public const ERRORS = [
        'aws.protocoltests.query#ComplexError' => 'aws.protocoltests.query#ComplexError',
        'ComplexError' => 'aws.protocoltests.query#ComplexError',
        'aws.protocoltests.query#CustomCodeError' => 'aws.protocoltests.query#CustomCodeError',
        'CustomCodeError' => 'aws.protocoltests.query#CustomCodeError',
        'Customized' => 'aws.protocoltests.query#CustomCodeError',
        'aws.protocoltests.query#InvalidGreeting' => 'aws.protocoltests.query#InvalidGreeting',
        'InvalidGreeting' => 'aws.protocoltests.query#InvalidGreeting'
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
        return AwsQueryException::class;
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
            id: 'aws.protocoltests.query#GreetingWithErrors',
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
            CustomCodeErrorException::schema(),
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
            'aws.protocoltests.query#ComplexError' => ComplexErrorException::schema(),
            'ComplexError' => ComplexErrorException::schema(),
            'aws.protocoltests.query#CustomCodeError' => CustomCodeErrorException::schema(),
            'CustomCodeError' => CustomCodeErrorException::schema(),
            'Customized' => CustomCodeErrorException::schema(),
            'aws.protocoltests.query#InvalidGreeting' => InvalidGreetingException::schema(),
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
