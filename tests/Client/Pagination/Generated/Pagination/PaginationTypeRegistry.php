<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Pagination\Generated\Pagination;

/**
 * Shape-ID <-> generated-class registry for the Pagination service.
 *
 * Populated once at codegen time from the service closure. Callers
 * use `schemaFor` for lazy schema resolution, `classFor` for a raw
 * class-string when they need to reflect on the generated type, and
 * `shapeIdFor` for the inverse lookup keyed by class.
 */
final class PaginationTypeRegistry
{
    /**
     * @var array<string, class-string>
     */
    private const CLASSES_BY_SHAPE_ID = [
        'smithy.pagination#ListMapsInput' => 'Smithy\\Tests\\Client\\Pagination\\Generated\\Pagination\\Input\\ListMapsInput',
        'smithy.pagination#ListMapsOutput' => 'Smithy\\Tests\\Client\\Pagination\\Generated\\Pagination\\Output\\ListMapsOutput',
        'smithy.pagination#ListStringsInput' => 'Smithy\\Tests\\Client\\Pagination\\Generated\\Pagination\\Input\\ListStringsInput',
        'smithy.pagination#ListStringsOutput' => 'Smithy\\Tests\\Client\\Pagination\\Generated\\Pagination\\Output\\ListStringsOutput',
        'smithy.pagination#ListWithoutPageSizeInput' => 'Smithy\\Tests\\Client\\Pagination\\Generated\\Pagination\\Input\\ListWithoutPageSizeInput',
        'smithy.pagination#ListWithoutPageSizeOutput' => 'Smithy\\Tests\\Client\\Pagination\\Generated\\Pagination\\Output\\ListWithoutPageSizeOutput'
    ];

    /**
     * @var array<class-string, string>
     */
    private const SHAPE_IDS_BY_CLASS = [
        'Smithy\\Tests\\Client\\Pagination\\Generated\\Pagination\\Input\\ListMapsInput' => 'smithy.pagination#ListMapsInput',
        'Smithy\\Tests\\Client\\Pagination\\Generated\\Pagination\\Output\\ListMapsOutput' => 'smithy.pagination#ListMapsOutput',
        'Smithy\\Tests\\Client\\Pagination\\Generated\\Pagination\\Input\\ListStringsInput' => 'smithy.pagination#ListStringsInput',
        'Smithy\\Tests\\Client\\Pagination\\Generated\\Pagination\\Output\\ListStringsOutput' => 'smithy.pagination#ListStringsOutput',
        'Smithy\\Tests\\Client\\Pagination\\Generated\\Pagination\\Input\\ListWithoutPageSizeInput' => 'smithy.pagination#ListWithoutPageSizeInput',
        'Smithy\\Tests\\Client\\Pagination\\Generated\\Pagination\\Output\\ListWithoutPageSizeOutput' => 'smithy.pagination#ListWithoutPageSizeOutput'
    ];

    /**
     * Return the PHP class-string generated for a given Smithy shape ID,
     * or null when the ID is unknown to this service.
     *
     * @return class-string|null
     */
    public static function classFor(string $shapeId): ?string
    {
        return self::CLASSES_BY_SHAPE_ID[$shapeId] ?? null;
    }

    /**
     * Inverse of `classFor`. Returns the Smithy shape ID for a generated
     * class, or null when the class was not produced by this codegen run.
     *
     * @param class-string $class
     */
    public static function shapeIdFor(string $class): ?string
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
    public static function schemaFor(string $shapeId): ?\Smithy\Core\Serde\Schema
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
    public static function all(): array
    {
        return self::CLASSES_BY_SHAPE_ID;
    }
}
