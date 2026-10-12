<?php

declare(strict_types=1);

namespace Smithy\Http\Message;

use Psr\Http\Message\{
    ResponseInterface,
    StreamInterface
};
use Smithy\Client\Transport\TransportResponseInterface;

/**
 * Transport-neutral envelope containing one PSR-7 HTTP response.
 */
final readonly class HttpTransportResponse implements
    TransportResponseInterface,
    ResponseInterface
{
    public function __construct(
        public ResponseInterface $response
    ) {}

    /**
     * Require an HTTP response envelope.
     */
    public static function expect(
        TransportResponseInterface|ResponseInterface $response
    ): self
    {
        if ($response instanceof self) {
            return $response;
        }
        if ($response instanceof ResponseInterface) {
            return new self($response);
        }

        throw new \InvalidArgumentException(
            'Expected transport response of type ' . self::class
                . ', got ' . $response::class . '.'
        );
    }

    /**
     * Return an envelope containing a replacement PSR-7 response.
     */
    public function withResponse(ResponseInterface $response): self
    {
        return $response === $this->response ? $this : new self($response);
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    public function withStatus(
        int $code,
        string $reasonPhrase = ''
    ): self
    {
        return $this->withResponse(
            $this->response->withStatus($code, $reasonPhrase)
        );
    }

    public function getReasonPhrase(): string
    {
        return $this->response->getReasonPhrase();
    }

    public function getProtocolVersion(): string
    {
        return $this->response->getProtocolVersion();
    }

    public function withProtocolVersion(string $version): self
    {
        return $this->withResponse(
            $this->response->withProtocolVersion($version)
        );
    }

    public function getHeaders(): array
    {
        return $this->response->getHeaders();
    }

    public function hasHeader(string $name): bool
    {
        return $this->response->hasHeader($name);
    }

    public function getHeader(string $name): array
    {
        return $this->response->getHeader($name);
    }

    public function getHeaderLine(string $name): string
    {
        return $this->response->getHeaderLine($name);
    }

    public function withHeader(string $name, $value): self
    {
        return $this->withResponse(
            $this->response->withHeader($name, $value)
        );
    }

    public function withAddedHeader(string $name, $value): self
    {
        return $this->withResponse(
            $this->response->withAddedHeader($name, $value)
        );
    }

    public function withoutHeader(string $name): self
    {
        return $this->withResponse(
            $this->response->withoutHeader($name)
        );
    }

    public function getBody(): StreamInterface
    {
        return $this->response->getBody();
    }

    public function withBody(StreamInterface $body): self
    {
        return $this->withResponse($this->response->withBody($body));
    }
}
