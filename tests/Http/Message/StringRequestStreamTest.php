<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Http\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Http\Message\StringRequestStream;

#[CoversClass(StringRequestStream::class)]
final class StringRequestStreamTest extends TestCase
{
    public function testReadsAndTracksConsumption(): void
    {
        $stream = new StringRequestStream('abcdef');

        $this->assertSame('abcdef', (string) $stream);
        $this->assertSame(6, $stream->getSize());
        $this->assertSame(6, $stream->length());
        $this->assertSame(0, $stream->tell());
        $this->assertFalse($stream->eof());
        $this->assertSame('abc', $stream->read(3));
        $this->assertSame(3, $stream->tell());
        $this->assertTrue($stream->started());
        $this->assertFalse($stream->fullyConsumed());
        $this->assertSame('def', $stream->getContents());
        $this->assertTrue($stream->eof());
        $this->assertTrue($stream->fullyConsumed());
    }

    public function testRewindsForRetry(): void
    {
        $stream = new StringRequestStream('abcdef');
        $stream->getContents();

        $stream->rewindForRetry();

        $this->assertSame(0, $stream->tell());
        $this->assertFalse($stream->started());
        $this->assertFalse($stream->fullyConsumed());
        $this->assertSame('abcdef', $stream->getContents());
    }

    public function testSeeksFromEachOrigin(): void
    {
        $stream = new StringRequestStream('abcdef');

        $stream->seek(2);
        $this->assertSame(2, $stream->tell());

        $stream->seek(2, SEEK_CUR);
        $this->assertSame(4, $stream->tell());

        $stream->seek(-1, SEEK_END);
        $this->assertSame(5, $stream->tell());
        $this->assertSame('f', $stream->read(1));
        $this->assertTrue($stream->fullyConsumed());
    }

    public function testRejectsInvalidSeekOrigin(): void
    {
        $stream = new StringRequestStream('abcdef');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid whence: 99');

        $stream->seek(0, 99);
    }

    public function testRejectsSeekOutsideContent(): void
    {
        $stream = new StringRequestStream('abcdef');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to seek to position 7.');

        $stream->seek(7);
    }

    public function testExposesReadOnlyStreamCapabilities(): void
    {
        $stream = new StringRequestStream('payload');

        $this->assertTrue($stream->isReadable());
        $this->assertTrue($stream->isSeekable());
        $this->assertTrue($stream->isReplayable());
        $this->assertTrue($stream->isAvailable());
        $this->assertFalse($stream->isWritable());
        $this->assertNull($stream->detach());
        $this->assertSame([], $stream->getMetadata());
        $this->assertNull($stream->getMetadata('key'));
    }

    public function testRejectsWrites(): void
    {
        $stream = new StringRequestStream('payload');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'StringRequestStream is not writable.'
        );

        $stream->write('updated');
    }

    public function testHashesCompleteContentWithoutMovingCursor(): void
    {
        $stream = new StringRequestStream('payload');
        $stream->seek(3);

        $this->assertSame(
            \hash('sha256', 'payload'),
            $stream->hash()
        );
        $this->assertSame(
            \hash('sha256', 'payload', true),
            $stream->hash(raw: true)
        );
        $this->assertSame(3, $stream->tell());
    }

    public function testReusesEmptyInstance(): void
    {
        $first = StringRequestStream::empty();
        $second = StringRequestStream::empty();

        $this->assertSame($first, $second);
        $this->assertSame(0, $first->length());
        $this->assertTrue($first->eof());
    }
}
