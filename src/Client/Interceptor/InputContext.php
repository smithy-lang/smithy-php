<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Client\Interceptor;

use Smithy\Client\AbstractClientConfig;
use Smithy\Client\ClientInterface;
use Smithy\Core\Context\MutableContext;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\OperationInterface;

/**
 * Hook data available before the input has been serialized.
 *
 * Supplied to `interceptCall`, `readBeforeExecution`,
 * `modifyBeforeSerialization`, and `readBeforeSerialization`. The `client`
 * property is populated for `interceptCall` so wrappers can re-enter the
 * client when needed.
 *
 * `attributes` is the per-call {@see MutableContext}. Every hook of the
 * same operation call receives the same instance; each call gets a new one.
 */
final class InputContext
{
    /**
     * Create input hook context.
     */
    public function __construct(
        public OperationInterface $operation,
        public SerializableStructInterface $input,
        public AbstractClientConfig $config,
        public MutableContext $attributes,
        public ?ClientInterface $client = null
    ) {}

    /**
     * Replace the mutable input value in this context.
     *
     * @return void
     */
    public function replaceInput(SerializableStructInterface $input): void
    {
        $this->input = $input;
    }

    /**
     * Return a new context with `input` replaced. Used by
     * `modifyBeforeSerialization` interceptor chaining.
     */
    public function withInput(SerializableStructInterface $input): self
    {
        return $input === $this->input
            ? $this
            : new self(
                $this->operation,
                $input,
                $this->config,
                $this->attributes,
                $this->client
            );
    }
}
