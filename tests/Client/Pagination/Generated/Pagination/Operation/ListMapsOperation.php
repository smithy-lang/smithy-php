<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Pagination\Generated\Pagination\Operation;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Tests\Client\Pagination\Generated\Pagination\Input\ListMapsInput;
use Smithy\Tests\Client\Pagination\Generated\Pagination\Output\ListMapsOutput;
final class ListMapsOperation implements OperationInterface
{
    public const NAME = 'ListMaps';
    public const ID = 'smithy.pagination#ListMaps';
    public const INPUT_CLASS = ListMapsInput::class;
    public const OUTPUT_CLASS = ListMapsOutput::class;
    public const ERRORS = [];
    public const TARGET = 'Pagination.ListMaps';

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
        return 'smithy.pagination#Pagination';
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
            id: 'smithy.pagination#ListMaps',
            type: ShapeType::Operation,
            traits: [
                Schema::T_PAGINATED => [
                    'inputToken' => 'exclusiveStartKey',
                    'outputToken' => 'lastEvaluatedKey',
                    'items' => 'items',
                    'pageSize' => 'limit'
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
        return ListMapsInput::schema();
    }

    public function getOutputSchema(): Schema
    {
        return self::outputSchema();
    }

    public static function outputSchema(): Schema
    {
        return ListMapsOutput::schema();
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
