<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Interceptor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Smithy\Async\RevoltEventLoop;
use Smithy\Client\ClientConfig;
use Smithy\Client\Endpoint\{
    Endpoint,
    StaticEndpointResolver
};
use Smithy\Client\Interceptor\{
    RequestCompressionInterceptor,
    RequestContext
};
use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\Http\Message\{
    AsyncRequestStream,
    AsyncRequestStreamInterface,
    HttpTransportRequest,
    Request,
    ResourceRequestStream,
    Stream
};
use Smithy\Tests\Client\Fake\{
    FakeInput,
    FakeProtocol
};
use Smithy\Tests\Client\Utils\UnknownSizeStreamFixture;
use Smithy\Tests\Utils\Http\{
    GenericPsrRequest,
    MockHttpClient
};

#[CoversClass(RequestCompressionInterceptor::class)]
final class RequestCompressionInterceptorTest extends TestCase
{
    public function testCompressesRequestBodyAndSetsHeaders(): void
    {
        $body = \str_repeat('a', 11000);
        $interceptor = new RequestCompressionInterceptor();
        $request = new Request('POST', 'https://example.test', [], $body);

        $updated = $interceptor->modifyBeforeRetryLoop(self::requestContext(
            $this->operation([
                Schema::T_REQUEST_COMPRESSION => ['gzip']
            ]),
            new FakeInput(),
            $this->config(),
            $request
        ));

        self::assertSame(
            'gzip',
            HttpTransportRequest::expect($updated)->request->getHeaderLine('Content-Encoding')
        );
        self::assertSame($body, \gzdecode((string) HttpTransportRequest::expect($updated)->request->getBody()));
        self::assertSame(
            (string) \strlen((string) HttpTransportRequest::expect($updated)->request->getBody()),
            HttpTransportRequest::expect($updated)->request->getHeaderLine('Content-Length')
        );
    }

    public function testCompressesGenericPsrRequest(): void
    {
        $body = \str_repeat('a', 11000);
        $request = new GenericPsrRequest(new Request(
            'POST',
            'https://example.test',
            [],
            $body
        ));

        $updated = (new RequestCompressionInterceptor())
            ->modifyBeforeRetryLoop(self::requestContext(
                $this->operation([
                    Schema::T_REQUEST_COMPRESSION => ['gzip']
                ]),
                new FakeInput(),
                $this->config(),
                $request
            ));

        self::assertInstanceOf(
            GenericPsrRequest::class,
            HttpTransportRequest::expect($updated)->request
        );
        self::assertSame(
            'gzip',
            HttpTransportRequest::expect($updated)->request->getHeaderLine('Content-Encoding')
        );
        self::assertSame(
            (string) \strlen((string) HttpTransportRequest::expect($updated)->request->getBody()),
            HttpTransportRequest::expect($updated)->request->getHeaderLine('Content-Length')
        );
        self::assertSame($body, \gzdecode((string) HttpTransportRequest::expect($updated)->request->getBody()));
    }

    public function testCompressesFromLogicalBodyStart(): void
    {
        $body = Stream::create('prefixpayload');
        $body->seek(6);
        $request = new Request(
            'POST',
            'https://example.test',
            [],
            $body
        );

        $updated = (new RequestCompressionInterceptor(
            minCompressionSizeBytes: 0
        ))->modifyBeforeRetryLoop(self::requestContext(
            $this->operation([
                Schema::T_REQUEST_COMPRESSION => ['gzip']
            ]),
            new FakeInput(),
            $this->config(),
            $request
        ));

        self::assertSame(
            'payload',
            \gzdecode((string) HttpTransportRequest::expect($updated)->request->getBody())
        );
        self::assertSame(6, $body->tell());
    }

    public function testAppendsContentEncodingAfterUserProvidedValue(): void
    {
        $interceptor = new RequestCompressionInterceptor(
            minCompressionSizeBytes: 0
        );
        $request = new Request(
            'POST',
            'https://example.test',
            ['Content-Encoding' => 'custom'],
            'payload'
        );

        $updated = $interceptor->modifyBeforeRetryLoop(self::requestContext(
            $this->operation([
                Schema::T_REQUEST_COMPRESSION => ['gzip']
            ]),
            new FakeInput(),
            $this->config(),
            $request
        ));

        self::assertSame(
            'custom, gzip',
            HttpTransportRequest::expect($updated)->request->getHeaderLine('Content-Encoding')
        );
        self::assertSame(
            'payload',
            \gzdecode((string) HttpTransportRequest::expect($updated)->request->getBody())
        );
    }

