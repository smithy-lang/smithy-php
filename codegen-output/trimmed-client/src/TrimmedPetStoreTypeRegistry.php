<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\CodegenOutput\TrimmedPetStore;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\TypeRegistryDeserializeTrait;
use Smithy\Core\Serde\TypeRegistryInterface;

/**
 * Shape-ID registry for the TrimmedPetStore service.
 *
 * Populated once at codegen time from the service closure. Callers
 * use `schemaFor` for lazy schema resolution, `classFor` for the
 * class name when they need to reflect on the generated type, and
 * `shapeIdFor` for the inverse lookup keyed by class. `deserialize`
 * builds the registered shape that a document's discriminator names.
 */
final class TrimmedPetStoreTypeRegistry implements TypeRegistryInterface
{
    use TypeRegistryDeserializeTrait;

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
     * Return the generated class name for a Smithy shape ID,
     * or null when the ID is unknown to this service.
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
        return \method_exists($class, 'schema') ? $class::schema() : null;
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
