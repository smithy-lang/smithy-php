<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Waiter\Generated\Waiter;

use Smithy\Client\AbstractClient;
use Smithy\Client\Waiter\{
    WaiterConfig,
    WaiterFailureException,
    WaiterResult
};
use Smithy\Tests\Client\Waiter\Generated\Waiter\Input\GetThingInput;
use Smithy\Tests\Client\Waiter\Generated\Waiter\Operation\GetThingOperation;
use Smithy\Tests\Client\Waiter\Generated\Waiter\Output\GetThingOutput;
/**
 * Client for the Waiter service.
 *
 * Emitted by ClientGenerator. Every operation method is a thin
 * typed wrapper around {@see AbstractClient::executeOperation()};
 * the pipeline, retry loop, endpoint resolver, signer, and
 * transport all live on the base class.
 */
final class WaiterClient extends AbstractClient
{
    public function getThing(GetThingInput $input): GetThingOutput
    {
        /** @var GetThingOutput */
        return $this->executeOperation(
            GetThingOperation::instance(),
            $input
        );
    }

    /**
     * Wait until the thing is ready.
     *
     * @param float $maxWaitTime Maximum total wait time in seconds.
     *
     * @return WaiterResult Terminal output or accepted error.
     * @throws WaiterFailureException If waiting does not succeed.
     * @throws \InvalidArgumentException If waiter timing is invalid.
     *
     * @see self::getThing()
     */
    public function waitUntilThingReady(
        GetThingInput $input,
        float $maxWaitTime,
        ?WaiterConfig $config = null
    ): WaiterResult
    {
        return $this->createWaiter(
            GetThingOperation::instance(),
            'ThingReady',
            $config
        )->wait($input, $maxWaitTime);
    }
}
