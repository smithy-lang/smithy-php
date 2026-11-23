<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Core\Context;

/**
 * Mutable map of typed values keyed by {@see ContextKey}.
 *
 * The client creates one for each operation call and hands the same
 * instance to every interceptor hook of that call.
 */
final class MutableContext
{
    /** @var array<int, mixed> Values by {@see ContextKey::$id}. */
    private array $values = [];

    /**
     * Return the value stored under `$key`, or null when it is absent.
     *
     * @template T
     * @param ContextKey<T> $key
     * @return T|null
     */
    public function get(ContextKey $key): mixed
    {
        return $this->values[$key->id] ?? null;
    }

    /**
     * Return true when a value, including null, is stored under `$key`.
     *
     * @param ContextKey<mixed> $key
     */
    public function has(ContextKey $key): bool
    {
        return \array_key_exists($key->id, $this->values);
    }

    /**
     * Store `$value` under `$key`, replacing any earlier value.
     *
     * @template T
     * @param ContextKey<T> $key
     * @param T $value
     * @return void
     */
    public function set(ContextKey $key, mixed $value): void
    {
        $this->values[$key->id] = $value;
    }
}
