<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Http\Checksum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Http\Checksum\ChecksumMismatchException;
use Smithy\Http\Checksum\ResponseChecksumSelection;
use Smithy\Http\Checksum\ResponseChecksumValidatingBodyStream;
use Smithy\Http\Message\StringRequestStream;

#[CoversClass(ResponseChecksumValidatingBodyStream::class)]
final class ResponseChecksumValidatingBodyStreamTest extends TestCase
{
    public function testValidatesIncrementallyAtEndOfPayload(): void
    {
        $stream = $this->stream('i9aeUg==');

        $this->assertSame('Hello', $stream->read(5));
        $this->assertFalse($stream->isValidationComplete());
        $this->assertFalse($stream->isValidated());

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
            $this->assertSame('CRC32', $exception->algorithm);
            $this->assertSame(
                'bm90LWEtY2hlY2tzdW0=',
                $exception->expectedChecksum
            );
            $this->assertSame('i9aeUg==', $exception->calculatedChecksum);
            $this->assertTrue($stream->isValidationComplete());
            $this->assertFalse($stream->isValidated());
        }
    }

    public function testValidatesEmptyPayload(): void
    {
        $stream = new ResponseChecksumValidatingBodyStream(
            new StringRequestStream(''),
            new ResponseChecksumSelection(
                algorithm: 'CRC32',
                hashAlgorithm: 'crc32b',
                header: 'x-amz-checksum-crc32',
                encodedChecksum: 'AAAAAA==',
                checksum: \base64_decode('AAAAAA==', true),
            )
        );

        $this->assertSame('', $stream->getContents());
        $this->assertTrue($stream->isValidationComplete());
        $this->assertTrue($stream->isValidated());
    }

    public function testRewindRestartsPartialValidation(): void
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

    private function stream(
        string $expected,
    ): ResponseChecksumValidatingBodyStream {
        $checksum = \base64_decode($expected, true);
        $this->assertIsString($checksum);

        return new ResponseChecksumValidatingBodyStream(
            new StringRequestStream('Hello world'),
            new ResponseChecksumSelection(
                algorithm: 'CRC32',
                hashAlgorithm: 'crc32b',
                header: 'x-amz-checksum-crc32',
                encodedChecksum: $expected,
                checksum: $checksum
            )
        );
    }
}
