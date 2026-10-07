<?php

declare(strict_types=1);

namespace Smithy\Tests\Http\Client;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\StreamInterface;
use Smithy\Async\Async;
use Smithy\Http\Client\Cancellation\{
    Cancellation,
    CancellationInterface
};
use Smithy\Http\Client\{
    CurlClient,
    HttpRequestOptions,
    RequestTrailers
};
use Smithy\Http\Client\Configuration\{
    ConnectConfiguration,
    ConnectionManagerConfiguration,
    HTTPVersionPolicy,
    TLSConfiguration
};
use Smithy\Http\Client\Connection\RequestContext;
use Smithy\Http\Client\Curl\CurlConnectionManager;
use Smithy\Http\Client\Exception\{
    ConnectionAcquireTimeoutException,
    HttpException,
    RequestCancelledException,
    RequestException
};
use Smithy\Http\Message\{
    AsyncRequestStream,
    Request,
    ResourceRequestStream,
    Stream,
    StreamingResponseStream,
    ResponseInterface,
    StringRequestStream
};
use Smithy\Tests\Http\Client\Curl\Utils\{
    FailingCleanupAsyncRequestStreamFixture,
    FailingResourceStreamWrapperFixture,
    FailingStreamFixture,
    H2ServerFixture,
    LocalServerFixture,
    NoProgressStreamFixture,
    PartialReadStreamFixture,
    PollCountingEventLoopFixture,
    TestConnectionFactoryFixture,
    TestConnectionManagerFixture,
    TestStreamFixture,
    TimeoutBoundaryEventLoopFixture
};

#[CoversClass(CurlClient::class)]
final class CurlClientTest extends TestCase
{
    protected function setUp(): void
    {
        if (!\extension_loaded('curl')) {
            self::markTestSkipped('The cURL extension is required.');
        }
    }

    public function testCloseRejectsFutureRequests(): void
    {
        $client = new CurlClient();
        $client->close();
        $client->close();

        $this->expectException(NetworkExceptionInterface::class);
        $this->expectExceptionMessage('HTTP client is closed.');

        $client->sendRequest(new Request('GET', 'https://example.com'));
    }

    #[DataProvider('progressBodyProvider')]
    public function testPollLoopYieldsAfterProductiveBurst(
        string|StreamInterface $body
    ): void
    {
        $server = LocalServerFixture::start(
            responseMode: 'stream-response'
        );
        $eventLoop = new PollCountingEventLoopFixture();
        $client = new CurlClient(eventLoop: $eventLoop);
        $request = new Request(
            'POST',
            $server->endpoint() . '/upload',
            body: $body
        );

        try {
            $responses = $eventLoop->run([
                static fn() => $client->sendRequest($request)
            ]);
        } finally {
            $server->stop();
        }

        $this->assertSame(200, $responses[0]->getStatusCode());
        $this->assertSame(0, $eventLoop->delayCalls);
        $this->assertGreaterThan(0, $eventLoop->scheduleCalls);
        $this->assertGreaterThan(1, $eventLoop->deferCalls);
        $this->assertNotSame([], $eventLoop->scheduledDelays);
        $this->assertLessThanOrEqual(
            65,
            $eventLoop->maxConsecutiveDefers
        );
    }

    /** @return iterable<string, array{string|StreamInterface}> */
    public static function progressBodyProvider(): iterable
    {
        $payload = \str_repeat('payload', 1024 * 1024);

        yield 'selectively scanned upload' => [$payload];
        yield 'callback-observable upload' => [
            new TestStreamFixture(
                $payload,
                true,
                true
            )
        ];
    }

    public function testSendsKnownLengthStreamBody(): void
    {
        $server = LocalServerFixture::start();
        $body = ResourceRequestStream::temp('payload');

        try {
            $response = $this->sendRequest(
                $server,
                $body
            );
            $capture = $server->capture();
        } finally {
            $server->stop();
        }

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('POST', $capture['method']);
        $this->assertSame('/upload', $capture['path']);
        $this->assertSame('payload', $capture['body']);
        $this->assertSame(['7'], $capture['headers']['content-length']);
        $this->assertTrue($body->started());
        $this->assertTrue($body->fullyConsumed());
    }

    public function testSendsLazilyOpenedReplayableResourceBody(): void
    {
        $server = LocalServerFixture::start();
        $openCount = 0;
        $body = ResourceRequestStream::create(
            static function () use (&$openCount) {
                ++$openCount;
                $resource = \fopen('php://temp', 'r+');
                if ($resource === false) {
                    throw new \RuntimeException(
                        'Unable to create test resource.'
                    );
                }
                \fwrite($resource, 'payload');
                \rewind($resource);

                return $resource;
            },
            7
        );

        try {
            $response = $this->sendRequest($server, $body);
            $capture = $server->capture();
        } finally {
            $server->stop();
        }

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('payload', $capture['body']);
        $this->assertSame(['7'], $capture['headers']['content-length']);
        $this->assertSame(1, $openCount);
        $this->assertTrue($body->fullyConsumed());
    }

    public function testSendsResourceBodyWithConfiguredBuffers(): void
    {
        $server = LocalServerFixture::start();
        $payload = \str_repeat('payload', 32 * 1024);
        $body = ResourceRequestStream::temp($payload);
        $options = new HttpRequestOptions(
            uploadBufferSize: 256 * 1024,
            bufferedDownloadBufferSize: 256 * 1024
        );

        try {
            $response = $this->sendRequest(
                $server,
                $body,
                options: $options
            );
            $capture = $server->capture();
        } finally {
            $server->stop();
        }

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($payload, $capture['body']);
        $this->assertTrue($body->fullyConsumed());
    }

    public function testDownloadsWithConfiguredTransportBuffer(): void
    {
        $server = LocalServerFixture::start(
            responseMode: 'stream-response'
        );
        $expected = \str_repeat('0123456789abcdef', 8192);

        try {
            $response = $this->sendRequest(
                $server,
                null,
                options: new HttpRequestOptions(
                    timeout: 2.0,
                    bufferedDownloadBufferSize: 256 * 1024
                )
            );
        } finally {
            $server->stop();
        }

        $this->assertSame($expected, (string) $response->getBody());
    }

    public function testSendsResourceBodyFromCurrentOffset(): void
    {
        $server = LocalServerFixture::start();
        $body = ResourceRequestStream::temp('prefix-payload');
        $body->seek(7);

        try {
            $response = $this->sendRequest($server, $body);
            $capture = $server->capture();
        } finally {
            $server->stop();
        }

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('payload', $capture['body']);
        $this->assertSame(['7'], $capture['headers']['content-length']);
        $this->assertTrue($body->started());
        $this->assertTrue($body->fullyConsumed());
    }

    public function testSendsUnknownLengthStreamBody(): void
    {
        $server = LocalServerFixture::start();

        try {
            $response = $this->sendRequest(
                $server,
                new TestStreamFixture('payload', false, false)
            );
            $capture = $server->capture();
        } finally {
            $server->stop();
        }

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('payload', $capture['body']);
        $this->assertArrayHasKey('transfer-encoding', $capture['headers']);
        $this->assertSame(
            ['chunked'],
            \array_map('strtolower', $capture['headers']['transfer-encoding'])
        );
    }

    public function testExplicitChunkedTransferStreamsKnownLengthBody(): void
    {
        $server = LocalServerFixture::start();

        try {
            $response = $this->sendRequest(
                $server,
                ResourceRequestStream::temp('payload'),
                ['Transfer-Encoding' => 'chunked']
            );
            $capture = $server->capture();
        } finally {
            $server->stop();
        }

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('payload', $capture['body']);
        $this->assertArrayNotHasKey('content-length', $capture['headers']);
        $this->assertSame(
            ['chunked'],
            \array_map('strtolower', $capture['headers']['transfer-encoding'])
        );
    }

    public function testExplicitChunkedTransferSendsEmptyBody(): void
    {
        $server = LocalServerFixture::start();

        try {
            $response = $this->sendRequest(
                $server,
                null,
                ['Transfer-Encoding' => 'chunked']
            );
            $capture = $server->capture();
        } finally {
            $server->stop();
        }

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('', $capture['body']);
        $this->assertSame(
            ['chunked'],
            \array_map(
                'strtolower',
                $capture['headers']['transfer-encoding']
            )
        );
    }

    public function testRejectsRequestTrailers(): void
    {
        $request = new Request(
            'POST',
            'https://example.test/upload',
            body: ResourceRequestStream::temp('payload')
        );
        $options = new HttpRequestOptions(
            trailers: RequestTrailers::fromArray([
                'X-Checksum' => 'value'
            ])
        );
        $client = new CurlClient();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'PHP cURL extension does not expose libcurl request trailer '
                . 'callbacks'
        );

