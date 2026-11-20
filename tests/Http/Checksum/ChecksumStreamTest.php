<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Http\Checksum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Checksum\ChecksumAlgorithm;
use Smithy\Http\Checksum\ChecksumMismatchException;
use Smithy\Http\Checksum\ChecksumStream;
use Smithy\Http\Checksum\ExpectedChecksum;
use Smithy\Http\Message\StringResponseStream;

#[CoversClass(ChecksumStream::class)]
final class ChecksumStreamTest extends TestCase
{
    public function testValidatesAtEndOfPayload(): void
    {
        $stream = $this->stream('i9aeUg==');

        $this->assertSame('Hello', $stream->read(5));
        $this->assertFalse($stream->isValidationComplete());

        $this->assertSame(' world', $stream->getContents());
        $this->assertTrue($stream->isValidationComplete());
        $this->assertTrue($stream->isValidated());
        $this->assertSame('CRC32', $stream->getAlgorithm());
    }

    public function testThrowsMismatchWhenPayloadIsFullyConsumed(): void
    {
        $stream = $this->stream('bm90LWEtY2hlY2tzdW0=');

        try {
            $stream->getContents();
            $this->fail('Expected checksum validation to fail.');
        } catch (ChecksumMismatchException $exception) {
            $this->assertSame(ChecksumAlgorithm::CRC32, $exception->algorithm);
            $this->assertSame('i9aeUg==', $exception->calculatedChecksum);
            $this->assertTrue($stream->isValidationComplete());
            $this->assertFalse($stream->isValidated());
        }
    }

    public function testValidatesEmptyPayload(): void
    {
        $stream = new ChecksumStream(
            new StringResponseStream(''),
            new ExpectedChecksum(ChecksumAlgorithm::CRC32, 'AAAAAA==')
        );

        $this->assertSame('', $stream->getContents());
        $this->assertTrue($stream->isValidated());
    }

    public function testRewindRestartsValidation(): void
    {
        $stream = $this->stream('i9aeUg==');

        $this->assertSame('Hello', $stream->read(5));
        $stream->rewind();

        $this->assertSame('Hello world', (string) $stream);
        $this->assertTrue($stream->isValidated());
    }

    public function testRejectsForwardSeekBeforeValidation(): void
    {
        $stream = $this->stream('i9aeUg==');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Cannot seek a response body before checksum validation completes.'
        );

        $stream->seek(1);
    }

    private function stream(string $base64Digest): ChecksumStream
    {
        return new ChecksumStream(
            new StringResponseStream('Hello world'),
            new ExpectedChecksum(ChecksumAlgorithm::CRC32, $base64Digest)
        );
    }
}
