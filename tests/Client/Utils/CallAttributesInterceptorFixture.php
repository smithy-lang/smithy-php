<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Utils;

use Smithy\Async\Async;
use Smithy\Client\Interceptor\{
    AbstractInterceptor,
    CallContext,
    InputContext,
    OutputContext,
    RequestContext,
    ResponseContext
};
use Smithy\Client\AbstractClientConfig;
use Smithy\Core\Context\ContextKey;
use Smithy\Tests\Client\Fake\FakeInput;

/**
 * Stores the call input in the per-call attributes and records what
 * each later hook reads back.
 *
 * `readBeforeTransmit` yields to the event loop so two calls run at the
 * same time interleave their hooks.
 */
final class CallAttributesInterceptorFixture extends AbstractInterceptor
{
    /** @var ContextKey<string> */
    public readonly ContextKey $key;

    /** @var list<string> */
    public array $records = [];

    public function __construct(
        private readonly bool $setInModifyBeforeCall = false
    ) {
        $this->key = new ContextKey('test.callValue');
    }

    public function modifyBeforeCall(CallContext $ctx): AbstractClientConfig
    {
        if ($this->setInModifyBeforeCall) {
            $ctx->attributes->set($this->key, 'call:' . $this->value($ctx));
        }

        return $ctx->config;
    }

    public function readBeforeExecution(InputContext $ctx): void
    {
        $value = $this->value($ctx);
        $this->records[] = "before:{$value}:"
            . ($ctx->attributes->get($this->key) ?? 'none');
        if (!$this->setInModifyBeforeCall) {
            $ctx->attributes->set($this->key, $value);
        }
    }

    public function readAfterSerialization(RequestContext $ctx): void
    {
        $this->record('request', $ctx);
    }

    public function readBeforeTransmit(RequestContext $ctx): void
    {
        Async::delay(0.001);
    }

    public function readAfterTransmit(ResponseContext $ctx): void
    {
        $this->record('response', $ctx);
    }

    public function readAfterExecution(
        OutputContext $ctx,
        ?\Throwable $error
    ): void
    {
        $this->record('after', $ctx);
    }

    private function record(
        string $hook,
        InputContext|RequestContext|ResponseContext|OutputContext $ctx
    ): void
    {
        $this->records[] = "{$hook}:{$this->value($ctx)}:"
            . ($ctx->attributes->get($this->key) ?? 'none');
    }

    private function value(
        CallContext|InputContext|RequestContext|ResponseContext|OutputContext $ctx
    ): string
    {
        return $ctx->input instanceof FakeInput ? $ctx->input->value : '';
    }
}
