<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Protocol\RpcV2\Cbor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Codec\Cbor\CborCodec;
use Smithy\Client\Endpoint\Endpoint;
use Smithy\Core\Exception\ApiException;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\Document\StructDocument;
use Smithy\Core\Shape\Exception\DocumentException;
use Smithy\Core\Shape\ShapeType;
use Smithy\Http\Message\{
    HttpTransportRequest,
    HttpTransportResponse,
    Request,
    RequestStreamInterface,
    Response
};
use Smithy\Protocol\Exception\ProtocolException;
use Smithy\Protocol\HttpErrorDeserializer;
use Smithy\Protocol\RpcV2\AbstractRpcV2Protocol;
use Smithy\Protocol\RpcV2\RpcV2CborProtocol;
use Smithy\Protocol\RpcV2\RpcV2ErrorDeserializer;
use Smithy\Tests\Http\Message\Utils\GenericStreamFixture;
use Smithy\Tests\Protocol\RpcV2\Cbor\Fixtures\RpcV2CborOperationFixture;
use Smithy\Tests\Protocol\RpcV2\Cbor\Fixtures\RpcV2CborServiceExceptionFixture;

#[CoversClass(AbstractRpcV2Protocol::class)]
#[CoversClass(HttpErrorDeserializer::class)]
#[CoversClass(RpcV2CborProtocol::class)]
#[CoversClass(RpcV2ErrorDeserializer::class)]
final class RpcV2CborProtocolTest extends TestCase
{
    public function testSerializesUnitInputRequest(): void
    {
        $schema = self::unitSchema();
        $operation = self::operation(
            inputSchema: $schema,
            outputSchema: $schema
        );

        $protocol = new RpcV2CborProtocol();
        $request = $protocol->serializeRequest(
            $operation,
            StructDocument::fromMembers($schema, [])
        );
        $request = $protocol->setServiceEndpoint(
            $request,
            new Endpoint('https://example.amazonaws.com:8443/base')
        );
        $request = HttpTransportRequest::expect($request)->request;

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
        self::assertSame(
            'example.amazonaws.com:8443',
            $request->getHeaderLine('Host')
        );
        self::assertSame('Host', \array_key_first($request->getHeaders()));
        self::assertFalse($request->hasHeader('Content-Type'));
        self::assertSame('', (string) $request->getBody());
    }

    public function testBindsEndpointAfterUnresolvedSerialization(): void
    {
        $schema = self::unitSchema();
        $operation = self::operation(
            inputSchema: $schema,
            outputSchema: $schema
        );
        $protocol = new RpcV2CborProtocol();
        $transportRequest = $protocol->serializeRequest(
            $operation,
            StructDocument::fromMembers($schema, [])
        );
        $request = HttpTransportRequest::expect($transportRequest)->request;

        self::assertSame(
            '/service/ExampleService/operation/GetThing',
            (string) $request->getUri()
        );
        self::assertSame('', $request->getHeaderLine('Host'));

        $transportRequest = $protocol->setServiceEndpoint(
            $transportRequest,
            new Endpoint('https://example.amazonaws.com:8443/base')
        );
        $request = HttpTransportRequest::expect($transportRequest)->request;

        self::assertSame(
            'https://example.amazonaws.com:8443/base/service/'
                . 'ExampleService/operation/GetThing',
            (string) $request->getUri()
        );
        self::assertSame(
            'example.amazonaws.com:8443',
            $request->getHeaderLine('Host')
        );
    }

    public function testPrefersExplicitRequestTarget(): void
    {
        $schema = self::unitSchema();
        $operation = self::operation(
            inputSchema: $schema,
            outputSchema: $schema,
            requestTarget: 'CustomService.CustomOperation'
        );

        $protocol = new RpcV2CborProtocol();
        $request = $protocol->serializeRequest(
            $operation,
            StructDocument::fromMembers($schema, [])
        );
        $request = $protocol->setServiceEndpoint(
            $request,
            new Endpoint('https://example.amazonaws.com/base')
        );
        $request = HttpTransportRequest::expect($request)->request;

        self::assertSame(
            '/base/service/CustomService/operation/CustomOperation',
            $request->getUri()->getPath()
        );
    }

