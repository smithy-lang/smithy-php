<?php

declare(strict_types=1);

namespace Smithy\Http\Message;

use Psr\Http\Message\{
    RequestInterface,
    StreamInterface,
    UriInterface
};
use Smithy\Client\Endpoint\Endpoint;
use Smithy\Client\Transport\TransportRequestInterface;

/**
 * Transport-neutral envelope containing one PSR-7 HTTP request.
 */
final readonly class HttpTransportRequest implements
    TransportRequestInterface,
    RequestInterface
{
    public function __construct(
        public RequestInterface $request
    ) {}

    /**
     * Require an HTTP request envelope.
     */
    public static function expect(
        TransportRequestInterface|RequestInterface $request
    ): self
    {
        if ($request instanceof self) {
            return $request;
        }
        if ($request instanceof RequestInterface) {
            return new self($request);
        }

        throw new \InvalidArgumentException(
            'Expected transport request of type ' . self::class
                . ', got ' . $request::class . '.'
        );
    }

    /**
     * Return an envelope containing a replacement PSR-7 request.
     */
    public function withRequest(RequestInterface $request): self
    {
        return $request === $this->request ? $this : new self($request);
    }

    /**
     * Apply endpoint headers to the enclosed HTTP request.
     */
    public function withEndpointHeaders(Endpoint $endpoint): self
    {
        $headers = $endpoint->headers;
        if ($headers === []) {
            return $this;
        }

        $rawHeaders = [];
        foreach ($headers as $name => $value) {
            $value = (string) $value;
            HttpMessageValidator::validateHeaderName($name);
            HttpMessageValidator::validateHeaderValue($value);
            $rawHeaders[\strtolower($name)] = [$name, [$value]];
        }

        $request = $this->request;
        if ($request instanceof Request) {
            return $this->withRequest(
                $request->withNormalizedHeaders($rawHeaders)
            );
        }

        foreach ($rawHeaders as [$name, $values]) {
            $request = $request->withHeader($name, $values);
        }

        return $this->withRequest($request);
    }

    public function getRequestTarget(): string
    {
        return $this->request->getRequestTarget();
    }

    public function withRequestTarget(string $requestTarget): self
    {
        return $this->withRequest(
            $this->request->withRequestTarget($requestTarget)
        );
    }

    public function getMethod(): string
    {
        return $this->request->getMethod();
    }

    public function withMethod(string $method): self
    {
        return $this->withRequest($this->request->withMethod($method));
    }

    public function getUri(): UriInterface
    {
        return $this->request->getUri();
    }

    public function withUri(
        UriInterface $uri,
        bool $preserveHost = false
    ): self
    {
        return $this->withRequest(
            $this->request->withUri($uri, $preserveHost)
        );
    }

    public function getProtocolVersion(): string
    {
        return $this->request->getProtocolVersion();
    }

    public function withProtocolVersion(string $version): self
    {
        return $this->withRequest(
            $this->request->withProtocolVersion($version)
        );
    }

    public function getHeaders(): array
    {
        return $this->request->getHeaders();
    }

    public function hasHeader(string $name): bool
    {
        return $this->request->hasHeader($name);
    }

    public function getHeader(string $name): array
    {
        return $this->request->getHeader($name);
    }

    public function getHeaderLine(string $name): string
    {
        return $this->request->getHeaderLine($name);
    }

    public function withHeader(string $name, $value): self
    {
        return $this->withRequest(
            $this->request->withHeader($name, $value)
        );
    }

    public function withAddedHeader(string $name, $value): self
    {
        return $this->withRequest(
            $this->request->withAddedHeader($name, $value)
        );
    }

    public function withoutHeader(string $name): self
    {
        return $this->withRequest($this->request->withoutHeader($name));
    }

    public function getBody(): StreamInterface
    {
        return $this->request->getBody();
    }

    public function withBody(StreamInterface $body): self
    {
        return $this->withRequest($this->request->withBody($body));
    }
}
