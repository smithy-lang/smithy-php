<?php

declare(strict_types=1);

namespace Smithy\CodegenOutput\TrimmedPetStore;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableShapeInterface;
use Smithy\Core\Serde\ShapeFactory;
use Smithy\Core\Serde\TypeRegistryInterface;
use Smithy\Core\Shape\Document\DocumentInterface;
use Smithy\Core\Shape\Exception\InvalidShapeValueException;

/**
 * Shape-ID registry for the TrimmedPetStore service.
 *
 * Populated once at codegen time from the service closure. Callers
 * use `schemaFor` for lazy schema resolution, `classFor` for a raw
 * class-string, `shapeIdFor` for inverse lookup, and `create` or
 * `deserialize` to materialize generated types from documents.
 */
final class TrimmedPetStoreTypeRegistry implements TypeRegistryInterface
{
    /**
     * @var array<string, class-string>
     */
    private const CLASSES_BY_SHAPE_ID = [
        'smithy.example#CommonError' => 'Smithy\\CodegenOutput\\TrimmedPetStore\\Exception\\CommonErrorException',
        'smithy.example#GetPetInput' => 'Smithy\\CodegenOutput\\TrimmedPetStore\\Input\\GetPetInput',
        'smithy.example#GetPetOutput' => 'Smithy\\CodegenOutput\\TrimmedPetStore\\Output\\GetPetOutput',
        'smithy.example#Pet' => 'Smithy\\CodegenOutput\\TrimmedPetStore\\Shape\\Pet',
        'smithy.example#PetDetails' => 'Smithy\\CodegenOutput\\TrimmedPetStore\\Shape\\PetDetails',
        'smithy.example#ServiceError' => 'Smithy\\CodegenOutput\\TrimmedPetStore\\Exception\\ServiceErrorException',
        'smithy.example#UnitUnion' => 'Smithy\\CodegenOutput\\TrimmedPetStore\\Shape\\UnitUnion'
    ];

    /**
     * @var array<class-string, string>
     */
    private const SHAPE_IDS_BY_CLASS = [
        'Smithy\\CodegenOutput\\TrimmedPetStore\\Exception\\CommonErrorException' => 'smithy.example#CommonError',
        'Smithy\\CodegenOutput\\TrimmedPetStore\\Input\\GetPetInput' => 'smithy.example#GetPetInput',
        'Smithy\\CodegenOutput\\TrimmedPetStore\\Output\\GetPetOutput' => 'smithy.example#GetPetOutput',
        'Smithy\\CodegenOutput\\TrimmedPetStore\\Shape\\Pet' => 'smithy.example#Pet',
        'Smithy\\CodegenOutput\\TrimmedPetStore\\Shape\\PetDetails' => 'smithy.example#PetDetails',
        'Smithy\\CodegenOutput\\TrimmedPetStore\\Exception\\ServiceErrorException' => 'smithy.example#ServiceError',
        'Smithy\\CodegenOutput\\TrimmedPetStore\\Shape\\UnitUnion' => 'smithy.example#UnitUnion'
    ];

    /**
     * Return the generated PHP class-string for a Smithy shape ID,
     * or null when the ID is unknown to this service.
     *
     * @return class-string|null
     */
    public function classFor(string $shapeId): ?string
    {
        return self::CLASSES_BY_SHAPE_ID[$shapeId] ?? null;
    }

    /**
     * Return the Smithy shape ID for a generated class.
     * Returns null when this codegen run did not produce the class.
     *
     * @param class-string $class
     */
    public function shapeIdFor(string $class): ?string
    {
        return self::SHAPE_IDS_BY_CLASS[$class] ?? null;
    }

    /**
     * Resolve a shape ID to its `Schema` object. Uses the generated
     * class's lazy `schema()` static, so schemas are only built the
     * first time they are accessed.
     *
     * Returns null when the shape ID is not registered.
     */
    public function schemaFor(string $shapeId): ?Schema
    {
        $class = self::CLASSES_BY_SHAPE_ID[$shapeId] ?? null;
        if ($class === null) {
            return null;
        }

        return $class::schema();
    }

    /**
     * Create a registered generated type from a document value.
     *
     * Returns null when the shape ID is not registered.
     */
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

    /**
     * Create a registered generated type using the document's
     * shape-ID discriminator.
     */
    public function deserialize(DocumentInterface $value): SerializableShapeInterface
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
     * Iterate every registered (shape ID, class-string) pair. Useful
     * for building higher-level indexes on top of the registry (a
     * category-scoped subset, a namespace filter, etc.).
     *
     * @return array<string, class-string>
     */
    public function all(): array
    {
        return self::CLASSES_BY_SHAPE_ID;
    }
}
