<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Http\Client\Curl\Utils;

use Smithy\Http\Client\Connection\{
    ConnectionHealthStatus,
    ConnectionState,
    HttpConnectionInterface,
    RequestContext
};

/**
 * Non-cURL connection used to test manager factory validation.
 */
final class NonCurlConnectionFixture implements HttpConnectionInterface
{
    private ConnectionState $state = ConnectionState::CONNECTED;

    public function __construct(
        private readonly RequestContext $requestContext
    ) {}

    /** @inheritDoc */
    public function close(): void
    {
        $this->state = ConnectionState::CLOSED;
    }

    /** @inheritDoc */
    public function state(): ConnectionState
    {
        return $this->state;
    }

    /** @inheritDoc */
    public function healthStatus(): ConnectionHealthStatus
    {
        return ConnectionHealthStatus::UNKNOWN;
    }

    /** @inheritDoc */
    public function markUnhealthy(): void {}

    /** @inheritDoc */
    public function requestContext(): RequestContext
    {
        return $this->requestContext;
    }

    /** @inheritDoc */
    public function lastUsedAt(): float
    {
        return 0.0;
    }
}