    public function testPreservesTransferEncodingForCompressedRequest(): void
    {
        $interceptor = new RequestCompressionInterceptor(
            minCompressionSizeBytes: 0
        );
        $request = new Request(
            'POST',
            'https://example.test',
            ['Transfer-Encoding' => 'chunked'],
            'payload'
        );

        $updated = $interceptor->modifyBeforeRetryLoop(self::requestContext(
            $this->operation([
                Schema::T_REQUEST_COMPRESSION => ['gzip']
            ]),
            new FakeInput(),
            $this->config(),
            $request
        ));

        self::assertSame(
            'chunked',
            HttpTransportRequest::expect($updated)->request->getHeaderLine('Transfer-Encoding')
        );
        self::assertFalse(HttpTransportRequest::expect($updated)->request->hasHeader('Content-Length'));
        self::assertSame(
            'gzip',
            HttpTransportRequest::expect($updated)->request->getHeaderLine('Content-Encoding')
        );
        self::assertSame(
            'payload',
            \gzdecode((string) HttpTransportRequest::expect($updated)->request->getBody())
        );
    }

    public function testPreservesTransferEncodingForGenericPsrRequest(): void
    {
        $interceptor = new RequestCompressionInterceptor(
            minCompressionSizeBytes: 0
        );
        $request = new GenericPsrRequest(new Request(
            'POST',
            'https://example.test',
            ['Transfer-Encoding' => 'chunked'],
            'payload'
        ));

        $updated = $interceptor->modifyBeforeRetryLoop(self::requestContext(
            $this->operation([
                Schema::T_REQUEST_COMPRESSION => ['gzip']
            ]),
            new FakeInput(),
            $this->config(),
            $request
        ));

        self::assertInstanceOf(
            GenericPsrRequest::class,
            HttpTransportRequest::expect($updated)->request
        );
        self::assertSame(
            'chunked',
            HttpTransportRequest::expect($updated)->request->getHeaderLine('Transfer-Encoding')
        );
        self::assertFalse(HttpTransportRequest::expect($updated)->request->hasHeader('Content-Length'));
        self::assertSame(
            'gzip',
            HttpTransportRequest::expect($updated)->request->getHeaderLine('Content-Encoding')
        );
        self::assertSame(
            'payload',
            \gzdecode((string) HttpTransportRequest::expect($updated)->request->getBody())
        );
    }

    public function testSkipsCompressionBelowThreshold(): void
    {
        $interceptor = new RequestCompressionInterceptor(
            minCompressionSizeBytes: 10
        );
        $request = new Request('POST', 'https://example.test', [], 'short');

        $updated = $interceptor->modifyBeforeRetryLoop(self::requestContext(
            $this->operation([
                Schema::T_REQUEST_COMPRESSION => ['gzip']
            ]),
            new FakeInput(),
            $this->config(),
            $request
        ));

        self::assertSame(
            $request,
            HttpTransportRequest::expect($updated)->request
        );
        self::assertFalse(HttpTransportRequest::expect($updated)->request->hasHeader('Content-Encoding'));
    }

    public function testCompressesStreamingRequestRegardlessOfThreshold(): void
    {
        $body = 'streaming-payload';
        $stream = new UnknownSizeStreamFixture($body);
        $interceptor = new RequestCompressionInterceptor(
            minCompressionSizeBytes: 10485760
        );
        $request = new Request(
            'POST',
            'https://example.test',
            [],
            $stream
        );

        $updated = $interceptor->modifyBeforeRetryLoop(self::requestContext(
            $this->operation(
                [Schema::T_REQUEST_COMPRESSION => ['gzip']],
                true
            ),
            new FakeInput(),
            $this->config(),
            $request
        ));

        self::assertInstanceOf(
            ResourceRequestStream::class,
            HttpTransportRequest::expect($updated)->request->getBody()
        );
        self::assertSame(
            'gzip',
            HttpTransportRequest::expect($updated)->request->getHeaderLine('Content-Encoding')
        );
        $compressed = (string) HttpTransportRequest::expect($updated)->request->getBody();
        self::assertSame(
            (string) \strlen($compressed),
            HttpTransportRequest::expect($updated)->request->getHeaderLine('Content-Length')
        );
        self::assertSame($body, \gzdecode($compressed));
        self::assertSame(0, $stream->tell());
        self::assertTrue(HttpTransportRequest::expect($updated)->request->getBody()->isReplayable());

        HttpTransportRequest::expect($updated)->request->getBody()->rewindForRetry();
        self::assertSame(
            $compressed,
            (string) HttpTransportRequest::expect($updated)->request->getBody()
        );
    }

