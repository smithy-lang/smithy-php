<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Core\Context;

/**
 * Immutable map of typed values keyed by {@see ContextKey}.
 *
 * {@see with()} returns a copy, so a context can be shared freely.
 */
final class Context
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
     * Return a copy with `$value` stored under `$key`.
     *
     * @template T
     * @param ContextKey<T> $key
     * @param T $value
     */
    public function with(ContextKey $key, mixed $value): self
    {
        $copy = clone $this;
        $copy->values[$key->id] = $value;

        return $copy;
    }
}
