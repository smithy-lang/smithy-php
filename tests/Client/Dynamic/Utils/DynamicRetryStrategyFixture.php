<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Dynamic\Utils;

use Smithy\Client\Retry\{
    RetryState,
    RetryStrategyInterface
};

final class DynamicRetryStrategyFixture implements RetryStrategyInterface
{
    public int $refreshCalls = 0;

    public function begin(
        string $scope,
        int $requestFlags = 0
    ): RetryState
    {
        return new RetryState($scope, $requestFlags);
    }

    public function refresh(
        RetryState $state,
        \Throwable $error
    ): float
    {
        ++$this->refreshCalls;
        ++$state->attempt;

        return 0.0;
    }

    public function recordSuccess(RetryState $state): void {}

    public function maxAttempts(): int
    {
        return 3;
    }
}
