<?php

declare(strict_types=1);

namespace Smithy\Tests\Http\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Async\RevoltEventLoop;
use Smithy\Http\Message\StreamingResponseStream;

#[CoversClass(StreamingResponseStream::class)]
final class StreamingResponseStreamTest extends TestCase
{
    public function testReturnsOneQueuedChunkAndPartialHead(): void
    {
        $spaceAvailable = 0;
        $body = new StreamingResponseStream(
            new RevoltEventLoop(),
            12,
            static function () use (&$spaceAvailable): void {
                ++$spaceAvailable;
            },
            static function (): void {}
        );
        $this->assertTrue($body->append('abcd'));
        $this->assertTrue($body->append('efghij'));

        $this->assertSame('abcd', $body->read(6));
        $this->assertSame(6, $body->bufferedBytes());
        $this->assertSame(4, $body->tell());
        $this->assertTrue($body->append('klmnop'));

        $this->assertSame('efg', $body->read(3));
        $this->assertSame(9, $body->bufferedBytes());
        $this->assertSame('hij', $body->read(10));
        $this->assertSame('klmnop', $body->read(10));
        $body->end();

        $this->assertSame(0, $body->bufferedBytes());
        $this->assertSame(16, $body->tell());
        $this->assertSame(0, $spaceAvailable);
        $this->assertTrue($body->fullyConsumed());
    }

    public function testReadZeroReturnsEmptyString(): void
    {
        $body = new StreamingResponseStream(
            new RevoltEventLoop(),
            8,
            static function (): void {},
            static function (): void {}
        );

        $this->assertSame('', $body->read(0));
    }

    public function testReadRejectsNegativeLength(): void
    {
        $body = new StreamingResponseStream(
            new RevoltEventLoop(),
            8,
            static function (): void {},
            static function (): void {}
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Read length must be non-negative.'
        );

        $body->read(-1);
    }

    public function testEnforcesHighWaterMarkAcrossQueuedChunks(): void
    {
        $body = new StreamingResponseStream(
            new RevoltEventLoop(),
            8,
            static function (): void {},
            static function (): void {}
        );
        $this->assertTrue($body->append('abc'));
        $this->assertTrue($body->append('defgh'));
        $this->assertFalse($body->append('i'));
        $this->assertSame(8, $body->bufferedBytes());

        $this->assertSame('ab', $body->read(2));
        $this->assertTrue($body->append('ij'));
        $this->assertFalse($body->append('k'));
        $body->end();

        $this->assertSame('c', $body->read(8));
        $this->assertSame('defgh', $body->read(8));
        $this->assertSame('ij', $body->read(8));
        $this->assertTrue($body->fullyConsumed());
    }

    public function testAcceptsOversizedChunkWhenBufferIsEmpty(): void
    {
        $body = new StreamingResponseStream(
            new RevoltEventLoop(),
            4,
            static function (): void {},
            static function (): void {}
        );

        $this->assertTrue($body->append('abcdefgh'));
        $this->assertSame(8, $body->bufferedBytes());
        $this->assertFalse($body->canAppend(1));
        $this->assertSame('abcde', $body->read(5));
        $this->assertTrue($body->canAppend(1));
    }

    public function testResumesProducerAtLowWaterMark(): void
    {
        $body = new StreamingResponseStream(
            new RevoltEventLoop(),
            8,
            static function (): void {},
            static function (): void {}
        );
        $this->assertTrue($body->append('abcdefgh'));

        $this->assertFalse($body->canResume(1));
        $this->assertSame('abc', $body->read(3));
        $this->assertTrue($body->canAppend(1));
        $this->assertFalse($body->canResume(1));

        $this->assertSame('d', $body->read(1));
        $this->assertTrue($body->canResume(1));
        $this->assertFalse($body->canResume(5));

        $this->assertSame('e', $body->read(1));
        $this->assertTrue($body->canResume(5));
    }

