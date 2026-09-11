<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Fake;

use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface,
    StreamInterface
};
use Smithy\Client\Endpoint\Endpoint;
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
    public const ID = 'smithy.tests#fakeProtocol';

    /**
     * Create a fake protocol.
     */
    public function __construct(
        public ?\Throwable $deserializeError = null,
        private readonly ?StreamInterface $requestBody = null,
        private readonly string $requestPrefix = 'input:'
    ) {}

    public function getId(): string
    {
        return self::ID;
    }

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
            $this->requestBody ?? $this->requestPrefix . $input->value
        );
    }

    public function setServiceEndpoint(
        RequestInterface $request,
        Endpoint $endpoint
    ): RequestInterface
    {
        $endpointUri = new Uri($endpoint->url);
        $requestUri = $request->getUri();
        $endpointPath = $endpointUri->getPath();
        $path = $endpointPath !== '' && $endpointPath !== '/'
            ? \rtrim($endpointPath, '/') . $requestUri->getPath()
            : $requestUri->getPath();

        return $request->withUri(
            $endpointUri
                ->withPath($path)
                ->withQuery($requestUri->getQuery())
                ->withFragment('')
                ->withUserInfo('')
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
