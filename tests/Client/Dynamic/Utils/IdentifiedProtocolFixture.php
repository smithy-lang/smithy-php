<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Dynamic\Utils;

use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface
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

    public function serializeRequest(
        OperationInterface $operation,
        SerializableStructInterface $input,
        string $endpoint
    ): RequestInterface
    {
        return $this->delegate->serializeRequest(
            $operation,
            $input,
            $endpoint
        );
    }

    public function deserializeResponse(
        OperationInterface $operation,
        ResponseInterface $response
    ): SerializableStructInterface
    {
        return $this->delegate->deserializeResponse(
            $operation,
            $response
        );
    }
}