    public function testPreservesTransferEncodingForStreamingRequest(): void
    {
        $request = new Request(
            'POST',
            'https://example.test',
            ['Transfer-Encoding' => 'chunked'],
            new UnknownSizeStreamFixture('payload')
        );

        $updated = (new RequestCompressionInterceptor())
            ->modifyBeforeRetryLoop(self::requestContext(
                $this->operation(
                    [Schema::T_REQUEST_COMPRESSION => ['gzip']],
                    true
                ),
                new FakeInput(),
                $this->config(),
                $request
            ));

        self::assertSame(
            'chunked',
            HttpTransportRequest::expect($updated)->request->getHeaderLine('Transfer-Encoding')
        );
        self::assertFalse(HttpTransportRequest::expect($updated)->request->hasHeader('Content-Length'));
        self::assertSame(
            'gzip',
            HttpTransportRequest::expect($updated)->request->getHeaderLine('Content-Encoding')
        );
        self::assertSame(
            'payload',
            \gzdecode((string) HttpTransportRequest::expect($updated)->request->getBody())
        );
    }

    public function testCompressesAsyncStreamingRequest(): void
    {
        $eventLoop = new RevoltEventLoop();
        $stream = new AsyncRequestStream($eventLoop);
        $request = new Request(
            'POST',
            'https://example.test',
            [],
            $stream
        );

        $results = $eventLoop->run([
            fn() => (new RequestCompressionInterceptor())
                ->modifyBeforeRetryLoop(self::requestContext(
                    $this->operation(
                        [Schema::T_REQUEST_COMPRESSION => ['gzip']],
                        true
                    ),
                    new FakeInput(),
                    $this->config(),
                    $request
                )),
            static function () use ($stream): void {
                $stream->write('pay');
                $stream->write('load');
                $stream->end();
            }
        ]);
        $updated = $results[0];

        self::assertInstanceOf(
            ResourceRequestStream::class,
            HttpTransportRequest::expect($updated)->request->getBody()
        );
        self::assertSame(
            'gzip',
            HttpTransportRequest::expect($updated)->request->getHeaderLine('Content-Encoding')
        );
        $compressed = (string) HttpTransportRequest::expect($updated)->request->getBody();
        self::assertSame(
            (string) \strlen($compressed),
            HttpTransportRequest::expect($updated)->request->getHeaderLine('Content-Length')
        );
        self::assertSame('payload', \gzdecode($compressed));
        self::assertTrue(HttpTransportRequest::expect($updated)->request->getBody()->isReplayable());
        self::assertTrue($stream->fullyConsumed());
    }

    public function testCompressesKnownLengthStreamingRequestBelowThreshold(): void
    {
        $eventLoop = new RevoltEventLoop();
        $stream = new AsyncRequestStream(
            $eventLoop,
            expectedLength: 7
        );
        $request = new Request(
            'POST',
            'https://example.test',
            [],
            $stream
        );

        $results = $eventLoop->run([
            fn() => (new RequestCompressionInterceptor())
                ->modifyBeforeRetryLoop(self::requestContext(
                    $this->operation(
                        [Schema::T_REQUEST_COMPRESSION => ['gzip']],
                        true
                    ),
                    new FakeInput(),
                    $this->config(),
                    $request
                )),
            static function () use ($stream): void {
                $stream->write('payload');
                $stream->end();
            }
        ]);
        $updated = $results[0];

        self::assertSame(
            'gzip',
            HttpTransportRequest::expect($updated)->request->getHeaderLine('Content-Encoding')
        );
        self::assertSame(
            'payload',
            \gzdecode((string) HttpTransportRequest::expect($updated)->request->getBody())
        );
    }

    public function testDetachesAsyncConsumerWhenCompressionFails(): void
    {
        $failure = new \RuntimeException('Request body read failed.');
        $stream = $this->createMock(AsyncRequestStreamInterface::class);
        $stream->method('isReplayable')->willReturn(false);
        $stream->method('isSeekable')->willReturn(false);
        $stream->method('eof')->willReturn(false);
        $stream->method('read')->willThrowException($failure);
        $stream->expects(self::once())->method('detachConsumer');
        $request = new Request(
            'POST',
            'https://example.test',
            [],
            $stream
        );

        try {
            (new RequestCompressionInterceptor())->modifyBeforeRetryLoop(
                self::requestContext(
                    $this->operation(
                        [Schema::T_REQUEST_COMPRESSION => ['gzip']],
                        true
                    ),
                    new FakeInput(),
                    $this->config(),
                    $request
                )
            );
        } catch (\RuntimeException $error) {
            self::assertSame($failure, $error);

            return;
        }

        self::fail('Expected request body compression to fail.');
    }

