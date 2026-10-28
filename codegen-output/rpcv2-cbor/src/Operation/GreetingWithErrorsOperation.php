<?php

declare(strict_types=1);

namespace Smithy\ProtocolTests\RpcV2Cbor\Operation;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RpcV2Cbor\Exception\ComplexErrorException;
use Smithy\ProtocolTests\RpcV2Cbor\Exception\InvalidGreetingException;
use Smithy\ProtocolTests\RpcV2Cbor\Exception\RpcV2CborException;
use Smithy\ProtocolTests\RpcV2Cbor\Output\GreetingWithErrorsOutput;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\Unit;

/**
 * This operation has three possible return values: 1. A successful response in the form of GreetingWithErrorsOutput 2.
 * An InvalidGreeting error. 3. A ComplexError error. Implementations must be able to successfully take a response and
 * properly deserialize successful and error responses.
 */
final class GreetingWithErrorsOperation implements OperationInterface
{
    public const NAME = 'GreetingWithErrors';
    public const ID = 'smithy.protocoltests.rpcv2Cbor#GreetingWithErrors';
    public const SERVICE_ID = 'smithy.protocoltests.rpcv2Cbor#RpcV2Protocol';
    public const INPUT_CLASS = Unit::class;
    public const OUTPUT_CLASS = GreetingWithErrorsOutput::class;
    public const AUTH_SCHEMES = [
        'smithy.api#noAuth'
    ];
    public const ERRORS = [
        'smithy.protocoltests.rpcv2Cbor#ComplexError' => 'smithy.protocoltests.rpcv2Cbor#ComplexError',
        'ComplexError' => 'smithy.protocoltests.rpcv2Cbor#ComplexError',
        'smithy.protocoltests.rpcv2Cbor#InvalidGreeting' => 'smithy.protocoltests.rpcv2Cbor#InvalidGreeting',
        'InvalidGreeting' => 'smithy.protocoltests.rpcv2Cbor#InvalidGreeting'
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
            id: 'smithy.protocoltests.rpcv2Cbor#GreetingWithErrors',
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
            'smithy.protocoltests.rpcv2Cbor#ComplexError' => ComplexErrorException::schema(),
            'ComplexError' => ComplexErrorException::schema(),
            'smithy.protocoltests.rpcv2Cbor#InvalidGreeting' => InvalidGreetingException::schema(),
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
