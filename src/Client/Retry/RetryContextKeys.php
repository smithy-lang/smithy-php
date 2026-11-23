<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Client\Retry;

use Smithy\Core\Context\ContextKey;

/**
 * Client config context keys read by the retry loop.
 */
final class RetryContextKeys
{
    /** @var ContextKey<RetryRequestFlagResolverInterface>|null */
    private static ?ContextKey $requestFlagResolver = null;

    private function __construct() {}

    /**
     * Key for the resolver whose flags are passed to
     * {@see RetryStrategyInterface::begin()} for each operation call.
     *
     * @return ContextKey<RetryRequestFlagResolverInterface>
     */
    public static function requestFlagResolver(): ContextKey
    {
        return self::$requestFlagResolver ??= new ContextKey(
            'smithy.client.retryRequestFlagResolver'
        );
    }
}
