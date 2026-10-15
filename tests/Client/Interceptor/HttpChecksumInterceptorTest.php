<?php

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
    HttpChecksumInterceptor,
    InterceptorChain,
    RequestCompressionInterceptor,
    RequestContext
};
use Smithy\Client\Transport\TransportRequestInterface;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\Http\Message\{
    AsyncRequestStream,
    AsyncRequestStreamInterface,
    HttpTransportRequest,
    Request,
    ResourceRequestStream
};
use Smithy\Tests\Client\Fake\{
    FakeInput,
    FakeProtocol
};
use Smithy\Tests\Client\Utils\UnknownSizeStreamFixture;
use Smithy\Tests\Http\Message\Utils\GenericStreamFixture;
use Smithy\Tests\Utils\Http\{
    GenericPsrRequest,
    MockHttpClient
};

#[CoversClass(HttpChecksumInterceptor::class)]
final class HttpChecksumInterceptorTest extends TestCase
{
    public function testAddsContentMd5ForChecksumRequiredOperation(): void
    {
        $interceptor = new HttpChecksumInterceptor();
        $request = new Request(
            'POST',
            'https://example.test',
            [],
            '{"foo":"bar"}'
        );

        $updated = $interceptor->modifyBeforeSigning(self::requestContext(
            $this->operation([
                Schema::T_HTTP_CHECKSUM_REQUIRED => true
            ]),
            new FakeInput(),
            $this->config(),
            $request
        ));

        self::assertSame(
            'm7WPJhkuS6APAeLnsTa72A==',
            HttpTransportRequest::expect($updated)->request->getHeaderLine('Content-MD5')
        );
    }

    public function testAddsContentMd5ToGenericPsrRequest(): void
    {
        $request = new GenericPsrRequest(new Request(
            'POST',
            'https://example.test',
            [],
            '{"foo":"bar"}'
        ));

        $updated = (new HttpChecksumInterceptor())->modifyBeforeSigning(
            self::requestContext(
                $this->operation([
                    Schema::T_HTTP_CHECKSUM_REQUIRED => true
                ]),
                new FakeInput(),
                $this->config(),
                $request
            )
        );

        self::assertInstanceOf(
            GenericPsrRequest::class,
            HttpTransportRequest::expect($updated)->request
        );
        self::assertSame(
            'm7WPJhkuS6APAeLnsTa72A==',
            HttpTransportRequest::expect($updated)->request->getHeaderLine('Content-MD5')
        );
    }

    public function testPreservesResourceBackedBodyPosition(): void
    {
        $body = ResourceRequestStream::temp('payload');
        $request = new Request(
            'POST',
            'https://example.test',
            [],
            $body
        );

        $updated = (new HttpChecksumInterceptor())->modifyBeforeSigning(
            self::requestContext(
                $this->operation([
                    Schema::T_HTTP_CHECKSUM_REQUIRED => true
                ]),
                new FakeInput(),
                $this->config(),
                $request
            )
        );

        self::assertSame(
            'Mhw89IbtUJFk7eweGYH+yA==',
            HttpTransportRequest::expect($updated)->request->getHeaderLine('Content-MD5')
        );
        self::assertSame($body, HttpTransportRequest::expect($updated)->request->getBody());
        self::assertSame(0, $body->tell());
        self::assertSame('payload', $body->getContents());
    }

    public function testSkipsWhenHeaderAlreadyPresent(): void
    {
        $interceptor = new HttpChecksumInterceptor();
        $request = new Request(
            'POST',
            'https://example.test',
            ['Content-MD5' => 'preset'],
            '{"foo":"bar"}'
        );

        $updated = $interceptor->modifyBeforeSigning(self::requestContext(
            $this->operation([
                Schema::T_HTTP_CHECKSUM_REQUIRED => true
            ]),
            new FakeInput(),
            $this->config(),
            $request
        ));

        self::assertSame('preset', HttpTransportRequest::expect($updated)->request->getHeaderLine('Content-MD5'));
    }

    public function testSkipsForOperationWithoutTrait(): void
    {
        $interceptor = new HttpChecksumInterceptor();
        $request = new Request(
            'POST',
            'https://example.test',
            [],
            '{"foo":"bar"}'
        );

        $updated = $interceptor->modifyBeforeSigning(self::requestContext(
            $this->operation(),
            new FakeInput(),
            $this->config(),
            $request
        ));

        self::assertFalse(HttpTransportRequest::expect($updated)->request->hasHeader('Content-MD5'));
    }

    public function testHashesSeekableStreamingRequest(): void
    {
        $stream = new UnknownSizeStreamFixture('payload');
        $interceptor = new HttpChecksumInterceptor();
        $request = new Request('POST', 'https://example.test', [], $stream);

        $updated = $interceptor->modifyBeforeSigning(self::requestContext(
            $this->operation(
                [Schema::T_HTTP_CHECKSUM_REQUIRED => true],
                true
            ),
            new FakeInput(),
            $this->config(),
            $request
        ));

        self::assertSame(
            'Mhw89IbtUJFk7eweGYH+yA==',
            HttpTransportRequest::expect($updated)->request->getHeaderLine('Content-MD5')
        );
        self::assertSame(0, $stream->tell());
    }