    #[DataProvider('malformedRequestTargets')]
    public function testRejectsMalformedExplicitRequestTarget(
        string $requestTarget
    ): void
    {
        $schema = self::unitSchema();
        $operation = self::operation(
            inputSchema: $schema,
            outputSchema: $schema,
            requestTarget: $requestTarget
        );

        $this->expectException(ProtocolException::class);
        $this->expectExceptionMessage(
            'RPC v2 request targets must use the '
                . '"<Service>.<Operation>" form.'
        );

        (new RpcV2CborProtocol())->serializeRequest(
            $operation,
            StructDocument::fromMembers($schema, [])
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function malformedRequestTargets(): iterable
    {
        yield 'missing separator' => ['MalformedTarget'];
        yield 'empty service' => ['.Operation'];
        yield 'empty operation' => ['Service.'];
        yield 'empty service and operation' => ['.'];
    }

    public function testTreatsReferencedUnitShapesAsUnit(): void
    {
        $schema = self::unitReferenceSchema();
        $operation = self::operation(
            inputSchema: $schema,
            outputSchema: $schema
        );
        $protocol = new RpcV2CborProtocol();

        $request = $protocol->serializeRequest(
            $operation,
            StructDocument::fromMembers($schema, [])
        );
        $output = $protocol->deserializeResponse(
            $operation,
            new HttpTransportResponse(new Response(
                status: 200,
                headers: ['Smithy-Protocol' => 'rpc-v2-cbor']
            ))
        );
        $request = HttpTransportRequest::expect($request)->request;

        self::assertFalse($request->hasHeader('Content-Type'));
        self::assertSame('', (string) $request->getBody());
        self::assertSame(self::unitSchema()->id, $output->getSchema()->id);
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
            new HttpTransportResponse(new Response(
                status: 200,
                headers: [
                    'Smithy-Protocol' => 'rpc-v2-cbor',
                    'Content-Length' => (string) \strlen($body)
                ],
                body: $body
            ))
        );

        self::assertSame(
            'ok',
            $output->getMemberValue($outputSchema->getMember('message'))
        );
    }

    public function testContentLengthZeroDoesNotReadSuccessBody(): void
    {
        $body = $this->createMock(RequestStreamInterface::class);
        $body->expects(self::never())->method('getSize');
        $body->expects(self::never())->method('getContents');

        $this->expectException(ProtocolException::class);
        $this->expectExceptionMessage(
            'missing body for non-Unit output'
        );

        (new RpcV2CborProtocol())->deserializeResponse(
            self::operation(
                inputSchema: self::unitSchema(),
                outputSchema: self::messageSchema('smithy.example#Output')
            ),
            new HttpTransportResponse(new Response(
                status: 200,
                headers: [
                    'Smithy-Protocol' => 'rpc-v2-cbor',
                    'Content-Length' => '0'
                ],
                body: $body
            ))
        );
    }

    public function testMissingContentLengthDeserializesUnknownSizeBody(): void
    {
        $outputSchema = self::messageSchema('smithy.example#Output');
        $body = self::serializeStruct($outputSchema, ['ok']);

        $output = (new RpcV2CborProtocol())->deserializeResponse(
            self::operation(
                inputSchema: self::unitSchema(),
                outputSchema: $outputSchema
            ),
            new HttpTransportResponse(new Response(
                status: 200,
                headers: ['Smithy-Protocol' => 'rpc-v2-cbor'],
                body: new GenericStreamFixture($body, false)
            ))
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

        $this->expectException(ProtocolException::class);
        $this->expectExceptionMessage('invalid Smithy-Protocol header');

        (new RpcV2CborProtocol())->deserializeResponse(
            $operation,
            new HttpTransportResponse(new Response(
                status: 200,
                headers: ['Smithy-Protocol' => 'wrong-protocol']
            ))
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
                new HttpTransportResponse(new Response(
                    status: 400,
                    headers: ['Smithy-Protocol' => 'rpc-v2-cbor'],
                    body: new GenericStreamFixture($body, false)
                ))
            );
        } catch (DocumentException $exception) {
            self::assertSame($errorSchema, $exception->getSchema());
            self::assertSame('failed', $exception->getMessage());

            return;
        }

        self::fail('Expected a modeled error.');
    }

    public function testUsesServiceExceptionForUnmodeledErrorResponse(): void
    {
        $operation = self::operation(
            inputSchema: self::unitSchema(),
            outputSchema: self::unitSchema(),
            serviceExceptionClass:
                RpcV2CborServiceExceptionFixture::class
        );
        $body = self::serializeStruct(
            self::errorEnvelopeSchema(),
            ['UnknownError', 'failed']
        );

        try {
            (new RpcV2CborProtocol())->deserializeResponse(
                $operation,
                new HttpTransportResponse(new Response(
                    status: 500,
                    headers: ['Smithy-Protocol' => 'rpc-v2-cbor'],
                    body: $body
                ))
            );
        } catch (RpcV2CborServiceExceptionFixture $exception) {
            self::assertSame('failed', $exception->getMessage());
            self::assertTrue($exception->isServerFault());

            return;
        }

        self::fail('Expected the service exception fallback.');
    }

    public function testContentLengthZeroDoesNotReadErrorBody(): void
    {
        $body = $this->createMock(RequestStreamInterface::class);
        $body->expects(self::never())->method('getSize');
        $body->expects(self::never())->method('getContents');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('HTTP 400');

        (new RpcV2CborProtocol())->deserializeResponse(
            self::operation(
                inputSchema: self::unitSchema(),
                outputSchema: self::unitSchema()
            ),
            new HttpTransportResponse(new Response(
                status: 400,
                headers: [
                    'Smithy-Protocol' => 'rpc-v2-cbor',
                    'Content-Length' => '0'
                ],
                body: $body
            ))
        );
    }

    public function testInvalidErrorProtocolHeaderDoesNotReadBody(): void
    {
        $body = $this->createMock(RequestStreamInterface::class);
        $body->expects(self::never())->method('getSize');
        $body->expects(self::never())->method('getContents');

        try {
            (new RpcV2CborProtocol())->deserializeResponse(
                self::operation(
                    inputSchema: self::unitSchema(),
                    outputSchema: self::unitSchema(),
                    serviceExceptionClass:
                        RpcV2CborServiceExceptionFixture::class
                ),
                new HttpTransportResponse(new Response(
                    status: 400,
                    headers: ['Smithy-Protocol' => 'wrong-protocol'],
                    body: $body
                ))
            );
        } catch (RpcV2CborServiceExceptionFixture $exception) {
            self::assertSame(
                'Malformed rpc-v2-cbor error response (HTTP 400).',
                $exception->getMessage()
            );
            self::assertInstanceOf(
                ProtocolException::class,
                $exception->getPrevious()
            );

            return;
        }

        self::fail('Expected the service exception fallback.');
    }

    /**
     * @param array<string, Schema> $errorSchemas
     */
    private static function operation(
        Schema $inputSchema,
        Schema $outputSchema,
        array $errorSchemas = [],
        ?string $requestTarget = null,
        string $serviceExceptionClass = ApiException::class
    ): RpcV2CborOperationFixture {
        return new RpcV2CborOperationFixture(
            inputSchema: $inputSchema,
            outputSchema: $outputSchema,
            errorSchemas: $errorSchemas,
            requestTarget: $requestTarget,
            serviceExceptionClass: $serviceExceptionClass
        );
    }

    private static function unitSchema(): Schema
    {
        return new Schema(
            id: 'smithy.api#Unit',
            type: ShapeType::Structure
        );
    }

    private static function unitReferenceSchema(): Schema
    {
        return new Schema(
            id: 'smithy.example#UnitReference',
            type: ShapeType::Structure,
            target: self::unitSchema()
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
