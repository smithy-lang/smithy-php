<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\Ec2Query\Operation;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\Ec2Query\Exception\ComplexErrorException;
use Smithy\ProtocolTests\Ec2Query\Exception\Ec2QueryException;
use Smithy\ProtocolTests\Ec2Query\Exception\InvalidGreetingException;
use Smithy\ProtocolTests\Ec2Query\Output\GreetingWithErrorsOutput;
use Smithy\ProtocolTests\Ec2Query\Shape\Unit;

/**
 * This operation has three possible return values: 1. A successful response in the form of GreetingWithErrorsOutput 2.
 * An InvalidGreeting error. 3. A BadRequest error.
 */
final class GreetingWithErrorsOperation implements OperationInterface
{
    public const NAME = 'GreetingWithErrors';
    public const ID = 'aws.protocoltests.ec2#GreetingWithErrors';
    public const SERVICE_ID = 'aws.protocoltests.ec2#AwsEc2';
    public const INPUT_CLASS = Unit::class;
    public const OUTPUT_CLASS = GreetingWithErrorsOutput::class;
    public const AUTH_SCHEMES = [
        'aws.auth#sigv4'
    ];
    public const ERRORS = [
        'aws.protocoltests.ec2#ComplexError' => 'aws.protocoltests.ec2#ComplexError',
        'ComplexError' => 'aws.protocoltests.ec2#ComplexError',
        'aws.protocoltests.ec2#InvalidGreeting' => 'aws.protocoltests.ec2#InvalidGreeting',
        'InvalidGreeting' => 'aws.protocoltests.ec2#InvalidGreeting'
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
        return Ec2QueryException::class;
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
            id: 'aws.protocoltests.ec2#GreetingWithErrors',
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
            'aws.protocoltests.ec2#ComplexError' => ComplexErrorException::schema(),
            'ComplexError' => ComplexErrorException::schema(),
            'aws.protocoltests.ec2#InvalidGreeting' => InvalidGreetingException::schema(),
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