        $client->sendRequest($request, $options);
    }

    public function testSendsStringBodyOnBufferedPath(): void
    {
        $server = LocalServerFixture::start();

        try {
            $response = $this->sendRequest($server, 'payload');
            $capture = $server->capture();
        } finally {
            $server->stop();
        }

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('payload', $capture['body']);
        $this->assertSame(['7'], $capture['headers']['content-length']);
        $this->assertArrayNotHasKey('content-type', $capture['headers']);
        $this->assertArrayNotHasKey(
            'transfer-encoding',
            $capture['headers']
        );
    }

    public function testEmptyBodyDoesNotUpload(): void
    {
        $server = LocalServerFixture::start();

        try {
            $response = $this->sendRequest($server, null);
            $capture = $server->capture();
        } finally {
            $server->stop();
        }

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('', $capture['body']);
        $this->assertArrayNotHasKey('content-length', $capture['headers']);
        $this->assertArrayNotHasKey('transfer-encoding', $capture['headers']);
    }

    public function testRejectsContentLengthMismatch(): void
    {
        $server = LocalServerFixture::start();

        try {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage(
                'Content-Length 7 does not match'
            );

            $this->sendRequest(
                $server,
                ResourceRequestStream::temp('payload-extra'),
                ['Content-Length' => '7']
            );
        } finally {
            $server->stop();
        }
    }

    public function testRejectsConflictingBodyFramingHeaders(): void
    {
        $server = LocalServerFixture::start();

        try {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage(
                'Content-Length and Transfer-Encoding'
            );

            $this->sendRequest(
                $server,
                new TestStreamFixture('payload', false, true),
                [
                    'Content-Length' => '7',
                    'Transfer-Encoding' => 'chunked'
                ]
            );
        } finally {
            $server->stop();
        }
    }

    public function testRejectsMalformedContentLength(): void
    {
        $server = LocalServerFixture::start();

        try {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage(
                'Content-Length must contain one non-negative integer'
            );

            $this->sendRequest(
                $server,
                ResourceRequestStream::temp('payload'),
                ['Content-Length' => '7, 7']
            );
        } finally {
            $server->stop();
        }
    }

    public function testRejectsPrematureRequestBodyEof(): void
    {
        $server = LocalServerFixture::start();
        $body = new TestStreamFixture('payload', false, true);

        try {
            $this->sendRequest(
                $server,
                $body,
                ['Content-Length' => '10']
            );
            $this->fail('Expected the upload to fail.');
        } catch (HttpException $e) {
            $this->assertInstanceOf(
                \UnexpectedValueException::class,
                $e->getPrevious()
            );
            $this->assertStringContainsString(
                'ended after 7 bytes; expected 10',
                $e->getMessage()
            );
            $this->assertTrue($e->requestBodyStarted);
            $this->assertTrue($e->requestBodyFullyConsumed);
        } finally {
            $server->stop();
        }
    }

    public function testRejectsBodyLongerThanDeclaredLength(): void
    {
        $server = LocalServerFixture::start();
        $body = new TestStreamFixture('payload', false, true);

        try {
            $this->sendRequest(
                $server,
                $body,
                ['Content-Length' => '3']
            );
            $this->fail('Expected the upload to fail.');
        } catch (HttpException $e) {
            $this->assertInstanceOf(
                \UnexpectedValueException::class,
                $e->getPrevious()
            );
            $this->assertStringContainsString(
                'contains more bytes than the declared length of 3',
                $e->getMessage()
            );
        } finally {
            $server->stop();
        }
    }

    public function testRejectsUnknownBodyWithZeroContentLength(): void
    {
        $server = LocalServerFixture::start();

        try {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage(
                'Content-Length 0 does not match the non-empty request body'
            );

            $this->sendRequest(
                $server,
                new TestStreamFixture('payload', false, true),
                ['Content-Length' => '0']
            );
        } finally {
            $server->stop();
        }
    }

    public function testRejectsNoProgressReadBeforeEof(): void
    {
        $server = LocalServerFixture::start();

        try {
            $this->sendRequest(
                $server,
                new NoProgressStreamFixture('payload', false, true)
            );
            $this->fail('Expected the upload to fail.');
        } catch (HttpException $e) {
            $this->assertInstanceOf(
                \RuntimeException::class,
                $e->getPrevious()
            );
            $this->assertStringContainsString(
                'returned no data before reaching EOF',
                $e->getMessage()
            );
            $this->assertFalse($e->requestBodyStarted);
        } finally {
            $server->stop();
        }
    }

    public function testSupportsPartialRequestBodyReads(): void
    {
        $server = LocalServerFixture::start();

        try {
            $response = $this->sendRequest(
                $server,
                new PartialReadStreamFixture('payload', true, true)
            );
            $capture = $server->capture();
        } finally {
            $server->stop();
        }

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('payload', $capture['body']);
    }

    public function testLargeStreamedUploadUsesExpectContinue(): void
    {
        $server = LocalServerFixture::start();
        $body = \str_repeat('x', 1024 * 1024 + 1);

        try {
            $response = $this->sendRequest(
                $server,
                ResourceRequestStream::temp($body)
            );
            $capture = $server->capture();
        } finally {
            $server->stop();
        }

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($body, $capture['body']);
        $this->assertSame(
            ['100-continue'],
            \array_map('strtolower', $capture['headers']['expect'])
        );
    }

    public function testServerCanRejectBeforeRequestBodyUpload(): void
    {
        $server = LocalServerFixture::start(
            responseMode: 'reject-before-body'
        );
        $body = new TestStreamFixture(
            \str_repeat('x', 1024 * 1024),
            true,
            true
        );

        try {
            $response = $this->sendRequest(
                $server,
                $body,
                ['Expect' => '100-continue']
            );
            $capture = $server->capture();
        } finally {
            $server->stop();
        }

        $this->assertSame(417, $response->getStatusCode());
        $this->assertSame(0, $body->tell());
        $this->assertSame('', $capture['body']);
    }

    public function testServerCanRejectDuringRequestBodyUpload(): void
    {
        $server = LocalServerFixture::start(
            responseMode: 'reject-after-body-start'
        );
        $length = 16 * 1024 * 1024;
        $body = new TestStreamFixture(
            \str_repeat('x', $length),
            true,
            true
        );

        try {
            $response = $this->sendRequest(
                $server,
                $body,
                ['Expect' => '100-continue']
            );
            $capture = $server->capture();
        } finally {
            $server->stop();
        }

        $this->assertSame(413, $response->getStatusCode());
        $this->assertGreaterThan(0, $body->tell());
        $this->assertLessThan($length, $body->tell());
        $this->assertNotSame('', $capture['body']);
    }

    public function testReturnsRedirectResponsesWithoutFollowingThem(): void
    {
        $server = LocalServerFixture::start(
            responseMode: 'redirect-response'
        );

        try {
            $response = $this->sendRequest($server, null);
        } finally {
            $server->stop();
        }

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/next', $response->getHeaderLine('Location'));
    }

    public function testAutomaticHttpVersionUsesHttp11ForPlainHttp(): void
    {
        $server = LocalServerFixture::start();

        try {
            $response = $this->sendRequest($server, 'payload');
            $capture = $server->capture();
        } finally {
            $server->stop();
        }

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('HTTP/1.1', $capture['protocol']);
    }

    public function testEnforceHttp11UsesHttp11ForPlainHttp(): void
    {
        $server = LocalServerFixture::start();
        $client = new CurlClient(
            connectConfiguration: new ConnectConfiguration(
                versionPolicy: HTTPVersionPolicy::EnforceHTTP_1_1
            )
        );

        try {
            $response = $this->sendRequest($server, 'payload', client: $client);
            $capture = $server->capture();
        } finally {
            $server->stop();
        }

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('HTTP/1.1', $capture['protocol']);
    }

    public function testEnforceHttp2StreamsKnownLengthBody(): void
    {
        $this->requireHttp2();
        $server = H2ServerFixture::start();
        $client = $this->createHttp2Client();

        try {
            $response = $this->sendToEndpoint(
                $server->endpoint(),
                ResourceRequestStream::temp('payload'),
                client: $client
            );
            $capture = $server->capture();
        } finally {
            $server->stop();
        }

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('HTTP/2', $capture['protocol']);
        $this->assertSame('payload', $capture['body']);
    }

    public function testEnforceHttp2StreamsUnknownLengthBody(): void
    {
        $this->requireHttp2();
        $server = H2ServerFixture::start();
        $client = $this->createHttp2Client();

        try {
            $response = $this->sendToEndpoint(
                $server->endpoint(),
                new TestStreamFixture('payload', false, false),
                client: $client
            );
            $capture = $server->capture();
        } finally {
            $server->stop();
        }

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('HTTP/2', $capture['protocol']);
        $this->assertSame('payload', $capture['body']);
    }

    public function testEnforceHttp2StreamsIncrementalBody(): void
    {
        $this->requireHttp2();
        $server = H2ServerFixture::start();
        $client = $this->createHttp2Client();
        $eventLoop = $client->getEventLoop();
        $body = new AsyncRequestStream(
            $eventLoop,
            expectedLength: 7
        );
        $request = new Request(
            'POST',
            $server->endpoint() . '/upload',
            [],
            $body
        );

        try {
            $responses = $eventLoop->run([
                static fn() => $client->sendRequest(
                    $request,
                    new HttpRequestOptions(
                        timeout: 1.0,
                        readTimeout: 0.01
                    )
                ),
                static function () use ($body, $eventLoop): void {
                    $eventLoop->delay(0.02);
                    $body->write('pay');
                    $eventLoop->delay(0.02);
                    $body->write('load');
                    $body->end();
                }
            ]);
            $capture = $server->capture();
        } finally {
            $server->stop();
        }

        $this->assertSame(200, $responses[0]->getStatusCode());
        $this->assertSame('HTTP/2', $capture['protocol']);
        $this->assertSame('payload', $capture['body']);
    }

    public function testAutomaticHttpVersionNegotiatesHttp2OverTls(): void
    {
        $this->requireHttp2();
        $this->requireOpenSsl();
        $server = H2ServerFixture::start(tls: true);
        $client = $this->createAutomaticHttp2Client();

        try {
            $response = $this->sendToEndpoint(
                $server->endpoint(),
                ResourceRequestStream::temp('payload'),
                client: $client
            );
            $capture = $server->capture();
        } finally {
            $server->stop();
        }

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('HTTP/2', $capture['protocol']);
        $this->assertSame('payload', $capture['body']);
    }

    public function testAutomaticHttpVersionStreamsUnknownBodyOverTls(): void
    {
        $this->requireHttp2();
        $this->requireOpenSsl();
        $server = H2ServerFixture::start(tls: true);
        $client = $this->createAutomaticHttp2Client();

        try {
            $response = $this->sendToEndpoint(
                $server->endpoint(),
                new TestStreamFixture('payload', false, false),
                client: $client
            );
            $capture = $server->capture();
        } finally {
            $server->stop();
        }

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('HTTP/2', $capture['protocol']);
        $this->assertSame('payload', $capture['body']);
    }

    public function testTransportErrorReportsUploadState(): void
    {
        $server = LocalServerFixture::start(closeAfterBodyStart: true);
        $body = new TestStreamFixture(
            \str_repeat('x', 1024 * 1024),
            true,
            false
        );

        try {
            $this->sendRequest($server, $body);
            $this->fail('Expected the upload to fail.');
        } catch (HttpException $e) {
            $this->assertTrue($e->requestBodyStarted);
            $this->assertFalse($e->requestBodySeekable);
        } finally {
            $server->stop();
        }
    }

    public function testStreamReadErrorPreservesCauseAndUploadState(): void
    {
        $server = LocalServerFixture::start();
        $cause = new \RuntimeException('source read failed');
        $body = new FailingStreamFixture('payload', $cause);

        try {
            $this->sendRequest($server, $body);
            $this->fail('Expected the upload to fail.');
        } catch (HttpException $e) {
            $this->assertSame($cause, $e->getPrevious());
            $this->assertStringContainsString(
                'source read failed',
                $e->getMessage()
            );
            $this->assertTrue($e->requestBodyStarted);
            $this->assertFalse($e->requestBodyFullyConsumed);
            $this->assertTrue($e->requestBodySeekable);
        } finally {
            $server->stop();
        }
    }

    public function testUserSpaceResourceReadErrorPreservesCause(): void
    {
        $scheme = 'smithy-failing-upload';
        \stream_wrapper_register(
            $scheme,
            FailingResourceStreamWrapperFixture::class
        );
        $resource = \fopen("{$scheme}://body", 'r');
        \assert(\is_resource($resource));
        $body = new ResourceRequestStream($resource);
        $server = LocalServerFixture::start();

        try {
            $this->sendRequest($server, $body);
            $this->fail('Expected the upload to fail.');
        } catch (HttpException $error) {
            $this->assertInstanceOf(
                \RuntimeException::class,
                $error->getPrevious()
            );
            $this->assertSame(
                'Resource stream read failed.',
                $error->getPrevious()?->getMessage()
            );
            $this->assertFalse($error->requestBodyStarted);
        } finally {
            $server->stop();
            $body->close();
            \stream_wrapper_unregister($scheme);
        }
    }

    public function testRejectsRequestWithPreCancelledSignal(): void
    {
        $cause = new \RuntimeException('caller stopped');
        $cancellation = new Cancellation();
        $cancellation->cancel($cause);
        $request = new Request(
            'POST',
            'https://example.invalid/upload',
            [],
            'payload'
        );

        try {
            (new CurlClient())->sendRequest(
                $request,
                new HttpRequestOptions(
                    cancellation: $cancellation
                )
            );
            $this->fail('Expected the request to be cancelled.');
        } catch (RequestCancelledException $error) {
            $this->assertSame($request, $error->request);
            $this->assertSame(
                $cause,
                $error->getPrevious()?->getPrevious()
            );
            $this->assertFalse($error->requestBodyStarted);
        }
    }

    public function testWrapsConnectionManagerAcquisitionFailure(): void
    {
        $cause = new \RuntimeException('manager unavailable');
        $manager = new TestConnectionManagerFixture(
            static function () use ($cause): never {
                throw $cause;
            }
        );
        $body = ResourceRequestStream::temp('payload');
        $request = new Request(
            'POST',
            'https://example.invalid/upload',
            body: $body
        );

        try {
            (new CurlClient(connectionManager: $manager))
                ->sendRequest($request);
            $this->fail('Expected connection acquisition to fail.');
        } catch (HttpException $error) {
            $this->assertSame($request, $error->request);
            $this->assertSame($cause, $error->getPrevious());
            $this->assertStringContainsString(
                'connection acquisition failed',
                $error->getMessage()
            );
            $this->assertFalse($error->requestBodyStarted);
            $this->assertFalse($error->requestBodyFullyConsumed);
            $this->assertTrue($error->requestBodySeekable);
        }
    }

    public function testWrapsAcquireTimeoutWithOriginalRequest(): void
    {
        $manager = new TestConnectionManagerFixture(
            static function (): never {
                throw new ConnectionAcquireTimeoutException('pool full');
            }
        );
        $request = new Request(
            'POST',
            'https://example.invalid/upload?part=1',
            body: 'payload'
        );

        try {
            (new CurlClient(connectionManager: $manager))
                ->sendRequest($request);
            $this->fail('Expected connection acquisition to time out.');
        } catch (NetworkExceptionInterface $error) {
            $this->assertSame($request, $error->getRequest());
            $this->assertInstanceOf(
                ConnectionAcquireTimeoutException::class,
                $error->getPrevious()
            );
        }
    }

    public function testCancellationExceptionIsNotNetworkException(): void
    {
        $request = new Request('GET', 'https://example.com/');
        $error = new RequestCancelledException(
            'cancelled',
            request: $request
        );

        $this->assertInstanceOf(HttpException::class, $error);
        $this->assertNotInstanceOf(NetworkExceptionInterface::class, $error);
    }

    public function testRequestFramingFailureIsPsrRequestException(): void
    {
        $server = LocalServerFixture::start();
        $request = new Request(
            'POST',
            $server->endpoint() . '/upload',
            [
                'Content-Length' => '7',
                'Transfer-Encoding' => 'chunked'
            ],
            'payload'
        );

        try {
            (new CurlClient())->sendRequest($request);
            $this->fail('Expected request framing to fail.');
        } catch (RequestExceptionInterface $error) {
            $this->assertSame($request, $error->getRequest());
            $this->assertStringContainsString(
                'Content-Length and Transfer-Encoding',
                $error->getMessage()
            );
        } finally {
            $server->stop();
        }
    }

    public function testPreservesConnectionManagerHttpException(): void
    {
        $request = new Request(
            'GET',
            'https://example.invalid/'
        );
        $expected = new HttpException(
            'manager transport failure',
            $request
        );
        $manager = new TestConnectionManagerFixture(
            static function () use ($expected): never {
                throw $expected;
            }
        );

        try {
            (new CurlClient(connectionManager: $manager))
                ->sendRequest($request);
            $this->fail('Expected connection acquisition to fail.');
        } catch (HttpException $error) {
            $this->assertSame($expected, $error);
        }
    }

    public function testWrapsConnectionFactoryAcquisitionFailure(): void
    {
        $cause = new \RuntimeException('factory unavailable');
        $factory = new TestConnectionFactoryFixture(
            static function () use ($cause): never {
                throw $cause;
            }
        );
        $manager = new CurlConnectionManager(
            connectionFactory: $factory
        );
        $request = new Request(
            'GET',
            'https://example.invalid/'
        );

        try {
            (new CurlClient(connectionManager: $manager))
                ->sendRequest($request);
            $this->fail('Expected connection acquisition to fail.');
        } catch (HttpException $error) {
            $this->assertSame($request, $error->request);
            $this->assertSame($cause, $error->getPrevious());
            $this->assertStringContainsString(
                'connection acquisition failed',
                $error->getMessage()
            );
        }
    }

    public function testWrapsConnectionReleaseFailure(): void
    {
        $server = LocalServerFixture::start();
        $cause = new \RuntimeException('release failed');
        $inner = new CurlConnectionManager();
        $manager = $this->createThrowingReleaseManager(
            $inner,
            $cause
        );
        $client = new CurlClient(connectionManager: $manager);
        $request = new Request(
            'GET',
            $server->endpoint() . '/health'
        );

        try {
            $client->sendRequest($request);
            $this->fail('Expected connection release to fail.');
        } catch (HttpException $error) {
            $this->assertSame($request, $error->request);
            $this->assertSame($cause, $error->getPrevious());
            $this->assertStringContainsString(
                'connection release failed',
                $error->getMessage()
            );
        } finally {
            $server->stop();
        }

        $this->assertSame(1, $manager->releaseCount);
    }

    public function testWrapsUploadCleanupFailure(): void
    {
        $server = LocalServerFixture::start();
        $cause = new \RuntimeException('unsubscribe failed');
        $client = new CurlClient();
        $eventLoop = $client->getEventLoop();
        $body = new FailingCleanupAsyncRequestStreamFixture(
            $cause,
            $eventLoop,
            7
        );
        $request = new Request(
            'POST',
            $server->endpoint() . '/upload',
            body: $body
        );

        try {
            $eventLoop->run([
                static fn() => $client->sendRequest(
                    $request,
                    new HttpRequestOptions(timeout: 1.0)
                ),
                static function () use (
                    $body,
                    $eventLoop
                ): void
                {
                    $eventLoop->delay(0.02);
                    $body->write('payload');
                    $body->end();
                }
            ]);
            $this->fail('Expected upload cleanup to fail.');
        } catch (HttpException $error) {
            $this->assertSame($request, $error->request);
            $this->assertSame($cause, $error->getPrevious());
            $this->assertStringContainsString(
                'transfer cleanup failed',
                $error->getMessage()
            );
            $this->assertTrue($error->requestBodyStarted);
            $this->assertTrue($error->requestBodyFullyConsumed);
            $this->assertFalse($error->requestBodySeekable);
        } finally {
            $server->stop();
        }
    }

    public function testReleaseFailureDoesNotMaskTransferFailure(): void
    {
        $server = LocalServerFixture::start(
            responseMode: 'truncate-response'
        );
        $cause = new \RuntimeException('release failed');
        $inner = new CurlConnectionManager();
        $manager = $this->createThrowingReleaseManager(
            $inner,
            $cause
        );
        $client = new CurlClient(connectionManager: $manager);
        $request = new Request(
            'GET',
            $server->endpoint() . '/upload'
        );

        try {
            $client->sendRequest($request);
            $this->fail('Expected the transfer to fail.');
        } catch (HttpException $error) {
            $this->assertStringContainsString(
                'cURL error',
                $error->getMessage()
            );
            $this->assertNotSame($cause, $error->getPrevious());
        } finally {
            $server->stop();
        }

        $this->assertSame(1, $manager->releaseCount);
    }

    public function testReleaseFailureDoesNotMaskSetupFailure(): void
    {
        $cause = new \RuntimeException('release failed');
        $inner = new CurlConnectionManager();
        $manager = $this->createThrowingReleaseManager(
            $inner,
            $cause
        );
        $client = new CurlClient(connectionManager: $manager);
        $request = new Request(
            'POST',
            'https://example.invalid/upload',
            [
                'Content-Length' => '7',
                'Transfer-Encoding' => 'chunked'
            ],
            'payload'
        );

        try {
            $client->sendRequest($request);
            $this->fail('Expected request framing to fail.');
        } catch (\InvalidArgumentException $error) {
            $this->assertStringContainsString(
                'Content-Length and Transfer-Encoding',
                $error->getMessage()
            );
        }

        $this->assertSame(1, $manager->releaseCount);
    }

    public function testStreamedReleaseFailureFailsBodyAndTrailers(): void
    {
        $server = LocalServerFixture::start(
            responseMode: 'slow-response'
        );
        $cause = new \RuntimeException('release failed');
        $inner = new CurlConnectionManager();
        $manager = $this->createThrowingReleaseManager(
            $inner,
            $cause
        );
        $client = new CurlClient(connectionManager: $manager);
        $request = new Request(
            'GET',
            $server->endpoint() . '/upload'
        );

        try {
            $response = $client->sendRequest(
                $request,
                new HttpRequestOptions(
                    timeout: 2.0,
                    maxBufferedResponseBytes: 0                )
            );

            try {
                $response->getBody()->getContents();
                $this->fail('Expected response body cleanup to fail.');
            } catch (HttpException $error) {
                $this->assertSame($cause, $error->getPrevious());
                $this->assertStringContainsString(
                    'connection release failed',
                    $error->getMessage()
                );
            }

            try {
                $response->getTrailers()->getHeaders();
                $this->fail('Expected response trailers to fail.');
            } catch (HttpException $trailerError) {
                $this->assertSame($error, $trailerError);
            }
        } finally {
            $server->stop();
        }

        $this->assertSame(1, $manager->releaseCount);
    }

    public function testCancelsRequestWaitingForConnection(): void
    {
        $server = LocalServerFixture::start(responseDelay: 0.1);
        $manager = new CurlConnectionManager(
            new ConnectionManagerConfiguration(maxConcurrency: 1)
        );
        $client = new CurlClient(connectionManager: $manager);
        $activeRequest = new Request(
            'POST',
            $server->endpoint() . '/upload',
            [],
            'payload'
        );
        $queuedRequest = new Request(
            'POST',
            $server->endpoint() . '/upload',
            [],
            'payload'
        );
        $cancellation = new Cancellation();
        $eventLoop = $client->getEventLoop();

        try {
            $eventLoop->run([
                static fn() => $client->sendRequest(
                    $activeRequest,
                    new HttpRequestOptions(timeout: 1.0)
                ),
                static fn() => $client->sendRequest(
                    $queuedRequest,
                    new HttpRequestOptions(
                        timeout: 1.0,
                        cancellation: $cancellation
                    )
                ),
                static function () use (
                    $cancellation,
                    $eventLoop
                ): void
                {
                    $eventLoop->delay(0.02);
                    $cancellation->cancel();
                }
            ]);
            $this->fail('Expected the queued request to be cancelled.');
        } catch (RequestCancelledException $error) {
            $this->assertFalse($error->requestBodyStarted);
        } finally {
            $server->stop();
        }

        $key = (string) RequestContext::fromUri(
            $activeRequest->getUri()
        )->cacheKey();
        $this->assertSame(0, $manager->activeCount($key));
    }

    public function testCancelsActiveRequest(): void
    {
        $server = LocalServerFixture::start(responseDelay: 0.2);
        $client = new CurlClient();
        $request = new Request(
            'POST',
            $server->endpoint() . '/upload',
            [],
            'payload'
        );
        $cancellation = new Cancellation();
        $eventLoop = $client->getEventLoop();

        try {
            $eventLoop->run([
                static fn() => $client->sendRequest(
                    $request,
                    new HttpRequestOptions(
                        timeout: 1.0,
                        cancellation: $cancellation
                    )
                ),
                static function () use (
                    $cancellation,
                    $eventLoop
                ): void
{
                    $eventLoop->delay(0.02);
                    $cancellation->cancel();
                }
            ]);
            $this->fail('Expected the active request to be cancelled.');
        } catch (RequestCancelledException $error) {
            $this->assertSame($request, $error->request);
        } finally {
            $server->stop();
        }
    }

    public function testStreamsIncrementallyProducedRequestBody(): void
    {
        $server = LocalServerFixture::start();
        $client = new CurlClient();
        $eventLoop = $client->getEventLoop();
        $body = new AsyncRequestStream(
            $eventLoop,
            expectedLength: 7
        );
        $request = new Request(
            'POST',
            $server->endpoint() . '/upload',
            [],
            $body
        );
        try {
            $results = $eventLoop->run([
                static fn() => $client->sendRequest(
                    $request,
                    new HttpRequestOptions(timeout: 1.0)
                ),
                static function () use ($body, $eventLoop): void {
                    $eventLoop->delay(0.02);
                    $body->write('pay');
                    $eventLoop->delay(0.02);
                    $body->write('load');
                    $body->end();
                }
            ]);
            $capture = $server->capture();
        } finally {
            $server->stop();
        }

        $this->assertSame(200, $results[0]->getStatusCode());
        $this->assertSame('payload', $capture['body']);
        $this->assertTrue($body->fullyConsumed());
    }

    public function testStreamsBackpressuredRequestBody(): void
    {
        $server = LocalServerFixture::start();
        $client = new CurlClient();
        $eventLoop = $client->getEventLoop();
        $body = new AsyncRequestStream(
            $eventLoop,
            expectedLength: 7,
            highWaterMark: 4
        );
        $request = new Request(
            'POST',
            $server->endpoint() . '/upload',
            [],
            $body
        );

        try {
            $results = $eventLoop->run([
                static fn() => $client->sendRequest(
                    $request,
                    new HttpRequestOptions(timeout: 1.0)
                ),
                static function () use ($body, $eventLoop): void {
                    $eventLoop->delay(0.02);
                    $body->write('payl');
                    $body->write('oad');
                    $body->end();
                }
            ]);
            $capture = $server->capture();
        } finally {
            $server->stop();
        }

        $this->assertSame(200, $results[0]->getStatusCode());
        $this->assertSame('payload', $capture['body']);
        $this->assertTrue($body->fullyConsumed());
    }

    public function testWaitsForAsyncRequestBodyEndAfterFinalChunk(): void
    {
        $server = LocalServerFixture::start();
        $client = new CurlClient();
        $eventLoop = $client->getEventLoop();
        $body = new AsyncRequestStream(
            $eventLoop,
            expectedLength: 7
        );
        $request = new Request(
            'POST',
            $server->endpoint() . '/upload',
            [],
            $body
        );
        try {
            $results = $eventLoop->run([
                static fn() => $client->sendRequest(
                    $request,
                    new HttpRequestOptions(timeout: 1.0)
                ),
                static function () use ($body, $eventLoop): void {
                    $eventLoop->delay(0.02);
                    $body->write('pay');
                    $eventLoop->delay(0.02);
                    $body->write('load');
                    $eventLoop->delay(0.02);
                    $body->end();
                }
            ]);
            $capture = $server->capture();
        } finally {
            $server->stop();
        }

        $this->assertSame(200, $results[0]->getStatusCode());
        $this->assertSame('payload', $capture['body']);
        $this->assertTrue($body->fullyConsumed());
    }

    public function testRejectsAsyncBodyWritesBeyondExpectedLength(): void
    {
        $server = LocalServerFixture::start();
        $client = new CurlClient();
        $eventLoop = $client->getEventLoop();
        $body = new AsyncRequestStream(
            $eventLoop,
            expectedLength: 7
        );
        $request = new Request(
            'POST',
            $server->endpoint() . '/upload',
            [],
            $body
        );
        try {
            $eventLoop->run([
                static fn() => $client->sendRequest(
                    $request,
                    new HttpRequestOptions(timeout: 1.0)
                ),
                static function () use ($body, $eventLoop): void {
                    $eventLoop->delay(0.02);
                    $body->write('payload');
                    $eventLoop->delay(0.02);
                    $body->write('x');
                    $body->end();
                }
            ]);
            $this->fail('Expected the upload to fail.');
        } catch (HttpException $error) {
            $this->assertInstanceOf(
                \LengthException::class,
                $error->getPrevious()
            );
            $this->assertStringContainsString(
                'Async request stream write exceeds expected length of 7 bytes.',
                $error->getMessage()
            );
        } finally {
            $server->stop();
        }
    }

    public function testAsyncBodyFailureAfterFinalChunkPreservesCause(): void
    {
        $server = LocalServerFixture::start();
        $client = new CurlClient();
        $eventLoop = $client->getEventLoop();
        $body = new AsyncRequestStream(
            $eventLoop,
            expectedLength: 7
        );
        $failure = new \RuntimeException('producer failed after final chunk');
        $request = new Request(
            'POST',
            $server->endpoint() . '/upload',
            [],
            $body
        );
        try {
            $eventLoop->run([
                static fn() => $client->sendRequest(
                    $request,
                    new HttpRequestOptions(timeout: 1.0)
                ),
                static function () use (
                    $body,
                    $failure,
                    $eventLoop
                ): void
                {
                    $eventLoop->delay(0.02);
                    $body->write('payload');
                    $eventLoop->delay(0.02);
                    $body->fail($failure);
                }
            ]);
            $this->fail('Expected the producer to fail.');
        } catch (HttpException $error) {
            $this->assertSame($failure, $error->getPrevious());
            $this->assertFalse($error->requestBodyFullyConsumed);
        } finally {
            $server->stop();
        }
    }

    public function testAsyncRequestBodyFailurePreservesCause(): void
    {
        $server = LocalServerFixture::start();
        $client = new CurlClient();
        $eventLoop = $client->getEventLoop();
        $body = new AsyncRequestStream($eventLoop);
        $failure = new \RuntimeException('producer failed');
        $request = new Request(
            'POST',
            $server->endpoint() . '/upload',
            [],
            $body
        );
        try {
            $eventLoop->run([
                static fn() => $client->sendRequest(
                    $request,
                    new HttpRequestOptions(timeout: 1.0)
                ),
                static function () use (
                    $body,
                    $failure,
                    $eventLoop
                ): void
                {
                    $eventLoop->delay(0.02);
                    $body->fail($failure);
                }
            ]);
            $this->fail('Expected the producer to fail.');
        } catch (HttpException $error) {
            $this->assertSame($failure, $error->getPrevious());
            $this->assertFalse($error->requestBodyFullyConsumed);
        } finally {
            $server->stop();
        }
    }

    public function testCancelsRequestWhileUploadIsPaused(): void
    {
        $server = LocalServerFixture::start();
        $manager = new CurlConnectionManager();
        $client = new CurlClient(connectionManager: $manager);
        $eventLoop = $client->getEventLoop();
        $body = new AsyncRequestStream($eventLoop);
        $request = new Request(
            'POST',
            $server->endpoint() . '/upload',
            [],
            $body
        );
        $cancellation = new Cancellation();

        try {
            $eventLoop->run([
                static fn() => $client->sendRequest(
                    $request,
                    new HttpRequestOptions(
                        timeout: 1.0,
                        cancellation: $cancellation
                    )
                ),
                static function () use (
                    $cancellation,
                    $eventLoop
                ): void
{
                    $eventLoop->delay(0.02);
                    $cancellation->cancel();
                }
            ]);
            $this->fail('Expected the paused upload to be cancelled.');
        } catch (RequestCancelledException $error) {
            $this->assertFalse($error->requestBodyStarted);
            $this->assertFalse($error->requestBodyFullyConsumed);
            $this->assertFalse($error->requestBodySeekable);
        } finally {
            $server->stop();
        }

        $key = (string) RequestContext::fromUri(
            $request->getUri()
        )->cacheKey();
        $this->assertSame(0, $manager->activeCount($key));
    }

    public function testStreamsResponseThroughBoundedBuffer(): void
    {
        $server = LocalServerFixture::start(responseMode: 'stream-response');
        $client = new CurlClient();
        $expected = \str_repeat('0123456789abcdef', 8192);

        try {
            $response = $this->sendRequest(
                $server,
                null,
                client: $client,
                options: new HttpRequestOptions(
                    timeout: 2.0,
                    maxBufferedResponseBytes: 0,
                    responseBufferSize: 16 * 1024,
                    bufferedDownloadBufferSize: 256 * 1024,
                    readTimeout: 0.05
                )
            );
            $body = $response->getBody();
            $client->getEventLoop()->delay(0.1);

            $this->assertInstanceOf(StreamingResponseStream::class, $body);
            $this->assertLessThanOrEqual(
                16 * 1024,
                $body->bufferedBytes()
            );

            $contents = '';
            while (!$body->eof()) {
                $contents .= $body->read(4096);
                $this->assertLessThanOrEqual(
                    16 * 1024,
                    $body->bufferedBytes()
                );
            }
        } finally {
            $server->stop();
        }

        $this->assertSame($expected, $contents);
        $this->assertTrue($body->fullyConsumed());
    }


    public function testBoundsBuffersAndRecoversConcurrentLifecycles(): void
    {
        $servers = [
            'read' => LocalServerFixture::start(
                responseMode: 'discard-reuse-response'
            ),
            'discard' => LocalServerFixture::start(
                responseMode: 'discard-reuse-response'
            ),
            'close' => LocalServerFixture::start(
                responseMode: 'discard-reuse-response'
            ),
            'cancel' => LocalServerFixture::start(
                responseMode: 'discard-reuse-response'
            )
        ];
        $manager = new CurlConnectionManager(
            new ConnectionManagerConfiguration(
                maxConcurrency: 8,
                maxIdlePerHost: 8,
                maxIdleTotal: 8
            )
        );
        $client = new CurlClient(connectionManager: $manager);
        $eventLoop = $client->getEventLoop();
        $cancellation = new Cancellation();
        $requests = [];
        $keys = [];
        $bodies = [];
        $responses = [];
        $consumed = [];
        $followUps = [];
        $bodyErrors = [];
        $trailerErrors = [];
        $buffered = [];
        $active = [];
        $aggregateBuffered = 0;
        $ready = 0;
        $release = false;
        $callbacks = [];

        foreach ($servers as $name => $server) {
            $request = new Request(
                'POST',
                $server->endpoint() . '/upload'
            );
            $requests[$name] = $request;
            $keys[$name] = (string) RequestContext::fromUri(
                $request->getUri()
            )->cacheKey();
            $options = new HttpRequestOptions(
                timeout: 2.0,
                    maxBufferedResponseBytes: 0,
                cancellation: $name === 'cancel'
                    ? $cancellation
                    : null,
                responseBufferSize: 16 * 1024,
                readTimeout: 2.0
            );
            $callbacks[] = function () use (
                $name,
                $client,
                $request,
                $options,
                $eventLoop,
                $cancellation,
                &$bodies,
                &$responses,
                &$consumed,
                &$followUps,
                &$bodyErrors,
                &$trailerErrors,
                &$ready,
                &$release
            ): void
            {
                $response = $client->sendRequest($request, $options);
                $body = $response->getBody();
                $responses[$name] = $response;
                $bodies[$name] = $body;
                $ready++;

                while (!$release) {
                    $eventLoop->delay(0.001);
                }

                if ($name === 'read') {
                    $consumed[$name] = \strlen(
                        $body->getContents()
                    );
                } elseif ($name === 'discard') {
                    $response->discardBody();
                    $consumed[$name] = $body->tell();
                } elseif ($name === 'close') {
                    $body->close();

                    try {
                        $response->getTrailers()->getHeaders();
                    } catch (HttpException $error) {
                        $trailerErrors[$name] = $error;
                    }

                    return;
                } else {
                    $cancellation->cancel();

                    try {
                        $body->getContents();
                    } catch (RequestCancelledException $error) {
                        $bodyErrors[$name] = $error;
                    }

                    try {
                        $response->getTrailers()->getHeaders();
                    } catch (RequestCancelledException $error) {
                        $trailerErrors[$name] = $error;
                    }

                    return;
                }

                $followUp = $client->sendRequest(
                    $request,
                    new HttpRequestOptions(timeout: 2.0)
                );
                $followUps[$name] = (string) $followUp->getBody();
            };
        }

        $callbacks[] = function () use (
            $eventLoop,
            $manager,
            $keys,
            &$bodies,
            &$buffered,
            &$active,
            &$aggregateBuffered,
            &$ready,
            &$release
        ): void
        {
            while ($ready < 4) {
                $eventLoop->delay(0.001);
            }

            $eventLoop->delay(0.05);

            foreach ($bodies as $name => $body) {
                $buffered[$name] = $body->bufferedBytes();
                $aggregateBuffered += $buffered[$name];
                $active[$name] = $manager->activeCount(
                    $keys[$name]
                );
            }

            $release = true;
        };

        try {
            $eventLoop->run($callbacks);
        } finally {
            foreach ($servers as $server) {
                $server->stop();
            }
        }

        $this->assertCount(4, $buffered);
        foreach ($buffered as $bytes) {
            $this->assertGreaterThan(0, $bytes);
            $this->assertLessThanOrEqual(16 * 1024, $bytes);
        }
        $this->assertLessThanOrEqual(
            4 * 16 * 1024,
            $aggregateBuffered
        );
        $this->assertEqualsCanonicalizing(
            [
                'cancel' => 1,
                'close' => 1,
                'discard' => 1,
                'read' => 1
            ],
            $active
        );
        $this->assertSame(128 * 1024, $consumed['read']);
        $this->assertSame(128 * 1024, $consumed['discard']);
        $this->assertSame('OK', $followUps['read']);
        $this->assertSame('OK', $followUps['discard']);
        $this->assertInstanceOf(
            HttpException::class,
            $trailerErrors['close']
        );
        $this->assertInstanceOf(
            RequestCancelledException::class,
            $bodyErrors['cancel']
        );
        $this->assertSame(
            $bodyErrors['cancel'],
            $trailerErrors['cancel']
        );

        foreach ($keys as $key) {
            $this->assertSame(0, $manager->activeCount($key));
        }
        $this->assertSame(2, $manager->idleCount());
    }

    public function testBufferedResponseFailsAfterReadInactivity(): void
    {
        $server = LocalServerFixture::start(responseMode: 'slow-response');

        try {
            $this->sendRequest(
                $server,
                null,
                options: new HttpRequestOptions(
                    timeout: 2.0,
                    readTimeout: 0.05
                )
            );
            $this->fail('Expected the response read to time out.');
        } catch (HttpException $error) {
            $this->assertStringContainsString(
                'response read timed out',
                $error->getMessage()
            );
        } finally {
            $server->stop();
        }
    }

    public function testRequestTimesOutBeforeResponseHeaders(): void
    {
        $server = LocalServerFixture::start(responseDelay: 0.2);

        try {
            $this->sendRequest(
                $server,
                null,
                options: new HttpRequestOptions(
                    timeout: 0.05,
                    readTimeout: 1.0
                )
            );
            $this->fail('Expected the request to time out.');
        } catch (HttpException $error) {
            $this->assertStringContainsString(
                'Operation timed out',
                $error->getMessage()
            );
        } finally {
            $server->stop();
        }
    }

    public function testPollsReadyResponseBeforeHeaderTimeoutExpires(): void
    {
        $server = LocalServerFixture::start(responseDelay: 0.01);
        $eventLoop = new TimeoutBoundaryEventLoopFixture(
            pollTimersBeforeDelay: 0,
            delayedPollSeconds: 0.02
        );
        $client = new CurlClient(eventLoop: $eventLoop);
        $request = new Request(
            'POST',
            $server->endpoint() . '/upload',
            body: 'payload'
        );

        try {
            $results = $eventLoop->run([
                static function () use (
                    $client,
                    $request
                ): array
                {
                    $response = $client->sendRequest(
                        $request,
                        new HttpRequestOptions(
                            timeout: 1.0,
                            readTimeout: 1.0
                        )
                    );

                    return [
                        $response->getStatusCode(),
                        $response->getBody()->getContents()
                    ];
                }
            ]);
        } finally {
            $server->stop();
        }

        $this->assertGreaterThan(
            0,
            $eventLoop->delayedPollTimers
        );
        $this->assertSame(200, $results[0][0]);
        $this->assertSame('OK', $results[0][1]);
    }

    public function testStreamedBodyOutlivesWholeRequestTimeout(): void
    {
        $server = LocalServerFixture::start(responseMode: 'slow-response');

        try {
            $response = $this->sendRequest(
                $server,
                null,
                options: new HttpRequestOptions(
                    timeout: 0.05,
                    maxBufferedResponseBytes: 0,
                    readTimeout: 1.0
                )
            );
            $contents = $response->getBody()->getContents();
        } finally {
            $server->stop();
        }

        $this->assertSame('firstsecond', $contents);
    }

    public function testAutomaticallyStreamedBodyOutlivesRequestTimeout(): void
    {
        $server = LocalServerFixture::start(responseMode: 'slow-response');

        try {
            $response = $this->sendRequest(
                $server,
                null,
                options: new HttpRequestOptions(
                    timeout: 0.05,
                    maxBufferedResponseBytes: 0,
                    readTimeout: 1.0
                )
            );
            $contents = $response->getBody()->getContents();
        } finally {
            $server->stop();
        }

        $this->assertInstanceOf(
            StreamingResponseStream::class,
            $response->getBody()
        );
        $this->assertSame('firstsecond', $contents);
    }

    public function testStreamedBodyFailsAfterReadInactivity(): void
    {
        $server = LocalServerFixture::start(responseMode: 'slow-response');

        try {
            $response = $this->sendRequest(
                $server,
                null,
                options: new HttpRequestOptions(
                    timeout: 2.0,
                    maxBufferedResponseBytes: 0,
                    readTimeout: 0.05
                )
            );
            $body = $response->getBody();
            $this->assertSame('first', $body->read(5));

            try {
                $body->read(1);
                $this->fail('Expected the response read to time out.');
            } catch (HttpException $error) {
                $this->assertStringContainsString(
                    'response read timed out',
                    $error->getMessage()
                );
            }

            try {
                $response->getTrailers()->getHeaders();
                $this->fail('Expected response trailers to fail.');
            } catch (HttpException $trailerError) {
                $this->assertSame($error, $trailerError);
            }
        } finally {
            $server->stop();
        }
    }

    public function testStreamsZeroLengthResponse(): void
    {
        $server = LocalServerFixture::start(responseMode: 'empty-response');

        try {
            $response = $this->sendRequest(
                $server,
                null,
                options: new HttpRequestOptions(
                    timeout: 1.0,
                    maxBufferedResponseBytes: 0
                )
            );
            $body = $response->getBody();
            $contents = $body->read(1);
        } finally {
            $server->stop();
        }

        $this->assertSame('', $contents);
        $this->assertTrue($body->eof());
    }

    public function testDiscardsPartiallyReadStreamedResponse(): void
    {
        $server = LocalServerFixture::start(responseMode: 'slow-response');

        try {
            $response = $this->sendRequest(
                $server,
                null,
                options: new HttpRequestOptions(
                    timeout: 2.0,
                    maxBufferedResponseBytes: 0,
                    readTimeout: 1.0
                )
            );
            $body = $response->getBody();
            $this->assertSame('first', $body->read(5));

            $response->discardBody();
        } finally {
            $server->stop();
        }

        $this->assertSame(11, $body->tell());
        $this->assertTrue($body->fullyConsumed());
        $this->assertSame([], $response->getTrailers()->getHeaders());
    }

    public function testDiscardResumesBackpressureAndReusesConnection(): void
    {
        $server = LocalServerFixture::start(
            responseMode: 'discard-reuse-response'
        );
        $manager = new CurlConnectionManager();
        $client = new CurlClient(connectionManager: $manager);
        $request = new Request(
            'POST',
            $server->endpoint() . '/upload'
        );
        $options = new HttpRequestOptions(
            timeout: 2.0,
                    maxBufferedResponseBytes: 0,
            responseBufferSize: 16 * 1024,
            readTimeout: 2.0
        );
        $key = (string) RequestContext::fromUri(
            $request->getUri()
        )->cacheKey();

        try {
            $response = $client->sendRequest($request, $options);
            $client->getEventLoop()->delay(0.05);
            $body = $response->getBody();

            $this->assertSame(1, $manager->activeCount($key));
            $this->assertLessThanOrEqual(
                16 * 1024,
                $body->bufferedBytes()
            );

            $response->discardBody();

            $this->assertSame(0, $manager->activeCount($key));
            $this->assertSame(1, $manager->idleCount());
            $this->assertSame(
                'complete',
                $response->getTrailers()->getHeaderLine(
                    'X-Discarded'
                )
            );

            $second = $client->sendRequest(
                $request,
                new HttpRequestOptions(timeout: 2.0)
            );
        } finally {
            $server->stop();
        }

        $this->assertSame('OK', (string) $second->getBody());
        $this->assertSame(128 * 1024, $body->tell());
        $this->assertTrue($body->fullyConsumed());
    }

    public function testDiscardPropagatesTransportFailure(): void
    {
        $server = LocalServerFixture::start(responseMode: 'truncate-response');

        try {
            $response = $this->sendRequest(
                $server,
                null,
                options: new HttpRequestOptions(
                    timeout: 2.0,
                    maxBufferedResponseBytes: 0                )
            );

            try {
                $response->discardBody();
                $this->fail('Expected response discard to fail.');
            } catch (HttpException $error) {
                $this->assertStringContainsString(
                    'cURL error',
                    $error->getMessage()
                );
            }

            try {
                $response->getTrailers()->getHeaders();
                $this->fail('Expected response trailers to fail.');
            } catch (HttpException $trailerError) {
                $this->assertSame($error, $trailerError);
            }
        } finally {
            $server->stop();
        }
    }

    public function testDiscardPreservesDecodedBodyLimit(): void
    {
        $this->requireZlib();
        $server = LocalServerFixture::start(
            responseMode: 'slow-gzip-response'
        );

        try {
            $response = $this->sendRequest(
                $server,
                null,
                options: new HttpRequestOptions(
                    timeout: 2.0,
                    maxBufferedResponseBytes: 0,
                    maxResponseBodyBytes: 1024
                )
            );

            $this->expectException(HttpException::class);
            $this->expectExceptionMessage(
                'maximum size of 1024 bytes'
            );
            $response->discardBody();
        } finally {
            $server->stop();
        }
    }

    public function testClosingStreamedResponseReleasesExchange(): void
    {
        $server = LocalServerFixture::start(responseMode: 'slow-response');
        $manager = new CurlConnectionManager();
        $client = new CurlClient(connectionManager: $manager);
        $request = new Request(
            'POST',
            $server->endpoint() . '/upload'
        );
        $eventLoop = $client->getEventLoop();
        $trailers = null;

        try {
            $eventLoop->run([
                static function () use (
                    $client,
                    $request,
                    &$trailers
                ): void
                {
                    $response = $client->sendRequest(
                        $request,
                        new HttpRequestOptions(
                            timeout: 2.0,
                            maxBufferedResponseBytes: 0
                        )
                    );
                    $trailers = $response->getTrailers();
                    $response->getBody()->close();
                }
            ]);
        } finally {
            $server->stop();
        }

        $key = (string) RequestContext::fromUri(
            $request->getUri()
        )->cacheKey();
        $this->assertSame(0, $manager->activeCount($key));
        $this->assertNotNull($trailers);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage(
            'closed before transfer completed'
        );
        $trailers->getHeaders();
    }

    public function testAbandoningStreamedResponseReleasesExchange(): void
    {
        $server = LocalServerFixture::start(responseMode: 'slow-response');
        $manager = new CurlConnectionManager();
        $client = new CurlClient(connectionManager: $manager);
        $request = new Request(
            'POST',
            $server->endpoint() . '/upload'
        );

        try {
            $response = $client->sendRequest(
                $request,
                new HttpRequestOptions(
                    timeout: 2.0,
                    maxBufferedResponseBytes: 0                )
            );
            $key = (string) RequestContext::fromUri(
                $request->getUri()
            )->cacheKey();

            $this->assertSame(1, $manager->activeCount($key));

            unset($response);

            $this->assertSame(0, $manager->activeCount($key));
            $this->assertSame(0, $manager->idleCount());
        } finally {
            $server->stop();
        }
    }

    public function testStreamedBodyOutlivesResponse(): void
    {
        $server = LocalServerFixture::start(responseMode: 'slow-response');
        $manager = new CurlConnectionManager();
        $client = new CurlClient(connectionManager: $manager);
        $request = new Request(
            'POST',
            $server->endpoint() . '/upload'
        );

        try {
            $response = $client->sendRequest(
                $request,
                new HttpRequestOptions(
                    timeout: 2.0,
                    maxBufferedResponseBytes: 0                )
            );
            $body = $response->getBody();
            $key = (string) RequestContext::fromUri(
                $request->getUri()
            )->cacheKey();

            unset($response);

            $this->assertSame(1, $manager->activeCount($key));
            $this->assertSame('firstsecond', $body->getContents());
            $this->assertSame(0, $manager->activeCount($key));
            $this->assertTrue($body->fullyConsumed());
        } finally {
            $server->stop();
        }
    }

    public function testCancellationAfterHeadersFailsResponseBody(): void
    {
        $server = LocalServerFixture::start(responseMode: 'slow-response');
        $client = new CurlClient();
        $cancellation = new Cancellation();
        $request = new Request(
            'POST',
            $server->endpoint() . '/upload'
        );
        $eventLoop = $client->getEventLoop();
        $response = null;

        try {
            $eventLoop->run([
                static function () use (
                    $client,
                    $cancellation,
                    $request,
                    &$response
                ): void
                {
                    $response = $client->sendRequest(
                        $request,
                        new HttpRequestOptions(
                            timeout: 2.0,
                            maxBufferedResponseBytes: 0,
                            cancellation: $cancellation
                        )
                    );
                },
                static function () use (
                    $cancellation,
                    $eventLoop
                ): void
                {
                    $eventLoop->delay(0.05);
                    $cancellation->cancel();
                }
            ]);

            $this->assertNotNull($response);
            $body = $response->getBody();
            $this->assertSame('first', $body->read(5));

            try {
                $body->read(1);
                $this->fail('Expected response body cancellation.');
            } catch (RequestCancelledException $error) {
                $this->assertStringContainsString(
                    'cancelled',
                    \strtolower($error->getMessage())
                );
            }

            try {
                $response->getTrailers()->getHeaders();
                $this->fail('Expected response trailers to fail.');
            } catch (RequestCancelledException $trailerError) {
                $this->assertSame($error, $trailerError);
            }
        } finally {
            $server->stop();
        }
    }

    public function testTransportErrorAfterHeadersFailsResponseBody(): void
    {
        $server = LocalServerFixture::start(responseMode: 'truncate-response');

        try {
            $response = $this->sendRequest(
                $server,
                null,
                options: new HttpRequestOptions(
                    timeout: 1.0,
                    maxBufferedResponseBytes: 0                )
            );
            $body = $response->getBody();
            $this->assertSame('part', $body->read(4));

            try {
                $body->read(1);
                $this->fail('Expected response body failure.');
            } catch (HttpException $error) {
                $this->assertStringContainsString(
                    'cURL error',
                    $error->getMessage()
                );
            }

            try {
                $response->getTrailers()->getHeaders();
                $this->fail('Expected response trailers to fail.');
            } catch (HttpException $trailerError) {
                $this->assertSame($error, $trailerError);
            }
        } finally {
            $server->stop();
        }
    }

    public function testDecodesBufferedGzipResponse(): void
    {
        $this->requireZlib();
        $server = LocalServerFixture::start(responseMode: 'gzip-response');
        $expected = \str_repeat('decoded-body-', 1024);

        try {
            $response = $this->sendRequest($server, null);
            $capture = $server->capture();
        } finally {
            $server->stop();
        }

        $this->assertSame($expected, (string) $response->getBody());
        $this->assertFalse($response->hasHeader('Content-Encoding'));
        $this->assertFalse($response->hasHeader('Content-Length'));
        $this->assertStringContainsString(
            'gzip',
            \strtolower($capture['headers']['accept-encoding'][0])
        );
    }

    public function testDecodesStreamedDeflateResponse(): void
    {
        $this->requireZlib();
        $server = LocalServerFixture::start(responseMode: 'deflate-response');
        $expected = \str_repeat('decoded-body-', 1024);

        try {
            $response = $this->sendRequest(
                $server,
                null,
                options: new HttpRequestOptions(
                    timeout: 2.0,
                    maxBufferedResponseBytes: 0                )
            );
            $contents = $response->getBody()->getContents();
        } finally {
            $server->stop();
        }

        $this->assertSame($expected, $contents);
        $this->assertFalse($response->hasHeader('Content-Encoding'));
        $this->assertFalse($response->hasHeader('Content-Length'));
    }

    public function testDecodesChunkedGzipResponse(): void
    {
        $this->requireZlib();
        $server = LocalServerFixture::start(
            responseMode: 'chunked-gzip-response'
        );
        $expected = \str_repeat('decoded-body-', 1024);

        try {
            $response = $this->sendRequest($server, null);
        } finally {
            $server->stop();
        }

        $this->assertSame($expected, (string) $response->getBody());
        $this->assertFalse($response->hasHeader('Content-Encoding'));
    }

    public function testHonorsCustomAcceptEncodingWhileDecoding(): void
    {
        $this->requireZlib();
        $server = LocalServerFixture::start(responseMode: 'gzip-response');
        $expected = \str_repeat('decoded-body-', 1024);

        try {
            $response = $this->sendRequest(
                $server,
                null,
                ['Accept-Encoding' => 'gzip']
            );
            $capture = $server->capture();
        } finally {
            $server->stop();
        }

        $this->assertSame($expected, (string) $response->getBody());
        $this->assertSame(
            ['gzip'],
            $capture['headers']['accept-encoding']
        );
    }

    public function testTreatsEmptyAcceptEncodingAsIdentity(): void
    {
        $server = LocalServerFixture::start();

        try {
            $this->sendRequest(
                $server,
                null,
                ['Accept-Encoding' => '']
            );
            $capture = $server->capture();
        } finally {
            $server->stop();
        }

        $this->assertSame(
            ['identity'],
            $capture['headers']['accept-encoding']
        );
    }

    public function testPreservesRawResponseWhenDecodingIsDisabled(): void
    {
        $this->requireZlib();
        $server = LocalServerFixture::start(responseMode: 'gzip-response');
        $expected = \str_repeat('decoded-body-', 1024);

        try {
            $response = $this->sendRequest(
                $server,
                null,
                options: new HttpRequestOptions(
                    timeout: 2.0,
                    decodeContent: false
                )
            );
            $capture = $server->capture();
        } finally {
            $server->stop();
        }

        $encoded = (string) $response->getBody();
        $this->assertSame($expected, \gzdecode($encoded));
        $this->assertSame('gzip', $response->getHeaderLine(
            'Content-Encoding'
        ));
        $this->assertSame(
            (string) \strlen($encoded),
            $response->getHeaderLine('Content-Length')
        );
        $this->assertSame(
            ['identity'],
            $capture['headers']['accept-encoding']
        );
    }

    public function testPreservesCustomAcceptEncodingWithoutDecoding(): void
    {
        $this->requireZlib();
        $server = LocalServerFixture::start(responseMode: 'gzip-response');
        $expected = \str_repeat('decoded-body-', 1024);

        try {
            $response = $this->sendRequest(
                $server,
                null,
                ['Accept-Encoding' => 'gzip'],
                options: new HttpRequestOptions(
                    timeout: 2.0,
                    decodeContent: false
                )
            );
            $capture = $server->capture();
        } finally {
            $server->stop();
        }

        $this->assertSame(
            $expected,
            \gzdecode((string) $response->getBody())
        );
        $this->assertSame(
            ['gzip'],
            $capture['headers']['accept-encoding']
        );
    }

    public function testAllowsResponseAtDecodedBodyLimit(): void
    {
        $this->requireZlib();
        $server = LocalServerFixture::start(responseMode: 'gzip-response');
        $expected = \str_repeat('decoded-body-', 1024);

        try {
            $response = $this->sendRequest(
                $server,
                null,
                options: new HttpRequestOptions(
                    timeout: 2.0,
                    maxResponseBodyBytes: \strlen($expected)
                )
            );
        } finally {
            $server->stop();
        }

        $this->assertSame($expected, (string) $response->getBody());
    }

    public function testDecodedBodyLimitFailsStreamAndTrailers(): void
    {
        $this->requireZlib();
        $server = LocalServerFixture::start(
            responseMode: 'slow-gzip-response'
        );

        try {
            $response = $this->sendRequest(
                $server,
                null,
                options: new HttpRequestOptions(
                    timeout: 2.0,
                    maxBufferedResponseBytes: 0,
                    maxResponseBodyBytes: 1024
                )
            );

            try {
                $response->getBody()->read(1);
                $this->fail('Expected the response body limit to fail.');
            } catch (HttpException $error) {
                $this->assertStringContainsString(
                    'maximum size of 1024 bytes',
                    $error->getMessage()
                );
            }

            try {
                $response->getTrailers()->getHeaders();
                $this->fail('Expected response trailers to fail.');
            } catch (HttpException $trailerError) {
                $this->assertSame($error, $trailerError);
            }
        } finally {
            $server->stop();
        }
    }

    public function testStreamsHttp11ResponseTrailers(): void
    {
        $server = LocalServerFixture::start(responseMode: 'response-trailers');

        try {
            $response = $this->sendRequest(
                $server,
                null,
                options: new HttpRequestOptions(
                    timeout: 2.0,
                    maxBufferedResponseBytes: 0                )
            );
            $trailers = $response->getTrailers();

            try {
                $trailers->getHeaders();
                $this->fail('Expected response trailers to be pending.');
            } catch (\LogicException $error) {
                $this->assertStringContainsString(
                    'response body completes',
                    $error->getMessage()
                );
            }

            $this->assertSame(
                'firstsecond',
                $response->getBody()->getContents()
            );
            $this->assertSame(
                'first, second',
                $trailers->getHeaderLine('X-Checksum')
            );
        } finally {
            $server->stop();
        }
    }

    public function testResponseWithoutTrailersCompletesEmptyState(): void
    {
        $server = LocalServerFixture::start();

        try {
            $response = $this->sendRequest($server, null);
        } finally {
            $server->stop();
        }

        $this->assertTrue($response->getTrailers()->isComplete());
        $this->assertSame([], $response->getTrailers()->getHeaders());
    }

    public function testKeepsThresholdBufferedDownloadInNativeString(): void
    {
        $server = LocalServerFixture::start(
            responseMode: 'threshold-response'
        );
        $client = new CurlClient();

        try {
            $responses = Async::all(
                static fn() => $client->sendRequest(
                    new Request(
                        'GET',
                        $server->endpoint() . '/download'
                    ),
                    new HttpRequestOptions(timeout: 2.0)
                )
            );
        } finally {
            $server->stop();
        }

        $body = $responses[0]->getBody();
        $this->assertInstanceOf(Stream::class, $body);
        $this->assertSame(1024 * 1024, $body->getSize());
        $this->assertSame(
            \hash(
                'sha256',
                \str_repeat('0123456789abcdef', 65536)
            ),
            \hash('sha256', (string) $body)
        );
    }

    public function testKeepsSmallBufferedDownloadInNativeString(): void
    {
        $server = LocalServerFixture::start(
            responseMode: 'large-response'
        );
        $client = new CurlClient();

        try {
            $responses = Async::all(
                static fn() => $client->sendRequest(
                    new Request(
                        'GET',
                        $server->endpoint() . '/download'
                    ),
                    new HttpRequestOptions(timeout: 2.0)
                )
            );
        } finally {
            $server->stop();
        }

        $body = $responses[0]->getBody();
        $this->assertInstanceOf(Stream::class, $body);
        $this->assertSame(768 * 1024, $body->getSize());
        $this->assertSame(
            \hash(
                'sha256',
                \str_repeat('0123456789abcdef', 49152)
            ),
            \hash('sha256', (string) $body)
        );
    }

    public function testStreamsKnownDownloadAboveAutomaticLimit(): void
    {
        $server = LocalServerFixture::start(
            responseMode: 'above-threshold-response'
        );
        $client = new CurlClient();

        try {
            $responses = Async::all(
                static fn() => $client->sendRequest(
                    new Request(
                        'GET',
                        $server->endpoint() . '/download'
                    ),
                    new HttpRequestOptions(timeout: 2.0)
                )
            );
            $body = $responses[0]->getBody();
            $contents = $body->getContents();
        } finally {
            $server->stop();
        }

        $this->assertInstanceOf(StreamingResponseStream::class, $body);
        $this->assertSame(1024 * 1024 + 16, \strlen($contents));
        $this->assertSame(
            \hash(
                'sha256',
                \str_repeat('0123456789abcdef', 65537)
            ),
            \hash('sha256', $contents)
        );
    }

    public function testStreamsUnknownLengthDownloadAutomatically(): void
    {
        $server = LocalServerFixture::start(
            responseMode: 'unknown-length-response'
        );
        $client = new CurlClient();

        try {
            $responses = Async::all(
                static fn() => $client->sendRequest(
                    new Request(
                        'GET',
                        $server->endpoint() . '/download'
                    ),
                    new HttpRequestOptions(timeout: 2.0)
                )
            );
            $body = $responses[0]->getBody();
            $contents = $body->getContents();
        } finally {
            $server->stop();
        }

        $this->assertInstanceOf(StreamingResponseStream::class, $body);
        $this->assertSame(64 * 1024, \strlen($contents));
        $this->assertSame(
            \hash(
                'sha256',
                \str_repeat('0123456789abcdef', 4096)
            ),
            \hash('sha256', $contents)
        );
    }

    public function testStreamsHttp2Response(): void
    {
        $this->requireHttp2();
        $server = H2ServerFixture::start();
        $client = $this->createHttp2Client();

        try {
            $response = $this->sendToEndpoint(
                $server->endpoint(),
                null,
                client: $client,
                options: new HttpRequestOptions(
                    timeout: 1.0,
                    maxBufferedResponseBytes: 0                )
            );
            $contents = $response->getBody()->getContents();
        } finally {
            $server->stop();
        }

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('', $contents);
    }

    public function testStreamsHttp2ResponseTrailers(): void
    {
        $this->requireHttp2();
        $server = H2ServerFixture::start(responseTrailers: true);
        $client = $this->createHttp2Client();

        try {
            $response = $this->sendToEndpoint(
                $server->endpoint(),
                null,
                client: $client,
                options: new HttpRequestOptions(
                    timeout: 2.0,
                    maxBufferedResponseBytes: 0                )
            );
            $trailers = $response->getTrailers();
            $contents = $response->getBody()->getContents();
        } finally {
            $server->stop();
        }

        $this->assertSame('OK', $contents);
        $this->assertSame(
            ['first', 'second'],
            $trailers->getHeader('x-checksum')
        );
        $this->assertSame('h2', $trailers->getHeaderLine('x-request-id'));
    }

    public function testDiscardsHttp2ResponseAndReceivesTrailers(): void
    {
        $this->requireHttp2();
        $server = H2ServerFixture::start(responseTrailers: true);
        $client = $this->createHttp2Client();

        try {
            $response = $this->sendToEndpoint(
                $server->endpoint(),
                null,
                client: $client,
                options: new HttpRequestOptions(
                    timeout: 2.0,
                    maxBufferedResponseBytes: 0                )
            );
            $body = $response->getBody();

            $response->discardBody();
        } finally {
            $server->stop();
        }

        $this->assertSame(2, $body->tell());
        $this->assertTrue($body->fullyConsumed());
        $this->assertSame(
            ['first', 'second'],
            $response->getTrailers()->getHeader('x-checksum')
        );
    }

    public function testRejectsResponseBufferSmallerThanCurlChunk(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Response buffer size must be at least 16384 bytes'
        );

        new HttpRequestOptions(responseBufferSize: 16383);
    }

    public function testUses64KiBResponseBufferByDefault(): void
    {
        $this->assertSame(
            64 * 1024,
            (new HttpRequestOptions())->responseBufferSize
        );
    }

    public function testUses1MiBAutomaticResponseBufferLimitByDefault(): void
    {
        $this->assertSame(
            1024 * 1024,
            (new HttpRequestOptions())->maxBufferedResponseBytes
        );
    }

    public function testUses128KiBUploadBufferByDefault(): void
    {
        $this->assertSame(
            128 * 1024,
            (new HttpRequestOptions())->uploadBufferSize
        );
    }

    public function testUses256KiBBufferedDownloadBufferByDefault(): void
    {
        $this->assertSame(
            256 * 1024,
            (new HttpRequestOptions())->bufferedDownloadBufferSize
        );
    }

    #[DataProvider('invalidUploadBufferSizeProvider')]
    public function testRejectsInvalidUploadBufferSize(int $size): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Upload buffer size must be between 16384 and 2097152 bytes'
        );

        new HttpRequestOptions(uploadBufferSize: $size);
    }

    /** @return iterable<string, array{int}> */
    public static function invalidUploadBufferSizeProvider(): iterable
    {
        yield 'below libcurl minimum' => [16 * 1024 - 1];
        yield 'above libcurl maximum' => [2 * 1024 * 1024 + 1];
    }

    #[DataProvider('invalidBufferedDownloadBufferSizeProvider')]
    public function testRejectsInvalidBufferedDownloadBufferSize(
        int $size
    ): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Buffered download buffer size must be 0 or between '
                . '16384 and 10485760 bytes'
        );

        new HttpRequestOptions(bufferedDownloadBufferSize: $size);
    }

    /** @return iterable<string, array{int}> */
    public static function invalidBufferedDownloadBufferSizeProvider(): iterable
    {
        yield 'below libcurl minimum' => [16 * 1024 - 1];
        yield 'above libcurl maximum' => [10 * 1024 * 1024 + 1];
    }

    public function testRejectsInvalidTimeouts(): void
    {
        try {
            new HttpRequestOptions(timeout: -1.0);
            $this->fail('Expected the request timeout to be rejected.');
        } catch (\InvalidArgumentException $error) {
            $this->assertStringContainsString(
                'finite and non-negative',
                $error->getMessage()
            );
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('finite and non-negative');

        new HttpRequestOptions(readTimeout: \INF);
    }

    public function testRejectsNegativeResponseBodyLimit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Maximum response body size must be non-negative'
        );

        new HttpRequestOptions(maxResponseBodyBytes: -1);
    }

    public function testRejectsNegativeAutomaticResponseBufferLimit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Maximum automatically buffered response size must be '
                . 'non-negative'
        );

        new HttpRequestOptions(maxBufferedResponseBytes: -1);
    }

    public function testUsesConfiguredDefaultRequestOptions(): void
    {
        $server = LocalServerFixture::start(responseDelay: 0.2);
        $client = new CurlClient(
            defaultOptions: new HttpRequestOptions(
                timeout: 0.05            )
        );
        $request = new Request(
            'POST',
            $server->endpoint() . '/upload',
            [],
            'payload'
        );

        try {
            Async::all(static fn() => $client->sendRequest($request));
            $this->fail('Expected the request to time out.');
        } catch (HttpException $e) {
            $this->assertStringContainsString('cURL error', $e->getMessage());
        } finally {
            $server->stop();
        }
    }

    public function testExplicitRequestOptionsOverrideDefaults(): void
    {
        $server = LocalServerFixture::start(responseDelay: 0.1);
        $client = new CurlClient(
            defaultOptions: new HttpRequestOptions(timeout: 0.01)
        );
        $request = new Request(
            'POST',
            $server->endpoint() . '/upload',
            [],
            'payload'
        );

        try {
            $responses = Async::all(
                static fn() => $client->sendRequest(
                    $request,
                    new HttpRequestOptions(timeout: 1.0)
                )
            );
        } finally {
            $server->stop();
        }

        $this->assertSame(200, $responses[0]->getStatusCode());
    }

    public function testAsyncMapRetainsEveryHttpResult(): void
    {
        $servers = [];
        foreach (\range(0, 5) as $index) {
            $servers[$index] = LocalServerFixture::start(
                responseMode: 'empty-response'
            );
        }
        $client = new CurlClient();
        Async::configure($client->getEventLoop());

        try {
            $results = Async::map(
                $servers,
                static function (
                    LocalServerFixture $server,
                    int $index
                ) use ($client): int
                {
                    $response = $client->sendRequest(
                        new Request(
                            'GET',
                            $server->endpoint() . "/{$index}"
                        ),
                        new HttpRequestOptions(timeout: 2.0)
                    );

                    return $response->getStatusCode();
                },
                concurrency: \count($servers)
            );
        } finally {
            foreach ($servers as $server) {
                $server->stop();
            }
        }

        $this->assertSame(
            \array_fill(0, \count($servers), 200),
            $results
        );
    }

    /**
     * Send a request to a local HTTP/1.1 server.
     *
     * @param array<string, string> $headers
     */
    private function sendRequest(
        LocalServerFixture $server,
        string|StreamInterface|null $body,
        array $headers = [],
        ?CurlClient $client = null,
        ?HttpRequestOptions $options = null
    ): ResponseInterface
    {
        return $this->sendToEndpoint(
            $server->endpoint(),
            $body,
            $headers,
            $client,
            $options
        );
    }

    /**
     * Send a request to an HTTP endpoint.
     *
     * @param array<string, string> $headers
     */
    private function sendToEndpoint(
        string $endpoint,
        string|StreamInterface|null $body,
        array $headers = [],
        ?CurlClient $client = null,
        ?HttpRequestOptions $options = null
    ): ResponseInterface
    {
        $request = new Request(
            'POST',
            $endpoint . '/upload',
            $headers,
            $body
        );
        $client ??= new CurlClient();
        $options ??= new HttpRequestOptions(timeout: 5.0);
        $responses = Async::all(
            static fn() => $client->sendRequest(
                $request,
                $options
            )
        );

        return $responses[0];
    }

    private function createThrowingReleaseManager(
        CurlConnectionManager $inner,
        \Throwable $releaseError
    ): TestConnectionManagerFixture
    {
        return new TestConnectionManagerFixture(
            static fn(
                RequestContext $requestContext,
                ConnectConfiguration $connectConfig,
                ?CancellationInterface $cancellation
            ) => $inner->lease(
                $requestContext,
                $connectConfig,
                $cancellation
            ),
            static function ($exchange) use (
                $inner,
                $releaseError
            ): never
            {
                $inner->release($exchange);

                throw $releaseError;
            },
            $inner->getConfiguration()
        );
    }

    private function createHttp2Client(): CurlClient
    {
        return new CurlClient(
            connectConfiguration: new ConnectConfiguration(
                versionPolicy: HTTPVersionPolicy::EnforceHTTP_2
            )
        );
    }

    private function createAutomaticHttp2Client(
        ?CurlConnectionManager $manager = null
    ): CurlClient
    {
        return new CurlClient(
            connectionManager: $manager,
            connectConfiguration: new ConnectConfiguration(
                tlsConfiguration: new TLSConfiguration(verifyPeer: false)
            )
        );
    }

    private function requireHttp2(): void
    {
        if ((\curl_version()['features'] & \CURL_VERSION_HTTP2) === 0) {
            self::markTestSkipped('The cURL extension must support HTTP/2.');
        }
    }

    private function requireOpenSsl(): void
    {
        if (!\extension_loaded('openssl')) {
            self::markTestSkipped('The OpenSSL extension is required.');
        }
    }

    private function requireZlib(): void
    {
        if (!\extension_loaded('zlib')) {
            self::markTestSkipped('The zlib extension is required.');
        }
    }
}
