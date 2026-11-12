<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Protocol\RpcV2;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Smithy\Async\RevoltEventLoop;
use Smithy\Codec\Cbor\CborCodec;
use Smithy\Codec\Json\JsonCodec;
use Smithy\Core\Exception\ApiException;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Http\Message\{
    Response,
    StreamingResponseStream
};
use Smithy\Protocol\Exception\ProtocolException;
use Smithy\Protocol\RpcV2\RpcV2ErrorDeserializer;

final class RpcV2ErrorDeserializerTest extends TestCase
{
    #[DataProvider('protocolHeaders')]
    public function testEmptyBodyDoesNotReadStream(string $header): void
    {
        $body = $this->createMock(StreamInterface::class);
        $body->expects(self::never())->method('__toString');
        $body->expects(self::never())->method('getSize');
        $body->expects(self::never())->method('getContents');

        $error = $this->deserialize($header, new Response(
            400,
            ['Smithy-Protocol' => $header, 'Content-Length' => '0'],
            $body
        ));

        self::assertSame('HTTP 400', $error->getMessage());
        self::assertNull($error->getPrevious());
    }

    #[DataProvider('protocolHeaders')]
    public function testEmptyBodyStillValidatesProtocolHeader(
        string $header
    ): void
    {
        $body = $this->createMock(StreamInterface::class);
        $body->expects(self::never())->method('__toString');
        $body->expects(self::never())->method('getContents');

        $error = $this->deserialize($header, new Response(
            400,
            ['Smithy-Protocol' => 'wrong', 'Content-Length' => '0'],
            $body
        ));

        self::assertInstanceOf(
            ProtocolException::class,
            $error->getPrevious()
        );
    }

    #[DataProvider('protocolHeaders')]
    public function testPreservesStreamReadFailure(string $header): void
    {
        $failure = new \RuntimeException('Response read failed.');
        $body = new StreamingResponseStream(
            new RevoltEventLoop(),
            1024,
            static function (): void {},
            static function (): void {}
        );
        $body->fail($failure);

        $error = $this->deserialize($header, new Response(
            500,
            ['Smithy-Protocol' => $header],
            $body
        ));

        self::assertSame($failure, $error->getPrevious());
        self::assertSame('Response read failed.', $error->getMessage());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function protocolHeaders(): iterable
    {
        yield 'CBOR' => ['rpc-v2-cbor'];
        yield 'JSON' => ['rpc-v2-json'];
    }

    private function deserialize(
        string $header,
        Response $response
    ): \Throwable
    {
        $operation = $this->createStub(OperationInterface::class);
        $operation->method('getServiceExceptionClass')
            ->willReturn(ApiException::class);
        $codec = $header === 'rpc-v2-cbor'
            ? new CborCodec()
            : new JsonCodec();

        return (new RpcV2ErrorDeserializer($codec, $header))->deserialize(
            $operation,
            $response
        );
    }
}
