<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Utils;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Core\Shape\ShapeType;

final class IdempotentTestOperationFixture implements OperationInterface
{
    private static ?self $instance = null;
    private static ?Schema $schema = null;

    private function __construct() {}

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function getName(): string
    {
        return 'IdempotentTest';
    }

    public function getId(): string
    {
        return 'smithy.tests#IdempotentTest';
    }

    public function getRuntimeIdentity(): string
    {
        return self::class;
    }

    public function getInputClass(): string
    {
        return IdempotentTestInputFixture::class;
    }

    public function getOutputClass(): string
    {
        return IdempotentTestInputFixture::class;
    }

    public function getErrors(): array
    {
        return [];
    }

    public function getErrorSchemas(): array
    {
        return [];
    }

    public function createInput(array $members): SerializableStructInterface
    {
        $inputClass = $this->getInputClass();

        return new $inputClass(...$members);
    }

    public function createOutput(array $members): SerializableStructInterface
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
        return self::$schema ??= new Schema(
            id: 'smithy.tests#IdempotentTest',
            type: ShapeType::Operation
        );
    }

    public function getInputSchema(): Schema
    {
        return self::inputSchema();
    }

    public static function inputSchema(): Schema
    {
        return IdempotentTestInputFixture::schema();
    }

    public function getOutputSchema(): Schema
    {
        return self::outputSchema();
    }

    public static function outputSchema(): Schema
    {
        return IdempotentTestInputFixture::schema();
    }

    public function getRequestTarget(): ?string
    {
        return \defined(static::class . '::TARGET')
            ? \constant(static::class . '::TARGET')
            : null;
    }

    public function resolveErrorSchema(string $discriminator): ?Schema
    {
        $errorMap = $this->getSchema()->getTrait(Schema::T_ERRORS) ?? [];
        $errorClass = $errorMap[$discriminator] ?? null;

        return \is_string($errorClass)
            && \method_exists($errorClass, 'schema')
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
