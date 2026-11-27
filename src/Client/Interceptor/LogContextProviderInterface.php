<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Client\Interceptor;

/**
 * Add fields to the PSR-3 context of each {@see LoggingInterceptor} record.
 *
 * The interceptor calls the provider once per record with the hook context
 * that produced it: an {@see InputContext} when a call starts, a
 * {@see RequestContext} when an attempt starts, and an {@see OutputContext}
 * when an attempt or call ends. Providers read the context and return the
 * extra fields, such as a service request ID taken from the response.
 *
 * Providers run inside read hooks, so they only read the context. The
 * interceptor's own fields take precedence over provider fields with the
 * same key.
 */
interface LogContextProviderInterface
{
    /**
     * Return extra PSR-3 context fields for one log record.
     *
     * @param \Throwable|null $error The attempt or call error, when the
     *        record describes a failure.
     *
     * @return array<string, mixed>
     */
    public function getLogContext(
        InputContext|RequestContext|OutputContext $ctx,
        ?\Throwable $error
    ): array;
}
