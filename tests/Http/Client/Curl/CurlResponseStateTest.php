<?php

declare(strict_types=1);

namespace Smithy\Tests\Http\Client\Curl;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Http\Client\Curl\CurlResponseState;

#[CoversClass(CurlResponseState::class)]
final class CurlResponseStateTest extends TestCase
{
    public function testNotifiesWhenReadTimeoutTrackingStarts(): void
    {
        $notifications = 0;
        $state = new CurlResponseState(
            null,
            onReadTimeoutStarted: static function () use (
                &$notifications
            ): void
            {
                $notifications++;
            }
        );

        $state->recordReadActivity();
        $state->recordReadActivity();
        $deadline = $state->readTimeoutDeadline(1.0);

        $this->assertSame(1, $notifications);
        $this->assertNotNull($deadline);
        $this->assertTrue(
            $state->readTimedOut(1.0, $deadline)
        );
    }

    public function testIgnoresInformationalResponseHeaders(): void
    {
        $state = new CurlResponseState(null);

        $this->assertFalse(
            $state->consumeHeader("HTTP/1.1 103 Early Hints\r\n")
        );
        $this->assertFalse(
            $state->consumeHeader("Link: </style.css>\r\n")
        );
        $this->assertFalse($state->consumeHeader("\r\n"));
        $this->assertFalse(
            $state->consumeHeader("HTTP/1.1 200 OK\r\n")
        );
        $this->assertFalse(
            $state->consumeHeader("Content-Type: text/plain\r\n")
        );
        $this->assertTrue($state->consumeHeader("\r\n"));

        $this->assertSame(200, $state->status);
        $this->assertSame(
            ['Content-Type' => ['text/plain']],
            $state->headers
        );
    }

    public function testPreservesRedirectStatusWithLocation(): void
    {
        $state = new CurlResponseState(null);

        $state->consumeHeader("HTTP/1.1 302 Found\r\n");
        $state->consumeHeader("Location: /next\r\n");
        $this->assertTrue($state->consumeHeader("\r\n"));

        $this->assertSame(302, $state->status);
        $this->assertSame('Found', $state->reason);
        $this->assertSame(
            ['Location' => ['/next']],
            $state->headers
        );
    }

    public function testIgnoresProxyConnectHeadersWithAnyReason(): void
    {
        $state = new CurlResponseState(
            null,
            ignoreProxyConnectResponse: true
        );

        $state->consumeHeader("HTTP/1.1 200 OK\r\n");
        $state->consumeHeader("Proxy-Agent: local\r\n");
        $this->assertFalse($state->consumeHeader("\r\n"));
        $state->consumeHeader("HTTP/1.1 200 Connection established\r\n");
        $state->consumeHeader("X-Final: value\r\n");
        $this->assertTrue($state->consumeHeader("\r\n"));

        $this->assertSame(['X-Final' => ['value']], $state->headers);
        $this->assertSame('Connection established', $state->reason);
    }

    public function testPreservesDirectConnectionEstablishedResponse(): void
    {
        $state = new CurlResponseState(null);

        $state->consumeHeader(
            "HTTP/1.1 200 Connection established\r\n"
        );
        $state->consumeHeader("X-Final: value\r\n");
        $this->assertTrue($state->consumeHeader("\r\n"));

        $this->assertSame(200, $state->status);
        $this->assertSame('Connection established', $state->reason);
        $this->assertSame(['X-Final' => ['value']], $state->headers);
    }

    public function testCollectsTrailersAfterFinalHeaders(): void
    {
        $state = new CurlResponseState(null);

        $state->consumeHeader("HTTP/1.1 200 OK\r\n");
        $state->consumeHeader("X-Initial: value\r\n");
        $this->assertTrue($state->consumeHeader("\r\n"));
        $this->assertFalse(
            $state->consumeHeader("X-Checksum: first\r\n")
        );
        $this->assertFalse(
            $state->consumeHeader("x-checksum: second\r\n")
        );
        $this->assertFalse($state->consumeHeader("\r\n"));
        $state->completeTrailers();

        $this->assertSame(
            ['X-Initial' => ['value']],
            $state->headers
        );
        $this->assertSame(
            ['X-Checksum' => ['first', 'second']],
            $state->trailers()->getHeaders()
        );
    }

    public function testPreservesFinalReasonPhrase(): void
    {
        $state = new CurlResponseState(null);

        $state->consumeHeader("HTTP/1.1 599 Custom Failure\r\n");
        $state->consumeHeader("\r\n");

        $this->assertSame(599, $state->status);
        $this->assertSame('Custom Failure', $state->reason);
    }

    public function testCompletesEmptyTrailers(): void
    {
        $state = new CurlResponseState(null);

        $state->consumeHeader("HTTP/2 204\r\n");
        $state->consumeHeader("\r\n");
        $state->completeTrailers();

        $this->assertSame([], $state->trailers()->getHeaders());
    }

    public function testFailsTrailersWithTransportCause(): void
    {
        $state = new CurlResponseState(null);
        $failure = new \RuntimeException('transfer failed');
        $state->failTrailers($failure);

        $this->expectExceptionObject($failure);

        $state->trailers()->getHeaders();
    }

