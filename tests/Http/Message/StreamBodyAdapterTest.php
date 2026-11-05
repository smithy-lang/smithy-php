<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Http\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Smithy\Http\Message\StreamBodyAdapter;
use Smithy\Http\Message\StringRequestStream;
use Smithy\Tests\Http\Message\Utils\GenericStreamFixture;

#[CoversClass(StreamBodyAdapter::class)]
final class StreamBodyAdapterTest extends TestCase
{
    public function testReturnsExistingBodyStream(): void
    {
        $inner = new StringRequestStream('abcdef');

        $this->assertSame($inner, StreamBodyAdapter::wrap($inner));
    }

    public function testTracksKnownSizeStreamFromInitialOffset(): void
    {
        $inner = new GenericStreamFixture('abcdef');
        $inner->seek(2);
        $stream = StreamBodyAdapter::wrap($inner);

        $this->assertSame(4, $stream->length());
        $this->assertSame(4, $stream->getSize());
        $this->assertSame(2, $stream->tell());
        $this->assertSame('cd', $stream->read(2));
        $this->assertTrue($stream->started());
        $this->assertFalse($stream->fullyConsumed());
        $this->assertSame('ef', $stream->getContents());
        $this->assertTrue($stream->fullyConsumed());
    }

    public function testTracksUnknownSizeStreamConsumption(): void
    {
        $stream = StreamBodyAdapter::wrap(
            new GenericStreamFixture(
                content: 'abc',
                sizeKnown: false
            )
        );

        $this->assertNull($stream->length());
        $this->assertSame('abc', $stream->read(8));
        $this->assertTrue($stream->started());
        $this->assertFalse($stream->fullyConsumed());
        $this->assertSame('', $stream->read(8));
        $this->assertTrue($stream->fullyConsumed());
    }

    public function testGetContentsMarksUnknownSizeStreamConsumed(): void
    {
        $stream = StreamBodyAdapter::wrap(
            new GenericStreamFixture(
                content: 'abc',
                sizeKnown: false
            )
        );

        $this->assertSame('abc', $stream->getContents());
        $this->assertTrue($stream->started());
        $this->assertTrue($stream->fullyConsumed());
    }

    public function testSeekBeforeReadingResetsLogicalStart(): void
    {
        $stream = StreamBodyAdapter::wrap(
            new GenericStreamFixture('abcdef')
        );

        $stream->seek(2);

        $this->assertSame(4, $stream->length());
        $this->assertSame('cdef', $stream->getContents());
        $this->assertTrue($stream->fullyConsumed());
    }

    public function testRewindsToLogicalStartForRetry(): void
    {
        $inner = new GenericStreamFixture('abcdef');
        $inner->seek(2);
        $stream = StreamBodyAdapter::wrap($inner);
        $stream->read(2);

        $stream->rewindForRetry();

        $this->assertSame(2, $stream->tell());
        $this->assertFalse($stream->started());
        $this->assertFalse($stream->fullyConsumed());
        $this->assertSame('cdef', $stream->getContents());
    }

    public function testRejectsRetryForNonSeekableStream(): void
    {
        $stream = StreamBodyAdapter::wrap(
            new GenericStreamFixture(
                content: 'abcdef',
                seekable: false
            )
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stream is not seekable.');

        $stream->rewindForRetry();
    }

    public function testHashesLogicalContentWithoutMovingCursor(): void
    {
        $inner = new GenericStreamFixture('abcdef');
        $inner->seek(2);
        $stream = StreamBodyAdapter::wrap($inner);
        $stream->read(1);

        $this->assertSame(\hash('sha256', 'cdef'), $stream->hash());
        $this->assertSame(3, $stream->tell());
    }

    public function testRejectsHashForNonSeekableStream(): void
    {
        $stream = StreamBodyAdapter::wrap(
            new GenericStreamFixture(
                content: 'abcdef',
                seekable: false
            )
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Cannot hash a non-seekable generic stream.'
        );

        $stream->hash();
    }

    public function testReportsReplayabilityAndAvailability(): void
    {
        $seekable = StreamBodyAdapter::wrap(
            new GenericStreamFixture('abcdef')
        );
        $oneShot = StreamBodyAdapter::wrap(
            new GenericStreamFixture(
                content: 'abcdef',
                seekable: false
            )
        );

        $this->assertTrue($seekable->isReplayable());
        $this->assertTrue($seekable->isAvailable());
        $this->assertFalse($oneShot->isReplayable());
        $this->assertTrue($oneShot->isAvailable());

        $oneShot->read(1);

        $this->assertFalse($oneShot->isAvailable());
    }

    public function testDelegatesStringAndMetadata(): void
    {
        $stream = StreamBodyAdapter::wrap(
            new GenericStreamFixture('abc')
        );

        $this->assertSame('abc', (string) $stream);
        $this->assertTrue($stream->getMetadata('fixture'));
    }

    public function testDelegatesGenericStreamMethods(): void
    {
        $inner = $this->createMock(StreamInterface::class);
        $inner->expects($this->once())->method('close');
        $inner->expects($this->once())->method('detach')->willReturn(null);
        $inner->method('getSize')->willReturn(3);
        $inner->method('isSeekable')->willReturn(true);
        $inner->method('tell')->willReturn(0);
        $inner->method('isWritable')->willReturn(true);
        $inner->method('isReadable')->willReturn(true);
        $inner->method('eof')->willReturn(false);
        $inner->method('write')->with('abc')->willReturn(3);
        $inner->method('getMetadata')->with('key')->willReturn('value');
        $stream = StreamBodyAdapter::wrap($inner);

        $this->assertTrue($stream->isWritable());
        $this->assertTrue($stream->isReadable());
        $this->assertFalse($stream->eof());
        $this->assertSame(3, $stream->write('abc'));
        $this->assertSame('value', $stream->getMetadata('key'));
        $this->assertNull($stream->detach());
        $stream->close();
    }
}
