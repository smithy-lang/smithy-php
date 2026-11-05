<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Fake;

use Smithy\Client\{
    AbstractClient,
    ClientConfig
};
use Smithy\Client\Plugin\PluginCollection;
use Smithy\Client\Waiter\{
    Waiter,
    WaiterConfig
};
use Smithy\Core\Shape\OperationInterface;

final class TestClientFixture extends AbstractClient
{
    public function __construct(
        ClientConfig $config,
        PluginCollection $plugins = new PluginCollection()
    )
    {
        parent::__construct(
            $config->withService(FakeService::instance()),
            $plugins
        );
    }

    public function createWaiterForTest(
        OperationInterface $operation,
        string $waiterName,
        ?WaiterConfig $config = null
    ): Waiter
    {
        return $this->createWaiter($operation, $waiterName, $config);
    }
}
