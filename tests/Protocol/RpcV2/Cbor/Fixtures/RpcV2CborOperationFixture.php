<?php

declare(strict_types=1);

namespace Smithy\Tests\Protocol\RpcV2\Cbor\Fixtures;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\Document\StructDocument;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Core\Shape\ShapeType;

final class RpcV2CborOperationFixture implements OperationInterface
{
    /**
     * @param array<string, Schema> $errorSchemas
     */
    public function __construct(
        private readonly Schema $inputSchema,
        private readonly Schema $outputSchema,
        private readonly array $errorSchemas = [],
        private readonly ?string $requestTarget = null
    ) {}

    public function getName(): string
    {
        return 'GetThing';
    }

    public function getId(): string
    {
        return 'smithy.example#GetThing';
    }

    public function getServiceId(): ?string
    {
        return 'smithy.example#ExampleService';
    }

    public function getRuntimeIdentity(): string
    {
        return self::class . '#' . \spl_object_id($this);
    }

    /**
     * @return list<string>
     */
    public function getEffectiveAuthSchemes(): array
    {
        return [];
    }

    public function getSchema(): Schema
    {
        return new Schema(
            id: $this->getId(),
            type: ShapeType::Operation
        );
    }

    public function getInputSchema(): Schema
    {
        return $this->inputSchema;
    }

    public function getOutputSchema(): Schema
    {
        return $this->outputSchema;
    }

    /**
     * @return list<Schema>
     */
    public function getErrorSchemas(): array
    {
        return \array_values($this->errorSchemas);
    }

    public function createInput(array $members): SerializableStructInterface
    {
        return StructDocument::fromMembers($this->inputSchema, $members);
    }

    public function createOutput(array $members): SerializableStructInterface
    {
        return StructDocument::fromMembers($this->outputSchema, $members);
    }

    public function getRequestTarget(): ?string
    {
        return $this->requestTarget;
    }

    public function resolveErrorSchema(string $discriminator): ?Schema
    {
        return $this->errorSchemas[$discriminator] ?? null;
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