    public function testRemovesDecodedRepresentationHeaders(): void
    {
        $state = new CurlResponseState(null, true);

        $state->consumeHeader("HTTP/1.1 200 OK\r\n");
        $state->consumeHeader("Content-Encoding: gzip\r\n");
        $state->consumeHeader("Content-Length: 10\r\n");
        $state->consumeHeader("X-Test: value\r\n");
        $state->consumeHeader("\r\n");

        $this->assertSame(
            ['X-Test' => ['value']],
            $state->headers
        );
    }

    public function testPreservesRawRepresentationHeaders(): void
    {
        $state = new CurlResponseState(null, false);

        $state->consumeHeader("HTTP/1.1 200 OK\r\n");
        $state->consumeHeader("Content-Encoding: gzip\r\n");
        $state->consumeHeader("Content-Length: 10\r\n");
        $state->consumeHeader("\r\n");

        $this->assertSame(
            [
                'Content-Encoding' => ['gzip'],
                'Content-Length' => ['10']
            ],
            $state->headers
        );
    }

    public function testPreservesMetadataForBodylessResponses(): void
    {
        $head = new CurlResponseState(
            null,
            true,
            0,
            false
        );
        $head->consumeHeader("HTTP/1.1 200 OK\r\n");
        $head->consumeHeader("Content-Encoding: gzip\r\n");
        $head->consumeHeader("Content-Length: 10\r\n");
        $head->consumeHeader("\r\n");

        $notModified = new CurlResponseState(null, true);
        $notModified->consumeHeader("HTTP/1.1 304 Not Modified\r\n");
        $notModified->consumeHeader("Content-Encoding: gzip\r\n");
        $notModified->consumeHeader("Content-Length: 10\r\n");
        $notModified->consumeHeader("\r\n");

        $expected = [
            'Content-Encoding' => ['gzip'],
            'Content-Length' => ['10']
        ];
        $this->assertSame($expected, $head->headers);
        $this->assertSame($expected, $notModified->headers);
    }

    public function testAutomaticDeliveryBuffersResponseAtThreshold(): void
    {
        $state = new CurlResponseState(
            null,
            maxBufferedResponseBytes: 1024
        );
        $state->consumeHeader("HTTP/1.1 200 OK\r\n");
        $state->consumeHeader("Content-Length: 1024\r\n");
        $state->consumeHeader("\r\n");

        $this->assertFalse($state->shouldStreamResponseBody());
    }

    public function testAutomaticDeliveryStreamsResponseAboveThreshold(): void
    {
        $state = new CurlResponseState(
            null,
            maxBufferedResponseBytes: 1024
        );
        $state->consumeHeader("HTTP/1.1 200 OK\r\n");
        $state->consumeHeader("Content-Length: 1025\r\n");
        $state->consumeHeader("\r\n");

        $this->assertTrue($state->shouldStreamResponseBody());
    }

    public function testAutomaticDeliveryStreamsUnknownLengthResponse(): void
    {
        $state = new CurlResponseState(null);
        $state->consumeHeader("HTTP/1.1 200 OK\r\n");
        $state->consumeHeader("\r\n");

        $this->assertTrue($state->shouldStreamResponseBody());
    }

    public function testAutomaticDeliveryStreamsDecodedResponse(): void
    {
        $state = new CurlResponseState(null);
        $state->consumeHeader("HTTP/1.1 200 OK\r\n");
        $state->consumeHeader("Content-Encoding: gzip\r\n");
        $state->consumeHeader("Content-Length: 100\r\n");
        $state->consumeHeader("\r\n");

        $this->assertTrue($state->shouldStreamResponseBody());
    }

    public function testAutomaticDeliveryBuffersBodylessResponse(): void
    {
        $state = new CurlResponseState(null);
        $state->consumeHeader("HTTP/1.1 204 No Content\r\n");
        $state->consumeHeader("\r\n");

        $this->assertFalse($state->shouldStreamResponseBody());
    }

    public function testBuffersBodyAtConfiguredLimit(): void
    {
        $state = new CurlResponseState(null, true, 4);
        $state->consumeHeader("HTTP/1.1 200 OK\r\n");
        $state->consumeHeader("\r\n");

        $this->assertSame(2, $state->consumeBody('ab'));
        $this->assertSame(2, $state->consumeBody('cd'));
        $this->assertSame('abcd', $state->bufferedBody());
        $this->assertNull($state->bodyWriteError);
    }

    public function testRejectsBodyAboveConfiguredLimit(): void
    {
        $state = new CurlResponseState(null, true, 3);
        $state->consumeHeader("HTTP/1.1 200 OK\r\n");
        $state->consumeHeader("\r\n");

        $this->assertSame(2, $state->consumeBody('ab'));
        $this->assertSame(0, $state->consumeBody('cd'));
        $this->assertSame('ab', $state->bufferedBody());
        $this->assertInstanceOf(
            \LengthException::class,
            $state->bodyWriteError
        );
    }
}
