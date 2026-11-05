<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Fake;

use Smithy\Client\Interceptor\{
    AbstractInterceptor,
    InputContext,
    OutputContext,
    RequestContext,
    ResponseContext
};
use Smithy\Client\Transport\{
    TransportRequestInterface,
    TransportResponseInterface
};
use Smithy\Core\Serde\SerializableStructInterface;

/**
 * Records every hook the pipeline invokes on this interceptor, in order.
 *
 * Used by pipeline tests to assert the hook firing sequence and to
 * verify that hooks see the expected context state.
 */
final class RecordingInterceptor extends AbstractInterceptor
{
    /**
     * Hook names in the order they were called on this instance.
     *
     * @var list<string>
     */
    public array $calls = [];

    /**
     * Create a recording interceptor.
     */
    public function __construct(
        public readonly string $tag = ''
    ) {}

    private function record(string $method): void
    {
        $this->calls[] = $this->tag === '' ? $method : "{$this->tag}:{$method}";
    }

    public function readBeforeExecution(InputContext $ctx): void
    {
        $this->record('readBeforeExecution');
    }

    public function modifyBeforeSerialization(
        InputContext $ctx
    ): SerializableStructInterface
    {
        $this->record('modifyBeforeSerialization');

        return $ctx->input;
    }

    public function readBeforeSerialization(InputContext $ctx): void
    {
        $this->record('readBeforeSerialization');
    }

    public function readAfterSerialization(RequestContext $ctx): void
    {
        $this->record('readAfterSerialization');
    }

    public function modifyBeforeRetryLoop(
        RequestContext $ctx
    ): TransportRequestInterface
    {
        $this->record('modifyBeforeRetryLoop');

        return $ctx->request;
    }

    public function readBeforeAttempt(RequestContext $ctx): void
    {
        $this->record('readBeforeAttempt');
    }

    public function modifyBeforeSigning(
        RequestContext $ctx
    ): TransportRequestInterface
    {
        $this->record('modifyBeforeSigning');

        return $ctx->request;
    }

    public function readBeforeSigning(RequestContext $ctx): void
    {
        $this->record('readBeforeSigning');
    }

    public function readAfterSigning(RequestContext $ctx): void
    {
        $this->record('readAfterSigning');
    }

    public function modifyBeforeTransmit(
        RequestContext $ctx
    ): TransportRequestInterface
    {
        $this->record('modifyBeforeTransmit');

        return $ctx->request;
    }

    public function readBeforeTransmit(RequestContext $ctx): void
    {
        $this->record('readBeforeTransmit');
    }

    public function readAfterTransmit(ResponseContext $ctx): void
    {
        $this->record('readAfterTransmit');
    }

    public function modifyBeforeDeserialization(
        ResponseContext $ctx
    ): TransportResponseInterface
    {
        $this->record('modifyBeforeDeserialization');

        return $ctx->response;
    }

    public function readBeforeDeserialization(ResponseContext $ctx): void
    {
        $this->record('readBeforeDeserialization');
    }

    public function readAfterDeserialization(
        OutputContext $ctx,
        ?\Throwable $error
    ): void
    {
        $this->record('readAfterDeserialization');
    }

    public function modifyBeforeAttemptCompletion(
        OutputContext $ctx,
        ?\Throwable $error
    ): SerializableStructInterface
    {
        $this->record('modifyBeforeAttemptCompletion');

        return parent::modifyBeforeAttemptCompletion($ctx, $error);
    }

    public function readAfterAttempt(
        OutputContext $ctx,
        ?\Throwable $error
    ): void
    {
        $this->record('readAfterAttempt');
    }

    public function modifyBeforeCompletion(
        OutputContext $ctx,
        ?\Throwable $error
    ): SerializableStructInterface
    {
        $this->record('modifyBeforeCompletion');

        return parent::modifyBeforeCompletion($ctx, $error);
    }

    public function readAfterExecution(
        OutputContext $ctx,
        ?\Throwable $error
    ): void
    {
        $this->record('readAfterExecution');
    }
}
