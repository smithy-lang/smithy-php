<?php

declare(strict_types=1);

namespace Smithy\Tests\Http\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Async\RevoltEventLoop;
use Smithy\Http\Message\AsyncRequestStream;

#[CoversClass(AsyncRequestStream::class)]
final class AsyncRequestStreamTest extends TestCase
{
    public function testSignalsReadableTransitions(): void
    {
        $stream = new AsyncRequestStream(
            new RevoltEventLoop(),
            expectedLength: 7
        );
        $notifications = 0;
        $stream->subscribe(
            static function () use (&$notifications): void {
                $notifications++;
            }
        );

        $this->assertFalse($stream->isReady());
        $this->assertSame(3, $stream->write('pay'));
        $this->assertTrue($stream->isReady());
        $this->assertSame(1, $notifications);
        $this->assertSame('pay', $stream->read(3));
        $this->assertFalse($stream->isReady());

        $stream->write('load');
        $stream->end();

        $this->assertSame(2, $notifications);
        $this->assertSame('load', $stream->read(4));
        $this->assertTrue($stream->eof());
        $this->assertTrue($stream->fullyConsumed());
    }

    public function testAwaitsReadableTransition(): void
    {
        $eventLoop = new RevoltEventLoop();
        $stream = new AsyncRequestStream($eventLoop);

        $results = $eventLoop->run([
            static function () use ($stream): string {
                $stream->awaitReadable();

                return $stream->read(1);
            },
            static function () use ($stream): void {
                $stream->write('a');
                $stream->end();
            }
        ]);

        $this->assertSame('a', $results[0]);
    }

    public function testSignalsFailure(): void
    {
        $stream = new AsyncRequestStream(new RevoltEventLoop());
        $failure = new \RuntimeException('producer failed');
        $notifications = 0;
        $stream->subscribe(
            static function () use (&$notifications): void {
                $notifications++;
            }
        );

        $stream->fail($failure);

        $this->assertTrue($stream->isReady());
        $this->assertSame(1, $notifications);

        $this->expectExceptionObject($failure);
        $stream->read(1);
    }

    public function testSubscriberFailureDoesNotBlockLaterSubscribers(): void
    {
        $stream = new AsyncRequestStream(new RevoltEventLoop());
        $failure = new \RuntimeException('subscriber failed');
        $calls = [];

        $stream->subscribe(
            static function () use (&$calls, $failure): void {
                $calls[] = 'first';

                throw $failure;
            }
        );
        $stream->subscribe(
            static function () use (&$calls): void {
                $calls[] = 'second';
            }
        );

        try {
            $stream->write('a');
            $this->fail('Expected subscriber failure.');
        } catch (\RuntimeException $e) {
            $this->assertSame($failure, $e);
        }

        $this->assertSame(['first', 'second'], $calls);
    }

    public function testReadsAcrossQueuedChunks(): void
    {
        $stream = new AsyncRequestStream(
            new RevoltEventLoop(),
            expectedLength: 8
        );
        $stream->write('ab');
        $stream->write('cdef');
        $stream->write('gh');
        $stream->end();

        $this->assertSame('abc', $stream->read(3));
        $this->assertSame(3, $stream->tell());
        $this->assertSame('defg', $stream->read(4));
        $this->assertSame('h', $stream->getContents());
        $this->assertTrue($stream->fullyConsumed());
    }

    public function testReadZeroReturnsEmptyString(): void
    {
        $stream = new AsyncRequestStream(new RevoltEventLoop());

        $this->assertSame('', $stream->read(0));
    }

    public function testReadRejectsNegativeLength(): void
    {
        $stream = new AsyncRequestStream(new RevoltEventLoop());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Read length must be non-negative.'
        );

