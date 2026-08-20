<?php

declare(strict_types=1);

namespace Smithy\Tests\Protocol\RpcV2\Cbor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Codec\Cbor\CborCodec;
use Smithy\Core\Exception\DocumentException;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeType;
use Smithy\Core\Shape\Document\StructDocument;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Http\Message\Request;
use Smithy\Http\Message\Response;
use Smithy\Protocol\HttpErrorDeserializer;
use Smithy\Protocol\RpcV2\AbstractRpcV2Protocol;
use Smithy\Protocol\RpcV2\RpcV2CborProtocol;
use Smithy\Protocol\RpcV2\RpcV2ErrorDeserializer;

#[CoversClass(AbstractRpcV2Protocol::class)]
#[CoversClass(HttpErrorDeserializer::class)]
#[CoversClass(RpcV2CborProtocol::class)]
#[CoversClass(RpcV2ErrorDeserializer::class)]
final class RpcV2ProtocolTest extends TestCase
{
    public function testSerializesUnitInputRequest(): void
    {
        $schema = self::unitSchema();
        $operation = self::operation(
            inputSchema: $schema,
            outputSchema: $schema
        );

        $request = (new RpcV2CborProtocol())->serializeRequest(
            $operation,
            StructDocument::fromMembers($schema, []),
            'https://example.amazonaws.com/base',
        );

        self::assertInstanceOf(Request::class, $request);
        self::assertSame(
            '/base/service/ExampleService/operation/GetThing',
            $request->getUri()->getPath()
        );
        self::assertSame('POST', $request->getMethod());
        self::assertSame(
            'rpc-v2-cbor',
            $request->getHeaderLine('Smithy-Protocol')
        );
        self::assertSame('application/cbor', $request->getHeaderLine('Accept'));
        self::assertFalse($request->hasHeader('Content-Type'));
        self::assertSame('', (string) $request->getBody());
    }

    public function testDeserializesSuccessResponse(): void
    {
        $inputSchema = self::unitSchema();
        $outputSchema = self::messageSchema('smithy.example#Output');
        $operation = self::operation(
            inputSchema: $inputSchema,
            outputSchema: $outputSchema
        );
        $body = self::serializeStruct($outputSchema, ['ok']);

        $output = (new RpcV2CborProtocol())->deserializeResponse(
            $operation,
            new Response(
                status: 200,
                headers: ['Smithy-Protocol' => 'rpc-v2-cbor'],
                body: $body
            )
        );

        self::assertSame(
            'ok',
            $output->getMemberValue($outputSchema->getMember('message'))
        );
    }

    public function testRejectsSuccessResponseWithWrongProtocolHeader(): void
    {
        $schema = self::unitSchema();
        $operation = self::operation(
            inputSchema: $schema,
            outputSchema: $schema
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('invalid Smithy-Protocol header');

        (new RpcV2CborProtocol())->deserializeResponse(
            $operation,
            new Response(
                status: 200,
                headers: ['Smithy-Protocol' => 'wrong-protocol']
            )
        );
    }

    public function testDeserializesModeledErrorResponse(): void
    {
        $inputSchema = self::unitSchema();
        $outputSchema = self::unitSchema();
        $errorSchema = self::messageSchema('smithy.example#ModeledError');
        $operation = self::operation(
            inputSchema: $inputSchema,
            outputSchema: $outputSchema,
            errorSchemas: [$errorSchema->id => $errorSchema]
        );
        $envelopeSchema = self::errorEnvelopeSchema();
        $body = self::serializeStruct(
            $envelopeSchema,
            [$errorSchema->id, 'failed']
        );

        try {
            (new RpcV2CborProtocol())->deserializeResponse(
                $operation,
                new Response(
                    status: 400,
                    headers: ['Smithy-Protocol' => 'rpc-v2-cbor'],
                    body: $body
                )
            );
        } catch (DocumentException $exception) {
            self::assertSame($errorSchema, $exception->getSchema());
            self::assertSame('failed', $exception->getMessage());

            return;
        }

        self::fail('Expected a modeled error.');
    }

    /**
     * @param array<string, Schema> $errorSchemas
     */
    private static function operation(
        Schema $inputSchema,
        Schema $outputSchema,
        array $errorSchemas = []
    ): OperationInterface {
        return new class ($inputSchema, $outputSchema, $errorSchemas)
            implements OperationInterface
        {
            /**
             * @param array<string, Schema> $errorSchemas
             */
            public function __construct(
                private readonly Schema $inputSchema,
                private readonly Schema $outputSchema,
                private readonly array $errorSchemas
            ) {}

            public function getName(): string
            {
                return 'GetThing';
            }

            public function getId(): string
            {
                return 'smithy.example#GetThing';
            }

            public function getRuntimeIdentity(): string
            {
                return self::class . '#' . \spl_object_id($this);
            }

            public function getSchema(): Schema
            {
                return new Schema(
                    id: $this->getId(),
                    type: ShapeType::Operation
                );
            }

            public function getInputSchema(): Schema
            {
                return $this->inputSchema;
            }

            public function getOutputSchema(): Schema
            {
                return $this->outputSchema;
            }

            public function createInput(
                array $members
            ): SerializableStructInterface
            {
                return StructDocument::fromMembers(
                    $this->inputSchema,
                    $members
                );
            }

            public function createOutput(
                array $members
            ): SerializableStructInterface
            {
                return StructDocument::fromMembers(
                    $this->outputSchema,
                    $members
                );
            }

            public function getRequestTarget(): ?string
            {
                return 'ExampleService.GetThing';
            }

            public function resolveErrorSchema(
                string $discriminator
            ): ?Schema
            {
                return $this->errorSchemas[$discriminator] ?? null;
            }

            public function getInputStreamMember(): ?Schema
            {
                return null;
            }

            public function getOutputStreamMember(): ?Schema
            {
                return null;
            }
        };
    }

    private static function unitSchema(): Schema
    {
        return new Schema(
            id: 'smithy.api#Unit',
            type: ShapeType::Structure
        );
    }

    private static function messageSchema(string $id): Schema
    {
        return new Schema(
            id: $id,
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: $id . '$message',
                    type: ShapeType::String_,
                    memberName: 'message',
                    memberIndex: 0
                )
            ]
        );
    }

    private static function errorEnvelopeSchema(): Schema
    {
        return new Schema(
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

    /**
     * @param array<int, mixed> $members
     */
    private static function serializeStruct(Schema $schema, array $members): string
    {
        $serializer = (new CborCodec())->createSerializer();
        StructDocument::fromMembers($schema, $members)->serialize(
            $serializer,
            0
        );

        return $serializer->flush();
    }
}
