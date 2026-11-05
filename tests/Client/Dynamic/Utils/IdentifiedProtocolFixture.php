<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Dynamic\Utils;

use Smithy\Client\Endpoint\Endpoint;
use Smithy\Client\Transport\{
    MessageExchangeInterface,
    TransportRequestInterface,
    TransportResponseInterface
};
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Protocol\ProtocolInterface;
use Smithy\Protocol\RpcV2\RpcV2CborProtocol;

final class IdentifiedProtocolFixture implements ProtocolInterface
{
    public function __construct(
        private readonly string $id,
        private readonly ProtocolInterface $delegate =
            new RpcV2CborProtocol()
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getMessageExchange(): MessageExchangeInterface
    {
        return $this->delegate->getMessageExchange();
    }

    public function serializeRequest(
        OperationInterface $operation,
        SerializableStructInterface $input
    ): TransportRequestInterface
    {
        return $this->delegate->serializeRequest(
            $operation,
            $input
        );
    }

    public function setServiceEndpoint(
        TransportRequestInterface $request,
        Endpoint $endpoint
    ): TransportRequestInterface
    {
        return $this->delegate->setServiceEndpoint($request, $endpoint);
    }

    public function deserializeResponse(
        OperationInterface $operation,
        TransportResponseInterface $response
    ): SerializableStructInterface
    {
        return $this->delegate->deserializeResponse(
            $operation,
            $response
        );
    }
}