        $stream->read(-1);
    }

    public function testBackpressuresProducer(): void
    {
        $eventLoop = new RevoltEventLoop();
        $stream = new AsyncRequestStream(
            $eventLoop,
            expectedLength: 8,
            highWaterMark: 4
        );
        $secondWriteCompleted = false;

        $results = $eventLoop->run([
            static function () use (
                $stream,
                &$secondWriteCompleted
            ): void
            {
                $stream->write('abcd');
                $stream->write('efgh');
                $secondWriteCompleted = true;
                $stream->end();
            },
            function () use (
                $stream,
                &$secondWriteCompleted
            ): string
            {
                $this->assertFalse($secondWriteCompleted);
                $this->assertSame(4, $stream->bufferedBytes());

                return $stream->read(4);
            }
        ]);

        $this->assertSame('abcd', $results[1]);
        $this->assertTrue($secondWriteCompleted);
        $this->assertSame(4, $stream->bufferedBytes());
        $this->assertSame('efgh', $stream->read(4));
        $this->assertTrue($stream->fullyConsumed());
    }

    public function testAcceptsOversizedWriteWhenEmpty(): void
    {
        $stream = new AsyncRequestStream(
            new RevoltEventLoop(),
            expectedLength: 8,
            highWaterMark: 4
        );

        $this->assertSame(8, $stream->write('oversize'));
        $this->assertSame(8, $stream->bufferedBytes());
        $this->assertSame('oversize', $stream->read(8));
    }

    public function testReleasesWriterWhenConsumerDetaches(): void
    {
        $eventLoop = new RevoltEventLoop();
        $stream = new AsyncRequestStream(
            $eventLoop,
            expectedLength: 8,
            highWaterMark: 4
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Async request stream is no longer writable.'
        );

        $eventLoop->run([
            static function () use ($stream): void {
                $stream->write('abcd');
                $stream->write('efgh');
            },
            static fn() => $stream->detach()
        ]);
    }

    public function testCloseWithUnreadBytesIsNotFullyConsumed(): void
    {
        $stream = new AsyncRequestStream(
            new RevoltEventLoop(),
            expectedLength: 7
        );
        $stream->write('payload');

        $stream->close();

        $this->assertTrue($stream->eof());
        $this->assertFalse($stream->fullyConsumed());
    }

    public function testCloseMakesStreamUnavailable(): void
    {
        $stream = new AsyncRequestStream(new RevoltEventLoop());

        $this->assertTrue($stream->isAvailable());

        $stream->close();

        $this->assertFalse($stream->isAvailable());
        $this->assertFalse($stream->isReadable());
    }

    public function testReadRejectsClosedStream(): void
    {
        $stream = new AsyncRequestStream(new RevoltEventLoop());
        $stream->close();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Async request stream is closed.');

        $stream->read(1);
    }

    public function testGetContentsRejectsClosedStream(): void
    {
        $stream = new AsyncRequestStream(new RevoltEventLoop());
        $stream->close();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Async request stream is closed.');

        $stream->getContents();
    }

    public function testRejectsWritesBeyondExpectedLength(): void
    {
        $stream = new AsyncRequestStream(
            new RevoltEventLoop(),
            expectedLength: 3
        );
        $stream->write('abc');

        $this->expectException(\LengthException::class);
        $this->expectExceptionMessage(
            'Async request stream write exceeds expected length of 3 bytes.'
        );

        $stream->write('x');
    }

    public function testRejectsEndBeforeExpectedLength(): void
    {
        $stream = new AsyncRequestStream(
            new RevoltEventLoop(),
            expectedLength: 4
        );
        $stream->write('abc');

        $this->expectException(\LengthException::class);
        $this->expectExceptionMessage(
            'Async request stream ended after 3 bytes, expected 4 bytes.'
        );

        $stream->end();
    }

    public function testDetachConsumerReleasesWriterWithOtherSubscriber(): void
    {
        $eventLoop = new RevoltEventLoop();
        $stream = new AsyncRequestStream(
            $eventLoop,
            expectedLength: 8,
            highWaterMark: 4
        );
        $stream->subscribe(static function (): void {});
        $error = null;

        $eventLoop->run([
            static function () use (
                $stream,
                &$error
            ): void
            {
                try {
                    $stream->write('abcd');
                    $stream->write('efgh');
                } catch (\RuntimeException $e) {
                    $error = $e;
                }
            },
            static function () use (
                $stream,
                $eventLoop
            ): void
            {
                $eventLoop->delay(0.01);
                $stream->detachConsumer();
            }
        ]);

        $this->assertInstanceOf(\RuntimeException::class, $error);
        $this->assertSame(
            'Async request stream consumer detached while a write '
                . 'was waiting for capacity.',
            $error->getMessage()
        );
    }

    public function testRejectsInvalidHighWaterMark(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Request buffer size must be positive.'
        );

        new AsyncRequestStream(
            new RevoltEventLoop(),
            highWaterMark: 0
        );
    }
}
