<?php

declare(strict_types=1);

namespace Smithy\Core\Serde;

use Smithy\Core\Model\SmithyModel;
use Smithy\Core\Shape\Document\DocumentInterface;
use Smithy\Core\Shape\Exception\InvalidShapeValueException;

/**
 * Lazily resolve runtime model shape IDs to schemas.
 */
final class TypeRegistry implements TypeRegistryInterface
{
    /** @var array<string, Schema> */
    private array $schemas = [];

    /** @var array<string, Schema> */
    private array $schemasById = [];

    /**
     * Create a registry backed by a runtime model converter.
     */
    public function __construct(
        private readonly SmithyModel $model,
        private readonly SchemaConverter $converter
    ) {}

    /**
     * Resolve a modeled shape ID, or return null when it is unknown.
     */
    public function schemaFor(string $shapeId): ?Schema
    {
        if ($this->model->getShape($shapeId) === null) {
            return null;
        }
        if (isset($this->schemas[$shapeId])) {
            return $this->schemas[$shapeId];
        }

        $canonicalId = $this->model->getOriginalShapeId($shapeId);
        $conversionId = (
            $canonicalId !== $shapeId
            && $this->model->getShape($canonicalId) !== null
        )
            ? $canonicalId
            : $shapeId;

        if ($conversionId === $canonicalId
            && isset($this->schemas[$canonicalId])
        ) {
            return $this->schemas[$shapeId] = $this->schemas[$canonicalId];
        }
        if (isset($this->schemas[$conversionId])) {
            return $this->schemas[$shapeId] = $this->schemas[$conversionId];
        }

        $schema = $this->converter->getSchema($conversionId);
        $this->schemas[$shapeId] = $schema;
        $this->schemas[$schema->id] ??= $schema;
        $this->schemasById[$conversionId] ??= $schema;

        return $schema;
    }

    /**
     * Resolve the PHP type projected by a runtime schema, when one exists.
     *
     * @return class-string<SerializableShapeInterface>|null
     */
    public function classFor(string $shapeId): ?string
    {
        return $this->schemaFor($shapeId)?->targetClass;
    }

    public function create(
        string $shapeId,
        DocumentInterface $value
    ): ?SerializableShapeInterface
    {
        $schema = $this->schemaFor($shapeId);
        if ($schema === null) {
            return null;
        }

        return ShapeFactory::create(
            $schema,
            $this->classFor($shapeId),
            $value
        );
    }

    public function deserialize(
        DocumentInterface $value
    ): SerializableShapeInterface
    {
        $shapeId = $value->discriminator();
        if ($shapeId === null) {
            throw new InvalidShapeValueException(
                'Cannot deserialize a document without a type discriminator'
            );
        }

        $shape = $this->create($shapeId, $value);
        if ($shape === null) {
            throw new InvalidShapeValueException(
                "No type is registered for discriminator {$shapeId}"
            );
        }

        return $shape;
    }

    /**
     * Return the schemas resolved by this registry.
     *
     * @return array<string, Schema> Canonical shape ID to schema.
     */
    public function all(): array
    {
        return $this->schemasById;
    }
}
