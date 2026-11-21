<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\CodegenOutput\TrimmedPetStore;

use Smithy\Client\AbstractClient;
use Smithy\Client\AbstractClientConfig;
use Smithy\Client\Plugin\PluginCollection;
use Smithy\Client\RequestOverrideConfig;
use Smithy\Client\Waiter\WaiterConfig;
use Smithy\Client\Waiter\WaiterFailureException;
use Smithy\Client\Waiter\WaiterResult;
use Smithy\CodegenOutput\TrimmedPetStore\Input\GetPetInput;
use Smithy\CodegenOutput\TrimmedPetStore\Operation\GetPetOperation;
use Smithy\CodegenOutput\TrimmedPetStore\Output\GetPetOutput;

/**
 * Client for the TrimmedPetStore service.
 *
 * Emitted by ClientGenerator. Every operation method is a thin
 * typed wrapper around AbstractClient::executeOperation().
 * the pipeline, retry loop, endpoint resolver, signer, and
 * transport all live on the base class.
 */
final class TrimmedPetStoreClient extends AbstractClient implements TrimmedPetStoreClientInterface
{
    public function __construct(
        AbstractClientConfig $config,
        PluginCollection $plugins = new PluginCollection()
    )
    {
        parent::__construct(
            $config->withService(TrimmedPetStoreService::instance()),
            $plugins
        );
    }

    public function getPet(GetPetInput $input, ?RequestOverrideConfig $overrides = null): GetPetOutput
    {
        /** @var GetPetOutput */
        return $this->executeOperation(
            GetPetOperation::instance(),
            $input,
            $overrides
        );
    }

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
    ): WaiterResult
    {
        return $this->createWaiter(
            GetPetOperation::instance(),
            'PetExists',
            $config
        )->wait($input, $maxWaitTime);
    }
}
