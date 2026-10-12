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

        $updated = $interceptor->modifyBeforeSigning(new RequestContext(
            $this->operation([
                Schema::T_HTTP_CHECKSUM_REQUIRED => true
            ]),
            new FakeInput(),
            $this->config(),
            $request
        ));

        self::assertSame(
            'm7WPJhkuS6APAeLnsTa72A==',
            $updated->getHeaderLine('Content-MD5')
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
            new RequestContext(
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
            $updated->getHeaderLine('Content-MD5')
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
            new RequestContext(
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
            $updated->getHeaderLine('Content-MD5')
        );
        self::assertSame($body, $updated->getBody());
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

        $updated = $interceptor->modifyBeforeSigning(new RequestContext(
            $this->operation([
                Schema::T_HTTP_CHECKSUM_REQUIRED => true
            ]),
            new FakeInput(),
            $this->config(),
            $request
        ));

        self::assertSame('preset', $updated->getHeaderLine('Content-MD5'));
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

        $updated = $interceptor->modifyBeforeSigning(new RequestContext(
            $this->operation(),
            new FakeInput(),
            $this->config(),
            $request
        ));

        self::assertFalse($updated->hasHeader('Content-MD5'));
    }

    public function testHashesSeekableStreamingRequest(): void
    {
        $stream = new UnknownSizeStreamFixture('payload');
        $interceptor = new HttpChecksumInterceptor();
        $request = new Request('POST', 'https://example.test', [], $stream);

        $updated = $interceptor->modifyBeforeSigning(new RequestContext(
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
            $updated->getHeaderLine('Content-MD5')
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
            ->modifyBeforeRetryLoop(new RequestContext(
                $this->operation([
                    Schema::T_HTTP_CHECKSUM_REQUIRED => true
                ]),
                new FakeInput(),
                $this->config(),
                $request
            ));

        self::assertInstanceOf(
            ResourceRequestStream::class,
            $updated->getBody()
        );
        self::assertSame('payload', (string) $updated->getBody());
        self::assertTrue($updated->getBody()->isReplayable());
        self::assertFalse($updated->hasHeader('Content-MD5'));
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
        $ctx = new RequestContext(
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
            fn(): RequestInterface => $chain->modifyBeforeRetryLoop($ctx),
            static function () use ($stream): void {
                $stream->write('payload');
                $stream->end();
            }
        ]);
        $prepared = $results[0];
        $compressedBody = $prepared->getBody();
        $updated = $chain->modifyBeforeSigning(
            $ctx->withRequest($prepared)
        );
        $compressed = (string) $compressedBody;

        self::assertSame($compressedBody, $updated->getBody());
        self::assertSame('payload', \gzdecode($compressed));
        self::assertSame(
            \base64_encode(\md5($compressed, true)),
            $updated->getHeaderLine('Content-MD5')
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
            fn(): RequestInterface =>
                (new HttpChecksumInterceptor())->modifyBeforeSigning(
                    new RequestContext(
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
            $updated->getHeaderLine('Content-MD5')
        );
        self::assertInstanceOf(
            ResourceRequestStream::class,
            $updated->getBody()
        );
        self::assertSame('payload', (string) $updated->getBody());
        self::assertTrue($updated->getBody()->isReplayable());
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
            new RequestContext(
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
            $updated->getHeaderLine('Content-MD5')
        );
        self::assertInstanceOf(
            ResourceRequestStream::class,
            $updated->getBody()
        );
        self::assertSame('payload', (string) $updated->getBody());
        self::assertTrue($updated->getBody()->isReplayable());
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
                new RequestContext(
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
            httpClient: new MockHttpClient(),
            endpointResolver: new StaticEndpointResolver(
                new Endpoint('https://example.test')
            )
        );
    }
}
