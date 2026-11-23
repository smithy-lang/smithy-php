<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Auth\Aws;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Auth\Aws\AwsChunkedRequestStream;
use Smithy\Core\Checksum\ChecksumAlgorithm;
use Smithy\Http\Message\ResourceRequestStream;
use Smithy\Http\Message\StreamBodyAdapter;
use Smithy\Http\Message\StringRequestStream;
use Smithy\Tests\Auth\Aws\Utils\NonSeekableStreamFixture;

/**
 * Byte-exact framing tests for the `aws-chunked` unsigned trailer body.
 *
 * Trailer values come from the flexible-checksums SEP fixture
 * `test-streaming-request-checksum-calculation.json` ("Hello world").
 */
final class AwsChunkedRequestStreamTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function sepTrailers(): iterable
    {
        yield 'CRC32' => ['CRC32', 'x-amz-checksum-crc32', 'i9aeUg=='];
        yield 'CRC32C' => ['CRC32C', 'x-amz-checksum-crc32c', 'crUfeA=='];
        yield 'SHA1' => [
            'SHA1',
            'x-amz-checksum-sha1',
            'e1AsOh9IyGCa4hLN+2Od7jlnP14=',
        ];
        yield 'SHA256' => [
            'SHA256',
            'x-amz-checksum-sha256',
            'ZOyIygCyaOW6GjVnihtTFtIS9PNmskdyMlNKiuyjfzw=',
        ];
        yield 'SHA512' => [
            'SHA512',
            'x-amz-checksum-sha512',
            't/eDuu2Cl/DbkXRiGE/08I5pwtXl95qUJgD5cl9Yzh8pwYE5v4CwbA//K900c4RS7PQMSIwip+PYDN9vnBwNRw==',
        ];
        yield 'XXHASH64' => [
            'XXHASH64',
            'x-amz-checksum-xxhash64',
            'xQCwyRKzdtg=',
        ];
        yield 'XXHASH3' => [
            'XXHASH3',
            'x-amz-checksum-xxhash3',
            'tqy52Eo4/3Q=',
        ];
        yield 'XXHASH128' => [
            'XXHASH128',
            'x-amz-checksum-xxhash128',
            'c1H4mBL5c4K5HQWzHgTdfw==',
        ];
    }

    #[DataProvider('sepTrailers')]
    public function testEncodesSepPayloadWithTrailerPerAlgorithm(
        string $algorithm,
        string $trailerName,
        string $checksum,
    ): void
    {
        $stream = new AwsChunkedRequestStream(
            new StringRequestStream('Hello world'),
            ChecksumAlgorithm::resolveRequest($algorithm),
        );

        $expected = "b\r\nHello world\r\n"
            . "0\r\n"
            . "{$trailerName}:{$checksum}\r\n"
            . "\r\n";

        $this->assertSame($trailerName, $stream->trailerName());
        $this->assertSame(11, $stream->decodedContentLength());
        $this->assertSame(\strlen($expected), $stream->length());
        $this->assertSame($expected, $stream->getContents());
        $this->assertSame($checksum, $stream->checksum());
        $this->assertTrue($stream->eof());
    }

    public function testMatchesSepUnsignedTrailerLayoutFor1024ByteChunk(): void
    {
        $payload = \str_repeat('a', 1024);
        $stream = new AwsChunkedRequestStream(
            new StringRequestStream($payload),
            ChecksumAlgorithm::resolveRequest('CRC32'),
            1024,
        );
        $checksum = \base64_encode(\hash('crc32b', $payload, true));

        $expected = "400\r\n" . $payload . "\r\n"
            . "0\r\n"
            . "x-amz-checksum-crc32:{$checksum}\r\n"
            . "\r\n";

        $this->assertSame($expected, $stream->getContents());
        $this->assertSame(1067, $stream->length());
        $this->assertSame(1067, \strlen($expected));
    }

    public function testEncodesEmptyBodyAsTerminatingChunkAndTrailer(): void
    {
        $stream = new AwsChunkedRequestStream(
            StringRequestStream::empty(),
            ChecksumAlgorithm::resolveRequest('CRC32'),
        );

        $expected = "0\r\nx-amz-checksum-crc32:AAAAAA==\r\n\r\n";

        $this->assertSame(0, $stream->decodedContentLength());
        $this->assertSame(\strlen($expected), $stream->length());
        $this->assertSame($expected, (string) $stream);
        $this->assertSame('AAAAAA==', $stream->checksum());
    }

    public function testEncodesLargeBodyAcrossMultipleChunks(): void
    {
        $chunkSize = 8192;
        $payload = \random_bytes(3 * $chunkSize + 5);
        $stream = new AwsChunkedRequestStream(
            new StringRequestStream($payload),
            ChecksumAlgorithm::resolveRequest('SHA256'),
            $chunkSize,
        );
        $checksum = \base64_encode(\hash('sha256', $payload, true));

        $chunks = \str_split($payload, $chunkSize);
        $expected = '';
        foreach ($chunks as $chunk) {
            $expected .= \dechex(\strlen($chunk)) . "\r\n" . $chunk . "\r\n";
        }
        $expected .= "0\r\nx-amz-checksum-sha256:{$checksum}\r\n\r\n";

        $encoded = '';
        while (!$stream->eof()) {
            $encoded .= $stream->read(1000);
        }

        $this->assertCount(4, $chunks);
        $this->assertStringStartsWith("2000\r\n", $encoded);
        $this->assertSame($expected, $encoded);
        $this->assertSame(\strlen($expected), $stream->length());
        $this->assertSame(\strlen($expected), $stream->tell());
        $this->assertSame($checksum, $stream->checksum());
    }

    public function testEncodesExactMultipleOfChunkSizeWithoutEmptyDataChunk(): void
    {
        $payload = \str_repeat('z', 32);
        $stream = new AwsChunkedRequestStream(
            new StringRequestStream($payload),
            ChecksumAlgorithm::resolveRequest('CRC32'),
            16,
        );
        $checksum = \base64_encode(\hash('crc32b', $payload, true));

        $expected = "10\r\n" . \str_repeat('z', 16) . "\r\n"
            . "10\r\n" . \str_repeat('z', 16) . "\r\n"
            . "0\r\nx-amz-checksum-crc32:{$checksum}\r\n\r\n";

        $this->assertSame($expected, $stream->getContents());
        $this->assertSame(\strlen($expected), $stream->length());
    }

    public function testEncodesNonSeekableUnknownLengthStreamWithFullChunks(): void
    {
        $payload = \str_repeat('0123456789', 10);
        $stream = new AwsChunkedRequestStream(
            StreamBodyAdapter::wrap(new NonSeekableStreamFixture($payload)),
            ChecksumAlgorithm::resolveRequest('CRC32C'),
            40,
        );
        $checksum = \base64_encode(\hash('crc32c', $payload, true));

        $expected = "28\r\n" . \substr($payload, 0, 40) . "\r\n"
            . "28\r\n" . \substr($payload, 40, 40) . "\r\n"
            . "14\r\n" . \substr($payload, 80) . "\r\n"
            . "0\r\nx-amz-checksum-crc32c:{$checksum}\r\n\r\n";

        $this->assertNull($stream->decodedContentLength());
        $this->assertNull($stream->length());
        $this->assertNull($stream->getSize());
        $this->assertFalse($stream->isReplayable());
        $this->assertSame($expected, $stream->getContents());
        $this->assertSame($checksum, $stream->checksum());
    }

    public function testEncodesNonSeekableResourcePayloadInChunks(): void
    {
        $payload = \str_repeat('0123456789', 10);
        $command = \escapeshellarg(PHP_BINARY)
            . ' -r '
            . \escapeshellarg(\sprintf('echo %s;', \var_export($payload, true)));
        $resource = \popen($command, 'r');
        if ($resource === false) {
            $this->fail('Unable to open test process pipe.');
        }

        try {
            $stream = new AwsChunkedRequestStream(
                new ResourceRequestStream($resource),
                ChecksumAlgorithm::resolveRequest('CRC32C'),
                40,
            );
            $checksum = \base64_encode(\hash('crc32c', $payload, true));

            $expected = "28\r\n" . \substr($payload, 0, 40) . "\r\n"
                . "28\r\n" . \substr($payload, 40, 40) . "\r\n"
                . "14\r\n" . \substr($payload, 80) . "\r\n"
                . "0\r\nx-amz-checksum-crc32c:{$checksum}\r\n\r\n";

            $this->assertFalse($stream->isReplayable());
            $this->assertSame($expected, $stream->getContents());
            $this->assertSame($checksum, $stream->checksum());
        } finally {
            if (\is_resource($resource)) {
                \pclose($resource);
            }
        }
    }

    public function testRetryReusesChecksumFromFirstAttempt(): void
    {
        $path = \tempnam(\sys_get_temp_dir(), 'aws-chunked');
        $this->assertIsString($path);
        try {
            \file_put_contents($path, 'first');
            $stream = new AwsChunkedRequestStream(
                ResourceRequestStream::fromFile($path),
                ChecksumAlgorithm::resolveRequest('CRC32'),
            );
            $firstChecksum = \base64_encode(\hash('crc32b', 'first', true));

            $this->assertSame(
                "5\r\nfirst\r\n0\r\nx-amz-checksum-crc32:{$firstChecksum}\r\n\r\n",
                $stream->getContents()
            );

            \file_put_contents($path, 'other');
            $stream->rewindForRetry();

            $this->assertFalse($stream->started());
            $this->assertSame(
                "5\r\nother\r\n0\r\nx-amz-checksum-crc32:{$firstChecksum}\r\n\r\n",
                $stream->getContents()
            );
            $this->assertSame($firstChecksum, $stream->checksum());
        } finally {
            @\unlink($path);
        }
    }

    public function testHashCoversEncodedBodyAndLeavesStreamAtStart(): void
    {
        $stream = new AwsChunkedRequestStream(
            new StringRequestStream('Hello world'),
            ChecksumAlgorithm::resolveRequest('CRC32'),
        );
        $encoded = "b\r\nHello world\r\n0\r\nx-amz-checksum-crc32:i9aeUg==\r\n\r\n";

        $this->assertSame(\hash('sha256', $encoded), $stream->hash());
        $this->assertSame(0, $stream->tell());
        $this->assertSame($encoded, $stream->getContents());
    }

    public function testRejectsNonPositiveChunkSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AwsChunkedRequestStream(
            StringRequestStream::empty(),
            ChecksumAlgorithm::resolveRequest('CRC32'),
            0,
        );
    }
}
