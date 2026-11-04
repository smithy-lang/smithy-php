<?php

declare(strict_types=1);

namespace Smithy\Core\Serde;

use Smithy\Core\Shape\Document\DocumentInterface;

/**
 * Resolve and create Smithy types by shape ID.
 *
 * Registries return null from lookup and creation methods when a shape ID is
 * unknown. Discriminator-based deserialization is strict and throws when the
 * document has no discriminator or the discriminator is not registered.
 */
interface TypeRegistryInterface
{
    /**
     * Resolve a modeled shape ID.
     */
    public function schemaFor(string $shapeId): ?Schema;

    /**
     * Resolve the generated PHP type for a shape ID, when one exists.
     *
     * @return class-string<SerializableShapeInterface>|null
     */
    public function classFor(string $shapeId): ?string;

    /**
     * Create a registered type from a document value.
     *
     * @return SerializableShapeInterface|null Null when the shape ID is not
     *                                         registered.
     */
    public function create(
        string $shapeId,
        DocumentInterface $value
    ): ?SerializableShapeInterface;

    /**
     * Create a registered type using the document's shape-ID discriminator.
     */
    public function deserialize(
        DocumentInterface $value
    ): SerializableShapeInterface;
}
