<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\CodegenOutput\TrimmedPetStore;

use Smithy\Client\ClientInterface;
use Smithy\Client\RequestOverrideConfig;
use Smithy\Client\Waiter\WaiterConfig;
use Smithy\Client\Waiter\WaiterFailureException;
use Smithy\Client\Waiter\WaiterResult;
use Smithy\CodegenOutput\TrimmedPetStore\Input\GetPetInput;
use Smithy\CodegenOutput\TrimmedPetStore\Output\GetPetOutput;

/**
 * Public contract for the TrimmedPetStore service client.
 */
interface TrimmedPetStoreClientInterface extends ClientInterface
{
    public function getPet(?GetPetInput $input = null, ?RequestOverrideConfig $overrides = null): GetPetOutput;

    /**
     * Wait until {@see self::getPet()} reports the pet as existing.
     *
     * @param float $maxWaitTime Maximum total wait time in seconds.
     *
     * @return WaiterResult Terminal output or accepted error.
     * @throws WaiterFailureException If waiting does not succeed.
     * @throws \InvalidArgumentException If waiter timing is invalid.
     *
     * @see self::getPet()
     */
    public function waitUntilPetExists(
        GetPetInput $input,
        float $maxWaitTime,
        ?WaiterConfig $config = null
    ): WaiterResult;
}
