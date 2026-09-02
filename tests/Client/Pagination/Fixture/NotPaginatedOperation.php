<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Pagination\Fixture;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\OperationInterface;
final class NotPaginatedOperation implements OperationInterface
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct() {}

    public function getName(): string
    {
        return 'NotPaginated';
    }

    public function getId(): string
    {
        return 'smithy.tests#NotPaginated';
    }

    public function getRuntimeIdentity(): string
    {
        return self::class;
    }

    public function getInputClass(): string
    {
        return PaginationInput::class;
    }

    public function getOutputClass(): string
    {
        return PaginationOutput::class;
    }

    public function getErrors(): array
    {
        return [];
    }

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
        return new Schema(
            id: 'smithy.tests#NotPaginated',
            type: ShapeType::Operation
        );
    }

    public function getInputSchema(): Schema
    {
        return self::inputSchema();
    }

    public static function inputSchema(): Schema
    {
        return PaginationInput::schema();
    }

    public function getOutputSchema(): Schema
    {
        return self::outputSchema();
    }

    public static function outputSchema(): Schema
    {
        return PaginationOutput::schema();
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
