<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Fake;

use Smithy\Client\AbstractClient;
use Smithy\Client\Waiter\{
    Waiter,
    WaiterConfig
};
use Smithy\Core\Shape\OperationInterface;

final class TestClientFixture extends AbstractClient
{
    public function createWaiterForTest(
        OperationInterface $operation,
        string $waiterName,
        ?WaiterConfig $config = null
    ): Waiter
    {
        return $this->createWaiter($operation, $waiterName, $config);
    }
}
