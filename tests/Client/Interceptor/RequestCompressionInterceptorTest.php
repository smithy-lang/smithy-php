<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Interceptor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
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

        $updated = $interceptor->modifyBeforeRetryLoop(new RequestContext(
            $this->operation([
                Schema::T_REQUEST_COMPRESSION => ['gzip']
            ]),
            new FakeInput(),
            $this->config(),
            $request
        ));

        self::assertSame(
            'gzip',
            $updated->getHeaderLine('Content-Encoding')
        );
        self::assertSame($body, \gzdecode((string) $updated->getBody()));
        self::assertSame(
            (string) \strlen((string) $updated->getBody()),
            $updated->getHeaderLine('Content-Length')
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
            ->modifyBeforeRetryLoop(new RequestContext(
                $this->operation([
                    Schema::T_REQUEST_COMPRESSION => ['gzip']
                ]),
                new FakeInput(),
                $this->config(),
                $request
            ));

        self::assertInstanceOf(GenericPsrRequest::class, $updated);
        self::assertSame(
            'gzip',
            $updated->getHeaderLine('Content-Encoding')
        );
        self::assertSame(
            (string) \strlen((string) $updated->getBody()),
            $updated->getHeaderLine('Content-Length')
        );
        self::assertSame($body, \gzdecode((string) $updated->getBody()));
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
        ))->modifyBeforeRetryLoop(new RequestContext(
            $this->operation([
                Schema::T_REQUEST_COMPRESSION => ['gzip']
            ]),
            new FakeInput(),
            $this->config(),
            $request
        ));

        self::assertSame(
            'payload',
            \gzdecode((string) $updated->getBody())
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

        $updated = $interceptor->modifyBeforeRetryLoop(new RequestContext(
            $this->operation([
                Schema::T_REQUEST_COMPRESSION => ['gzip']
            ]),
            new FakeInput(),
            $this->config(),
            $request
        ));

        self::assertSame(
            'custom, gzip',
            $updated->getHeaderLine('Content-Encoding')
        );
        self::assertSame(
            'payload',
            \gzdecode((string) $updated->getBody())
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

        $updated = $interceptor->modifyBeforeRetryLoop(new RequestContext(
            $this->operation([
                Schema::T_REQUEST_COMPRESSION => ['gzip']
            ]),
            new FakeInput(),
            $this->config(),
            $request
        ));

        self::assertSame(
            'chunked',
            $updated->getHeaderLine('Transfer-Encoding')
        );
        self::assertFalse($updated->hasHeader('Content-Length'));
        self::assertSame(
            'gzip',
            $updated->getHeaderLine('Content-Encoding')
        );
        self::assertSame(
            'payload',
            \gzdecode((string) $updated->getBody())
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

        $updated = $interceptor->modifyBeforeRetryLoop(new RequestContext(
            $this->operation([
                Schema::T_REQUEST_COMPRESSION => ['gzip']
            ]),
            new FakeInput(),
            $this->config(),
            $request
        ));

        self::assertInstanceOf(GenericPsrRequest::class, $updated);
        self::assertSame(
            'chunked',
            $updated->getHeaderLine('Transfer-Encoding')
        );
        self::assertFalse($updated->hasHeader('Content-Length'));
        self::assertSame(
            'gzip',
            $updated->getHeaderLine('Content-Encoding')
        );
        self::assertSame(
            'payload',
            \gzdecode((string) $updated->getBody())
        );
    }

    public function testSkipsCompressionBelowThreshold(): void
    {
        $interceptor = new RequestCompressionInterceptor(
            minCompressionSizeBytes: 10
        );
        $request = new Request('POST', 'https://example.test', [], 'short');

        $updated = $interceptor->modifyBeforeRetryLoop(new RequestContext(
            $this->operation([
                Schema::T_REQUEST_COMPRESSION => ['gzip']
            ]),
            new FakeInput(),
            $this->config(),
            $request
        ));

        self::assertSame($request, $updated);
        self::assertFalse($updated->hasHeader('Content-Encoding'));
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

        $updated = $interceptor->modifyBeforeRetryLoop(new RequestContext(
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
            $updated->getBody()
        );
        self::assertSame(
            'gzip',
            $updated->getHeaderLine('Content-Encoding')
        );
        $compressed = (string) $updated->getBody();
        self::assertSame(
            (string) \strlen($compressed),
            $updated->getHeaderLine('Content-Length')
        );
        self::assertSame($body, \gzdecode($compressed));
        self::assertSame(0, $stream->tell());
        self::assertTrue($updated->getBody()->isReplayable());

        $updated->getBody()->rewindForRetry();
        self::assertSame(
            $compressed,
            (string) $updated->getBody()
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
            ->modifyBeforeRetryLoop(new RequestContext(
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
            $updated->getHeaderLine('Transfer-Encoding')
        );
        self::assertFalse($updated->hasHeader('Content-Length'));
        self::assertSame(
            'gzip',
            $updated->getHeaderLine('Content-Encoding')
        );
        self::assertSame(
            'payload',
            \gzdecode((string) $updated->getBody())
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
                ->modifyBeforeRetryLoop(new RequestContext(
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
            $updated->getBody()
        );
        self::assertSame(
            'gzip',
            $updated->getHeaderLine('Content-Encoding')
        );
        $compressed = (string) $updated->getBody();
        self::assertSame(
            (string) \strlen($compressed),
            $updated->getHeaderLine('Content-Length')
        );
        self::assertSame('payload', \gzdecode($compressed));
        self::assertTrue($updated->getBody()->isReplayable());
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
                ->modifyBeforeRetryLoop(new RequestContext(
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
            $updated->getHeaderLine('Content-Encoding')
        );
        self::assertSame(
            'payload',
            \gzdecode((string) $updated->getBody())
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
                new RequestContext(
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
                new RequestContext(
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

        $updated = $interceptor->modifyBeforeRetryLoop(new RequestContext(
            $this->operation([
                Schema::T_REQUEST_COMPRESSION => ['gzip']
            ]),
            new FakeInput(),
            $this->config(),
            $request
        ));

        self::assertSame($request, $updated);
        self::assertFalse($updated->hasHeader('Content-Encoding'));
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
            ->modifyBeforeRetryLoop(new RequestContext(
                $this->operation(),
                new FakeInput(),
                $this->config(),
                $request
            ));

        self::assertSame($request, $updated);
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
            ->modifyBeforeRetryLoop(new RequestContext(
                $this->operation([
                    Schema::T_REQUEST_COMPRESSION => ['br']
                ]),
                new FakeInput(),
                $this->config(),
                $request
            ));

        self::assertSame($request, $updated);
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
            ->modifyBeforeRetryLoop(new RequestContext(
                $this->operation([
                    Schema::T_REQUEST_COMPRESSION => ['gzip']
                ]),
                new FakeInput(),
                $this->config(),
                $request
            ));

        self::assertSame($request, $updated);
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
            httpClient: new MockHttpClient(),
            endpointResolver: new StaticEndpointResolver(
                new Endpoint('https://example.test')
            )
        );
    }
}
