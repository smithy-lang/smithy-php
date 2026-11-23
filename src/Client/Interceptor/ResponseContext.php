<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Client\Interceptor;

use Smithy\Client\AbstractClientConfig;
use Smithy\Core\Transport\{
    TransportRequestInterface,
    TransportResponseInterface
};
use Smithy\Core\Context\MutableContext;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\OperationInterface;

/**
 * Hook data available after the response has arrived but before it has
 * been deserialized into an output shape.
 *
 * Supplied to `readAfterTransmit`, `modifyBeforeDeserialization`, and
 * `readBeforeDeserialization`.
 *
 * `attributes` is the per-call {@see MutableContext}. Every hook of the
 * same operation call receives the same instance; each call gets a new one.
 */
final class ResponseContext
{
    /**
     * Create response hook context.
     */
    public function __construct(
        public OperationInterface $operation,
        public SerializableStructInterface $input,
        public AbstractClientConfig $config,
        public MutableContext $attributes,
        public TransportRequestInterface $request,
        public TransportResponseInterface $response
    ) {}

    /**
     * Replace the mutable response value in this context.
     *
     * @return void
     */
    public function replaceResponse(TransportResponseInterface $response): void
    {
        $this->response = $response;
    }

    /**
     * Return a new context with `response` replaced.
     */
    public function withResponse(TransportResponseInterface $response): self
    {
        return $response === $this->response
            ? $this
            : new self(
                $this->operation,
                $this->input,
                $this->config,
                $this->attributes,
                $this->request,
                $response
            );
    }
}
