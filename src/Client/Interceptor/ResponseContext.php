<?php

declare(strict_types=1);

namespace Smithy\Client\Interceptor;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Smithy\Client\ClientConfig;
use Smithy\Client\Transport\{
    TransportRequestInterface,
    TransportResponseInterface
};
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Http\Message\{
    HttpTransportRequest,
    HttpTransportResponse
};

/**
 * Hook data available after the response has arrived but before it has
 * been deserialized into an output shape.
 *
 * Supplied to `readAfterTransmit`, `modifyBeforeDeserialization`, and
 * `readBeforeDeserialization`.
 */
final class ResponseContext
{
    public TransportRequestInterface $request;

    public TransportResponseInterface $response;

    /**
     * Create response hook context.
     */
    public function __construct(
        public OperationInterface $operation,
        public SerializableStructInterface $input,
        public ClientConfig $config,
        TransportRequestInterface|RequestInterface $request,
        TransportResponseInterface|ResponseInterface $response
    )
    {
        $this->request = $request instanceof TransportRequestInterface
            ? $request
            : new HttpTransportRequest($request);
        $this->response = $response instanceof TransportResponseInterface
            ? $response
            : new HttpTransportResponse($response);
    }

    /**
     * Replace the mutable response value in this context.
     *
     * @return void
     */
    public function replaceResponse(TransportResponseInterface $response): void
    {
        $this->response = $response;
    }

    /**
     * Return a new context with `response` replaced.
     */
    public function withResponse(TransportResponseInterface $response): self
    {
        return $response === $this->response
            ? $this
            : new self(
                $this->operation,
                $this->input,
                $this->config,
                $this->request,
                $response
            );
    }

    /**
     * Return the PSR-7 request for an HTTP message exchange.
     */
    public function getHttpRequest(): RequestInterface
    {
        return HttpTransportRequest::expect($this->request)->request;
    }

    /**
     * Return the PSR-7 response for an HTTP message exchange.
     */
    public function getHttpResponse(): ResponseInterface
    {
        return HttpTransportResponse::expect($this->response)->response;
    }

    /**
     * Replace the PSR-7 response in an HTTP message exchange.
     */
    public function replaceHttpResponse(ResponseInterface $response): void
    {
        $this->response = HttpTransportResponse::expect($this->response)
            ->withResponse($response);
    }
}