    public function testBuffersNonReplayableBodyBeforeRetryLoop(): void
    {
        $stream = new GenericStreamFixture(
            content: 'payload',
            seekable: false
        );
        $request = new Request(
            'POST',
            'https://example.test',
            [],
            $stream
        );

        $updated = (new HttpChecksumInterceptor())
            ->modifyBeforeRetryLoop(self::requestContext(
                $this->operation([
                    Schema::T_HTTP_CHECKSUM_REQUIRED => true
                ]),
                new FakeInput(),
                $this->config(),
                $request
            ));

        self::assertInstanceOf(
            ResourceRequestStream::class,
            HttpTransportRequest::expect($updated)->request->getBody()
        );
        self::assertSame('payload', (string) HttpTransportRequest::expect($updated)->request->getBody());
        self::assertTrue(HttpTransportRequest::expect($updated)->request->getBody()->isReplayable());
        self::assertFalse(HttpTransportRequest::expect($updated)->request->hasHeader('Content-MD5'));
        self::assertTrue($stream->eof());
    }

    public function testChecksumsCompressedStreamingBodyWithoutRebuffering(): void
    {
        $eventLoop = new RevoltEventLoop();
        $stream = new AsyncRequestStream($eventLoop);
        $request = new Request(
            'POST',
            'https://example.test',
            [],
            $stream
        );
        $ctx = self::requestContext(
            $this->operation(
                [
                    Schema::T_REQUEST_COMPRESSION => ['gzip'],
                    Schema::T_HTTP_CHECKSUM_REQUIRED => true
                ],
                true
            ),
            new FakeInput(),
            $this->config(),
            $request
        );
        $chain = new InterceptorChain(
            new RequestCompressionInterceptor(),
            new HttpChecksumInterceptor()
        );

        $results = $eventLoop->run([
            fn(): TransportRequestInterface =>
                $chain->modifyBeforeRetryLoop($ctx),
            static function () use ($stream): void {
                $stream->write('payload');
                $stream->end();
            }
        ]);
        $prepared = $results[0];
        $compressedBody = HttpTransportRequest::expect($prepared)->request->getBody();
        $updated = $chain->modifyBeforeSigning(
            $ctx->withRequest($prepared)
        );
        $compressed = (string) $compressedBody;

        self::assertSame($compressedBody, HttpTransportRequest::expect($updated)->request->getBody());
        self::assertSame('payload', \gzdecode($compressed));
        self::assertSame(
            \base64_encode(\md5($compressed, true)),
            HttpTransportRequest::expect($updated)->request->getHeaderLine('Content-MD5')
        );
        self::assertTrue($stream->fullyConsumed());
    }

    public function testBuffersAndHashesAsyncStreamingRequest(): void
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
            fn(): TransportRequestInterface =>
                (new HttpChecksumInterceptor())->modifyBeforeSigning(
                    self::requestContext(
                        $this->operation(
                            [Schema::T_HTTP_CHECKSUM_REQUIRED => true],
                            true
                        ),
                        new FakeInput(),
                        $this->config(),
                        $request
                    )
                ),
            static function () use ($stream): void {
                $stream->write('pay');
                $stream->write('load');
                $stream->end();
            }
        ]);
        $updated = $results[0];

        self::assertSame(
            'Mhw89IbtUJFk7eweGYH+yA==',
            HttpTransportRequest::expect($updated)->request->getHeaderLine('Content-MD5')
        );
        self::assertInstanceOf(
            ResourceRequestStream::class,
            HttpTransportRequest::expect($updated)->request->getBody()
        );
        self::assertSame('payload', (string) HttpTransportRequest::expect($updated)->request->getBody());
        self::assertTrue(HttpTransportRequest::expect($updated)->request->getBody()->isReplayable());
        self::assertTrue($stream->fullyConsumed());
    }

    public function testBuffersAndHashesNonSeekableStreamingRequest(): void
    {
        $stream = new GenericStreamFixture(
            content: 'payload',
            seekable: false
        );
        $request = new Request(
            'POST',
            'https://example.test',
            [],
            $stream
        );

        $updated = (new HttpChecksumInterceptor())->modifyBeforeSigning(
            self::requestContext(
                $this->operation(
                    [Schema::T_HTTP_CHECKSUM_REQUIRED => true],
                    true
                ),
                new FakeInput(),
                $this->config(),
                $request
            )
        );

        self::assertSame(
            'Mhw89IbtUJFk7eweGYH+yA==',
            HttpTransportRequest::expect($updated)->request->getHeaderLine('Content-MD5')
        );
        self::assertInstanceOf(
            ResourceRequestStream::class,
            HttpTransportRequest::expect($updated)->request->getBody()
        );
        self::assertSame('payload', (string) HttpTransportRequest::expect($updated)->request->getBody());
        self::assertTrue(HttpTransportRequest::expect($updated)->request->getBody()->isReplayable());
        self::assertTrue($stream->eof());
    }

    public function testDetachesAsyncConsumerWhenBufferingFails(): void
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
            (new HttpChecksumInterceptor())->modifyBeforeSigning(
                self::requestContext(
                    $this->operation([
                        Schema::T_HTTP_CHECKSUM_REQUIRED => true
                    ]),
                    new FakeInput(),
                    $this->config(),
                    $request
                )
            );
        } catch (\RuntimeException $error) {
            self::assertSame($failure, $error);

            return;
        }

        self::fail('Expected request body buffering to fail.');
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
            id: 'smithy.tests#ChecksumOperation',
            type: ShapeType::Operation,
            traits: $traits
        ));
        $streamMember = $streaming
            ? new Schema(
                id: 'smithy.tests#ChecksumOperation$body',
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
