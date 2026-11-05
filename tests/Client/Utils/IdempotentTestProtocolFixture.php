<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Utils;

use Smithy\Client\Endpoint\Endpoint;
use Smithy\Client\Transport\{
    MessageExchangeInterface,
    TransportRequestInterface,
    TransportResponseInterface
};
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Http\Message\HttpMessageExchange;
use Smithy\Protocol\ProtocolInterface;

final class IdempotentTestProtocolFixture implements ProtocolInterface
{
    public function getId(): string
    {
        return 'smithy.tests#idempotentTestProtocol';
    }

    public function getMessageExchange(): MessageExchangeInterface
    {
        return HttpMessageExchange::instance();
    }

    public function serializeRequest(
        OperationInterface $operation,
        SerializableStructInterface $input
    ): TransportRequestInterface
    {
        throw new \LogicException('Not used by idempotency tests.');
    }

    public function setServiceEndpoint(
        TransportRequestInterface $request,
        Endpoint $endpoint
    ): TransportRequestInterface
    {
        throw new \LogicException('Not used by idempotency tests.');
    }

    public function deserializeResponse(
        OperationInterface $operation,
        TransportResponseInterface $response
    ): SerializableStructInterface
    {
        throw new \LogicException('Not used by idempotency tests.');
    }
}
