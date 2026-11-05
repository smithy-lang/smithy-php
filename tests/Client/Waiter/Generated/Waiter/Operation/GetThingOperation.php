<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Waiter\Generated\Waiter\Operation;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Tests\Client\Waiter\Generated\Waiter\Input\GetThingInput;
use Smithy\Tests\Client\Waiter\Generated\Waiter\Output\GetThingOutput;
final class GetThingOperation implements OperationInterface
{
    public const NAME = 'GetThing';
    public const ID = 'smithy.waiter#GetThing';
    public const INPUT_CLASS = GetThingInput::class;
    public const OUTPUT_CLASS = GetThingOutput::class;
    public const ERRORS = [];
    public const TARGET = 'Waiter.GetThing';

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
        return 'smithy.waiters#Waiter';
    }

    public function getServiceExceptionClass(): string
    {
        return \Smithy\Core\Exception\ApiException::class;
    }

    public function getRuntimeIdentity(): string
    {
        return self::class;
    }

    public function getInputClass(): string
    {
        return self::INPUT_CLASS;
    }

    public function getOutputClass(): string
    {
        return self::OUTPUT_CLASS;
    }

    public function getErrors(): array
    {
        return self::ERRORS;
    }

    /**
     * Get the shape schema.
     */

    public function getErrorSchemas(): array
    {
        return [];
    }

    public function createInput(array $members): \Smithy\Core\Serde\SerializableStructInterface
    {
        $inputClass = $this->getInputClass();

        return new $inputClass(...$members);
    }

    public function createOutput(array $members): \Smithy\Core\Serde\SerializableStructInterface
    {
        $outputClass = $this->getOutputClass();

        return $outputClass::fromMembers($members);
    }

    public function getEffectiveAuthSchemes(): array
    {
        return ['smithy.api#noAuth'];
    }

    public function getSchema(): Schema
    {
        return self::schema();
    }

    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: 'smithy.waiter#GetThing',
            type: ShapeType::Operation,
            traits: [
                Schema::T_WAITABLE => [
                    'ThingReady' => [
                        'documentation' => 'Wait until the thing is ready.',
                        'minDelay' => 1,
                        'maxDelay' => 2,
                        'acceptors' => [
                            [
                                'state' => 'failure',
                                'matcher' => [
                                    'output' => [
                                        'path' => 'status',
                                        'expected' => 'failed',
                                        'comparator' => 'stringEquals'
                                    ]
                                ],
                                'message' => 'failureReason'
                            ],
                            [
                                'state' => 'success',
                                'matcher' => [
                                    'output' => [
                                        'path' => 'status',
                                        'expected' => 'ready',
                                        'comparator' => 'stringEquals'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );
    }

    public function getInputSchema(): Schema
    {
        return self::inputSchema();
    }

    public static function inputSchema(): Schema
    {
        return GetThingInput::schema();
    }

    public function getOutputSchema(): Schema
    {
        return self::outputSchema();
    }

    public static function outputSchema(): Schema
    {
        return GetThingOutput::schema();
    }

    public function getRequestTarget(): ?string
    {
        return \defined(static::class . "::TARGET")
            ? \constant(static::class . "::TARGET")
            : null;
    }

    public function resolveErrorSchema(string $discriminator): ?Schema
    {
        $errorMap = $this->getSchema()->getTrait(Schema::T_ERRORS) ?? [];
        $errorClass = $errorMap[$discriminator] ?? null;

        return \is_string($errorClass)
            && \method_exists($errorClass, "schema")
                ? $errorClass::schema()
                : null;
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
