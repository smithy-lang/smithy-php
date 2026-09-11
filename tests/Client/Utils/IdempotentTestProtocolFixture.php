<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Utils;

use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface
};
use Smithy\Client\Endpoint\Endpoint;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Protocol\ProtocolInterface;

final class IdempotentTestProtocolFixture implements ProtocolInterface
{
    public function getId(): string
    {
        return 'smithy.tests#idempotentTestProtocol';
    }

    public function serializeRequest(
        OperationInterface $operation,
        SerializableStructInterface $input,
        string $endpoint
    ): RequestInterface
    {
        throw new \LogicException('Not used by idempotency tests.');
    }

    public function setServiceEndpoint(
        RequestInterface $request,
        Endpoint $endpoint
    ): RequestInterface
    {
        throw new \LogicException('Not used by idempotency tests.');
    }

    public function deserializeResponse(
        OperationInterface $operation,
        ResponseInterface $response
    ): SerializableStructInterface
    {
        throw new \LogicException('Not used by idempotency tests.');
    }
}