    public function testDetachesAsyncConsumerWhenCompressionSetupFails(): void
    {
        $failure = new \RuntimeException('Request body setup failed.');
        $stream = $this->createMock(AsyncRequestStreamInterface::class);
        $stream->method('isReplayable')->willReturn(false);
        $stream->method('isSeekable')->willThrowException($failure);
        $stream->expects(self::once())->method('detachConsumer');
        $request = new Request(
            'POST',
            'https://example.test',
            [],
            $stream
        );

        try {
            (new RequestCompressionInterceptor())->modifyBeforeRetryLoop(
                self::requestContext(
                    $this->operation(
                        [Schema::T_REQUEST_COMPRESSION => ['gzip']],
                        true
                    ),
                    new FakeInput(),
                    $this->config(),
                    $request
                )
            );
        } catch (\RuntimeException $error) {
            self::assertSame($failure, $error);

            return;
        }

        self::fail('Expected request body compression setup to fail.');
    }

    public function testSkipsCompressionWhenDisabled(): void
    {
        $interceptor = new RequestCompressionInterceptor(
            disabled: true,
            minCompressionSizeBytes: 0
        );
        $request = new Request('POST', 'https://example.test', [], 'payload');

        $updated = $interceptor->modifyBeforeRetryLoop(self::requestContext(
            $this->operation([
                Schema::T_REQUEST_COMPRESSION => ['gzip']
            ]),
            new FakeInput(),
            $this->config(),
            $request
        ));

        self::assertSame(
            $request,
            HttpTransportRequest::expect($updated)->request
        );
        self::assertFalse(HttpTransportRequest::expect($updated)->request->hasHeader('Content-Encoding'));
    }

    public function testSkipsOperationWithoutCompressionTrait(): void
    {
        $request = new Request(
            'POST',
            'https://example.test',
            [],
            \str_repeat('a', 11000)
        );

        $updated = (new RequestCompressionInterceptor())
            ->modifyBeforeRetryLoop(self::requestContext(
                $this->operation(),
                new FakeInput(),
                $this->config(),
                $request
            ));

        self::assertSame(
            $request,
            HttpTransportRequest::expect($updated)->request
        );
    }

    public function testSkipsUnsupportedCompressionEncoding(): void
    {
        $request = new Request(
            'POST',
            'https://example.test',
            [],
            \str_repeat('a', 11000)
        );

        $updated = (new RequestCompressionInterceptor())
            ->modifyBeforeRetryLoop(self::requestContext(
                $this->operation([
                    Schema::T_REQUEST_COMPRESSION => ['br']
                ]),
                new FakeInput(),
                $this->config(),
                $request
            ));

        self::assertSame(
            $request,
            HttpTransportRequest::expect($updated)->request
        );
    }

    public function testSkipsBodyWithUnknownSize(): void
    {
        $request = new Request(
            'POST',
            'https://example.test',
            [],
            new UnknownSizeStreamFixture(\str_repeat('a', 11000))
        );

        $updated = (new RequestCompressionInterceptor())
            ->modifyBeforeRetryLoop(self::requestContext(
                $this->operation([
                    Schema::T_REQUEST_COMPRESSION => ['gzip']
                ]),
                new FakeInput(),
                $this->config(),
                $request
            ));

        self::assertSame(
            $request,
            HttpTransportRequest::expect($updated)->request
        );
    }

    /**
     * @param array<int, mixed> $traits
     */
    private function operation(
        array $traits = [],
        bool $streaming = false
    ): OperationInterface
    {
        $operation = $this->createStub(OperationInterface::class);
        $operation->method('getSchema')->willReturn(new Schema(
            id: 'smithy.tests#CompressionOperation',
            type: ShapeType::Operation,
            traits: $traits
        ));
        $streamMember = $streaming
            ? new Schema(
                id: 'smithy.tests#CompressionOperation$body',
                type: ShapeType::Blob,
                memberName: 'body',
                memberIndex: 0
            )
            : null;
        $operation->method('getInputStreamMember')->willReturn($streamMember);

        return $operation;
    }

    private function config(): ClientConfig
    {
        return new ClientConfig(
            protocol: new FakeProtocol(),
            transport: new \Smithy\Http\Client\HttpClientTransport(
                new MockHttpClient()
            ),
            endpointResolver: new StaticEndpointResolver(
                new Endpoint('https://example.test')
            )
        );
    }

    private static function requestContext(
        OperationInterface $operation,
        FakeInput $input,
        ClientConfig $config,
        RequestInterface $request
    ): RequestContext {
        return new RequestContext(
            $operation,
            $input,
            $config,
            new HttpTransportRequest($request)
        );
    }
}
