<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Core\Context;

/**
 * Typed key for a value stored in a {@see Context} or {@see MutableContext}.
 *
 * Keys match by identity: two keys created with the same name address
 * different slots. Share one key instance between the code that writes a
 * value and the code that reads it, usually through a static accessor that
 * caches the key:
 *
 * ```php
 * // Annotated `@return ContextKey<int>`.
 * public static function maxItemsKey(): ContextKey
 * {
 *     return self::$maxItemsKey ??= new ContextKey('example.maxItems');
 * }
 * ```
 *
 * @template T
 */
final class ContextKey
{
    private static int $nextId = 0;

    /**
     * Slot identifier, unique to this key for the life of the process.
     */
    public readonly int $id;

    /**
     * @param string $name Name shown when debugging.
     */
    public function __construct(public readonly string $name)
    {
        $this->id = self::$nextId++;
    }
}
