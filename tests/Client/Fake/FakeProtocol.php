<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Fake;

use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface,
    StreamInterface
};
use Smithy\Protocol\ProtocolInterface;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Http\Message\{
    Request,
    Uri
};

/**
 * Test double for {@see ProtocolInterface}. Serializes to a POST with a
 * body of `input:<value>` and reads the response body as the output
 * value. Enough shape to exercise the full pipeline without pulling in
 * XML or JSON codec dependencies.
 */
final class FakeProtocol implements ProtocolInterface
{
    /**
     * Create a fake protocol.
     */
    public function __construct(
        public ?\Throwable $deserializeError = null,
        private readonly ?StreamInterface $requestBody = null
    ) {}

    public function serializeRequest(
        OperationInterface $operation,
        SerializableStructInterface $input,
        string $endpoint
    ): RequestInterface
    {
        \assert($input instanceof FakeInput);

        return Request::fromNormalized(
            'POST',
            new Uri($endpoint),
            [],
            $this->requestBody ?? 'input:' . $input->value
        );
    }

    public function deserializeResponse(
        OperationInterface $operation,
        ResponseInterface $response
    ): SerializableStructInterface
    {
        if ($this->deserializeError !== null) {
            throw $this->deserializeError;
        }

        return new FakeOutput((string) $response->getBody());
    }
}
