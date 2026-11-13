<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Fake;

use Psr\Http\Message\{
    StreamInterface
};
use Smithy\Core\Endpoint\Endpoint;
use Smithy\Core\Transport\{
    MessageExchangeInterface,
    TransportRequestInterface,
    TransportResponseInterface
};
use Smithy\Protocol\ProtocolInterface;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Http\Message\{
    HttpMessageExchange,
    HttpTransportRequest,
    HttpTransportResponse,
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

    public function getMessageExchange(): MessageExchangeInterface
    {
        return HttpMessageExchange::instance();
    }

    public function serializeRequest(
        OperationInterface $operation,
        SerializableStructInterface $input
    ): TransportRequestInterface
    {
        \assert($input instanceof FakeInput);

        return new HttpTransportRequest(
            Request::fromNormalized(
                'POST',
                new Uri('/'),
                [],
                $this->requestBody ?? $this->requestPrefix . $input->value
            )
        );
    }

    public function setServiceEndpoint(
        TransportRequestInterface $request,
        Endpoint $endpoint
    ): TransportRequestInterface
    {
        $request = HttpTransportRequest::expect($request);
        $httpRequest = $request->request;
        $endpointUri = new Uri($endpoint->url);
        $requestUri = $httpRequest->getUri();
        $endpointPath = $endpointUri->getPath();
        $path = $endpointPath !== '' && $endpointPath !== '/'
            ? \rtrim($endpointPath, '/') . $requestUri->getPath()
            : $requestUri->getPath();

        return $request
            ->withRequest(
                $httpRequest->withUri(
                    $endpointUri
                        ->withPath($path)
                        ->withQuery($requestUri->getQuery())
                        ->withFragment('')
                        ->withUserInfo('')
                )
            )
            ->withEndpointHeaders($endpoint);
    }

    public function deserializeResponse(
        OperationInterface $operation,
        TransportResponseInterface $response
    ): SerializableStructInterface
    {
        if ($this->deserializeError !== null) {
            throw $this->deserializeError;
        }

        return new FakeOutput(
            (string) HttpTransportResponse::expect($response)
                ->response
                ->getBody()
        );
    }
}
