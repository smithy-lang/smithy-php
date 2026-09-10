<?php

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Generator\Utils;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Core\Shape\ShapeType;

final class GetThingOperation implements OperationInterface
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

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
        return null;
    }

    public function getRuntimeIdentity(): string
    {
        return self::class;
    }

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
        throw new \LogicException('Not needed by this test.');
    }

    public function getOutputSchema(): Schema
    {
        throw new \LogicException('Not needed by this test.');
    }

    public function getErrorSchemas(): array
    {
        return [];
    }

    public function createInput(array $members): SerializableStructInterface
    {
        throw new \LogicException('Not needed by this test.');
    }

    public function createOutput(array $members): SerializableStructInterface
    {
        throw new \LogicException('Not needed by this test.');
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
