<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Utils;

use Smithy\Client\Interceptor\{
    InputContext,
    LogContextProviderInterface,
    OutputContext,
    RequestContext
};
use Smithy\Tests\Client\Fake\FakeInput;

/**
 * Context provider that adds fixed values and the fake input value, and
 * records the hook contexts it receives.
 */
final class InputLogContextProviderFixture implements
    LogContextProviderInterface
{
    /** @var list<InputContext|RequestContext|OutputContext> */
    public array $contexts = [];

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        private readonly array $context = []
    ) {}

    /** @inheritDoc */
    public function getLogContext(
        InputContext|RequestContext|OutputContext $ctx,
        ?\Throwable $error
    ): array
    {
        $this->contexts[] = $ctx;
        $input = $ctx->input;

        return [
            ...$this->context,
            'inputValue' => $input instanceof FakeInput ? $input->value : null
        ];
    }
}