    public function testNotifiesBlockedProducerOnceChunkCanResume(): void
    {
        $spaceAvailable = 0;
        $body = new StreamingResponseStream(
            new RevoltEventLoop(),
            8,
            static function () use (&$spaceAvailable): void {
                ++$spaceAvailable;
            },
            static function (): void {}
        );
        $this->assertTrue($body->append('abcdefgh'));
        $this->assertFalse($body->append('ijklm'));

        $this->assertSame('abc', $body->read(3));
        $this->assertSame(0, $spaceAvailable);
        $this->assertSame('d', $body->read(1));
        $this->assertSame(0, $spaceAvailable);
        $this->assertSame('e', $body->read(1));
        $this->assertSame(1, $spaceAvailable);
        $this->assertSame('f', $body->read(1));
        $this->assertSame(1, $spaceAvailable);
    }

    public function testGetsContentsAcrossProducerCycles(): void
    {
        $eventLoop = new RevoltEventLoop();
        $body = new StreamingResponseStream(
            $eventLoop,
            4,
            static function (): void {},
            static function (): void {}
        );
        $contents = null;

        $eventLoop->run([
            static function () use ($body, &$contents): void {
                $contents = $body->getContents();
            },
            static function () use ($body, $eventLoop): void {
                $eventLoop->delay(0.01);
                self::assertTrue($body->append('ab'));
                $eventLoop->delay(0.01);
                self::assertTrue($body->append('cdef'));
                $eventLoop->delay(0.01);
                self::assertTrue($body->append('gh'));
                $body->end();
            }
        ]);

        $this->assertSame('abcdefgh', $contents);
        $this->assertSame(8, $body->tell());
        $this->assertTrue($body->fullyConsumed());
    }

    public function testDiscardsBufferedAndProducedBytes(): void
    {
        $eventLoop = new RevoltEventLoop();
        $spaceAvailable = 0;
        $closed = 0;
        $body = new StreamingResponseStream(
            $eventLoop,
            8,
            static function () use (&$spaceAvailable): void {
                ++$spaceAvailable;
            },
            static function () use (&$closed): void {
                ++$closed;
            }
        );
        $this->assertTrue($body->append('buffered'));
        $this->assertFalse($body->append('blocked'));

        $eventLoop->run([
            static fn() => $body->discard(),
            static function () use ($body, $eventLoop): void {
                $eventLoop->delay(0.01);
                self::assertTrue($body->append('produced'));
                $body->end();
            }
        ]);

        $this->assertSame(16, $body->tell());
        $this->assertSame(0, $body->bufferedBytes());
        $this->assertTrue($body->fullyConsumed());
        $this->assertSame(1, $spaceAvailable);
        $this->assertSame(0, $closed);
    }

    public function testDiscardPropagatesProducerFailure(): void
    {
        $eventLoop = new RevoltEventLoop();
        $failure = new \RuntimeException('response failed');
        $body = new StreamingResponseStream(
            $eventLoop,
            8,
            static function (): void {},
            static function (): void {}
        );

        try {
            $eventLoop->run([
                static fn() => $body->discard(),
                static function () use (
                    $body,
                    $eventLoop,
                    $failure
                ): void
                {
                    $eventLoop->delay(0.01);
                    $body->fail($failure);
                }
            ]);
            $this->fail('Expected discard to propagate the failure.');
        } catch (\RuntimeException $error) {
            $this->assertSame($failure, $error);
        }
    }

    public function testDiscardIsIdempotentAfterCompletion(): void
    {
        $body = new StreamingResponseStream(
            new RevoltEventLoop(),
            8,
            static function (): void {},
            static function (): void {}
        );
        $body->append('complete');
        $body->end();

        $body->discard();
        $body->discard();

        $this->assertTrue($body->fullyConsumed());
        $this->assertSame(8, $body->tell());
    }

    public function testGetContentsRejectsClosedStream(): void
    {
        $body = new StreamingResponseStream(
            new RevoltEventLoop(),
            8,
            static function (): void {},
            static function (): void {}
        );
        $body->close();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Streamed response body is closed.');

        $body->getContents();
    }
}
