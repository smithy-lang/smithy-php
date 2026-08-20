<?php

declare(strict_types=1);

namespace Smithy\Protocol\RpcV2;

use Psr\Http\Message\ResponseInterface;
use Smithy\Core\Serde\AbstractCodec;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeType;
use Smithy\Protocol\HttpErrorDeserializer;

/**
 * Deserialize Smithy RPC v2 error responses.
 */
final class RpcV2ErrorDeserializer extends HttpErrorDeserializer
{
    private static ?Schema $envelopeSchema = null;

    /**
     * Create an RPC v2 error deserializer.
     */
    public function __construct(
        AbstractCodec $codec,
        private readonly string $protocolHeaderValue
    )
    {
        parent::__construct($codec);
    }

    /**
     * Parse the RPC v2 error envelope.
     *
     * @return array{
     *     discriminator: ?string,
     *     message: string,
     *     body: string,
     *     requestId: ?string
     * }
     */
    protected function parseError(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();
        if (
            $response->getHeaderLine('Smithy-Protocol')
            !== $this->protocolHeaderValue
        ) {
            throw new \RuntimeException(
                'Malformed ' . $this->protocolHeaderValue
                . " error response (HTTP {$statusCode})."
            );
        }

        $body = (string) $response->getBody();
        if ($body === '') {
            return [
                'discriminator' => null,
                'message' => '',
                'body' => '',
                'requestId' => null
            ];
        }

        $members = [null, null];
        $this->codec->createDeserializer($body)
            ->deserialize(self::envelopeSchema(), $members);

        return [
            'discriminator' => \is_string($members[0])
                ? $members[0]
                : null,
            'message' => \is_string($members[1]) ? $members[1] : '',
            'body' => $body,
            'requestId' => null
        ];
    }

    private static function envelopeSchema(): Schema
    {
        return self::$envelopeSchema ??= new Schema(
            id: 'smithy.protocols#rpcv2ErrorEnvelope',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.protocols#rpcv2ErrorEnvelope$__type',
                    type: ShapeType::String_,
                    memberName: '__type',
                    memberIndex: 0
                ),
                new Schema(
                    id: 'smithy.protocols#rpcv2ErrorEnvelope$message',
                    type: ShapeType::String_,
                    memberName: 'message',
                    memberIndex: 1
                )
            ]
        );
    }
}
