<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Client\Interceptor;

use Smithy\Core\Exception\ApiException;
use Smithy\Core\Retry\RetryInfoInterface;
use Smithy\Core\Retry\RetrySafety;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\OperationInterface;

/**
 * Treat an ambiguous failure of a `@readonly` or `@idempotent` operation as
 * safe to retry.
 *
 * Matches smithy-java's `ApplyModelRetryInfoPlugin`. Runs in
 * `modifyBeforeAttemptCompletion`, which the pipeline invokes before the
 * retry strategy classifies the attempt's error. The first error in the
 * previous-chain that carries retry info decides, exactly as
 * {@see \Smithy\Client\Retry\StandardRetryStrategy} reads it. When that error
 * is an {@see ApiException} classified {@see RetrySafety::Maybe}, it is
 * promoted to {@see RetrySafety::Yes}. A `No` or `Yes` is never changed.
 */
final class RetrySafetyInterceptor extends AbstractInterceptor
{
    /** @inheritDoc */
    public function appliesTo(OperationInterface $operation): bool
    {
        return self::isRetrySafe($operation);
    }

    /**
     * @inheritDoc
     *
     * @throws \Throwable The attempt error, possibly promoted.
     */
    public function modifyBeforeAttemptCompletion(
        OutputContext $ctx,
        ?\Throwable $error
    ): SerializableStructInterface
    {
        if ($error !== null && self::isRetrySafe($ctx->operation)) {
            for ($e = $error; $e !== null; $e = $e->getPrevious()) {
                if ($e instanceof RetryInfoInterface) {
                    if ($e instanceof ApiException) {
                        $e->promoteMaybeRetrySafety();
                    }
                    break;
                }
            }
        }

        return parent::modifyBeforeAttemptCompletion($ctx, $error);
    }

    private static function isRetrySafe(OperationInterface $operation): bool
    {
        $schema = $operation->getSchema();

        return $schema->hasTrait(Schema::T_READ_ONLY)
            || $schema->hasTrait(Schema::T_IDEMPOTENT);
    }
}
