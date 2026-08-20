<?php

declare(strict_types=1);

namespace Smithy\Protocol\RpcV2;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Smithy\Core\Serde\AbstractCodec;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Protocol\Utils\ComponentUri;
use Smithy\Http\Message\Request;
use Smithy\Protocol\HttpErrorDeserializer;
use Smithy\Protocol\ProtocolInterface;

/**
 * Shared base for Smithy RPC v2 protocols.
 *
 * RPC v2 protocols ignore HTTP binding traits and instead send every request
 * as a POST to `/service/{Service}/operation/{Operation}` with the protocol
 * identified by the `Smithy-Protocol` header. Subclasses supply the concrete
 * codec and media type (`rpc-v2-cbor` / `application/cbor` today, and a JSON
 * variant later). Error envelope extraction is delegated to the
 * protocol's {@see HttpErrorDeserializer}.
 *
 * The hot request path caches each operation's derived wire metadata once:
 * the fixed request path and whether the input/output shapes are `Unit`.
 * That keeps repeated calls on a long-lived client down to codec work,
 * endpoint stitching, and a handful of header assignments.
 */
abstract class AbstractRpcV2Protocol implements ProtocolInterface
{
    protected const HEADER_ACCEPT = 'accept';
    protected const HEADER_CONTENT_TYPE = 'content-type';
    protected const HEADER_CONTENT_LENGTH = 'content-length';
    protected const HEADER_SMITHY_PROTOCOL = 'smithy-protocol';

    /**
     * @var string Smithy-Protocol header value for the concrete protocol.
     */
    protected static string $protocolHeaderValue;
    /**
     * @var string Buffered request and response media type.
     */
    protected static string $mediaType;

    /**
     * Cached per-operation wire info: `[path, inputIsUnit, outputIsUnit]`.
     *
     * @var array<string, array{0: string, 1: bool, 2: bool}>
     */
    private array $wireInfoByOperation = [];

    /**
     * Create an RPC v2 protocol with its payload codec.
     */
    public function __construct(
        protected readonly AbstractCodec $codec,
        private readonly HttpErrorDeserializer $errorDeserializer
    ) {}

    /**
     * Serialize operation input into an RPC v2 HTTP request.
     */
    public function serializeRequest(
        OperationInterface $operation,
        SerializableStructInterface $input,
        string $endpoint,
    ): RequestInterface {
        [$requestPath, $inputIsUnit] = $this->resolveWireInfo($operation);

        $headers = [
            self::HEADER_SMITHY_PROTOCOL => [
                'Smithy-Protocol',
                [static::$protocolHeaderValue],
            ],
            self::HEADER_ACCEPT => ['Accept', [static::$mediaType]],
        ];

        $body = '';
        if (!$inputIsUnit) {
            $serializer = $this->codec->acquireSerializer();
            try {
                $input->serialize($serializer, 0);
                $body = $serializer->flush();
            } finally {
                $this->codec->releaseSerializer($serializer);
            }

            $headers[self::HEADER_CONTENT_TYPE] = [
                'Content-Type',
                [static::$mediaType]
            ];
            $headers[self::HEADER_CONTENT_LENGTH] = [
                'Content-Length',
                [(string) \strlen($body)]
            ];
        }

        $ep = \parse_url($endpoint);
        $endpointPath = $ep['path'] ?? '';
        $path = $endpointPath !== '' && $endpointPath !== '/'
            ? \rtrim($endpointPath, '/') . $requestPath
            : $requestPath;

        $uri = new ComponentUri(
            scheme: $ep['scheme'] ?? 'https',
            host: $ep['host'] ?? '',
            port: $ep['port'] ?? null,
            path: $path,
        );

        return Request::fromNormalized(
            'POST',
            $uri,
            $headers,
            $body,
        );
    }

    /**
     * Deserialize an RPC v2 HTTP response into operation output.
     */
    public function deserializeResponse(
        OperationInterface $operation,
        ResponseInterface $response,
    ): SerializableStructInterface {
        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200) {
            throw $this->errorDeserializer->deserialize(
                $operation,
                $response
            );
        }

        if (!$this->hasExpectedProtocolHeader($response)) {
            throw new \RuntimeException(
                'Malformed ' . static::$protocolHeaderValue
                . ' response: invalid Smithy-Protocol header for HTTP 200.',
            );
        }

        $outputSchema = $operation->getOutputSchema()->resolveTarget();
        [, , $outputIsUnit] = $this->resolveWireInfo($operation);
        if ($outputIsUnit) {
            return $operation->createOutput([]);
        }

        $body = (string) $response->getBody();
        if ($body === '') {
            if ($outputSchema->getMemberCount() === 0) {
                return $operation->createOutput([]);
            }

            throw new \RuntimeException(
                'Malformed ' . static::$protocolHeaderValue
                . ' response: missing body for non-Unit output.',
            );
        }

        $members = $this->codec->createMembersBuffer($outputSchema);

        $this->codec->createDeserializer($body)->deserialize(
            $outputSchema,
            $members
        );

        return $operation->createOutput($members);
    }

    /**
     * @return array{0: string, 1: bool, 2: bool}
     */
    private function resolveWireInfo(OperationInterface $operation): array
    {
        $runtimeIdentity = $operation->getRuntimeIdentity();

        return $this->wireInfoByOperation[$runtimeIdentity]
            ??= $this->buildWireInfo($operation);
    }

    /**
     * @return array{0: string, 1: bool, 2: bool}
     */
    private function buildWireInfo(OperationInterface $operation): array
    {
        $target = $operation->getRequestTarget();
        if ($target === null) {
            throw new \LogicException(
                'RPC v2 operations require a request target.'
            );
        }
        $dot = \strrpos($target, '.');
        if ($dot === false) {
            throw new \LogicException(
                'RPC v2 request targets must use the '
                    . '"<Service>.<Operation>" form.'
            );
        }

        $serviceName = \substr($target, 0, $dot);
        $operationName = \substr($target, $dot + 1);

        return [
            '/service/' . $serviceName . '/operation/' . $operationName,
            $operation->getInputSchema()->id === 'smithy.api#Unit',
            $operation->getOutputSchema()->id === 'smithy.api#Unit',
        ];
    }

    private function hasExpectedProtocolHeader(ResponseInterface $response): bool
    {
        return $response->getHeaderLine('Smithy-Protocol')
            === static::$protocolHeaderValue;
    }
}
