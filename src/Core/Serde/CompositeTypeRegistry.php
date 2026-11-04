<?php

declare(strict_types=1);

namespace Smithy\Core\Serde;

use Smithy\Core\Shape\Document\DocumentInterface;
use Smithy\Core\Shape\Exception\InvalidShapeValueException;

/**
 * Compose registries using ordered, first-match resolution.
 */
final readonly class CompositeTypeRegistry implements TypeRegistryInterface
{
    /** @var list<TypeRegistryInterface> */
    private array $registries;

    public function __construct(TypeRegistryInterface ...$registries)
    {
        $this->registries = $registries;
    }

    public function schemaFor(string $shapeId): ?Schema
    {
        foreach ($this->registries as $registry) {
            $schema = $registry->schemaFor($shapeId);
            if ($schema !== null) {
                return $schema;
            }
        }

        return null;
    }

    public function classFor(string $shapeId): ?string
    {
        foreach ($this->registries as $registry) {
            if ($registry->schemaFor($shapeId) !== null) {
                return $registry->classFor($shapeId);
            }
        }

        return null;
    }

    public function create(
        string $shapeId,
        DocumentInterface $value
    ): ?SerializableShapeInterface
    {
        foreach ($this->registries as $registry) {
            if ($registry->schemaFor($shapeId) !== null) {
                return $registry->create($shapeId, $value);
            }
        }

        return null;
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
}
