<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Http\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Http\Message\ResourceRequestStream;

#[CoversClass(ResourceRequestStream::class)]
final class ResourceRequestStreamTest extends TestCase
{
    public function testUsesRemainingLength(): void
    {
        $stream = ResourceRequestStream::temp('abcdef');
        $stream->seek(2);

        $this->assertSame(4, $stream->length());
        $this->assertSame('cd', $stream->read(2));
        $this->assertTrue($stream->started());
        $this->assertFalse($stream->fullyConsumed());

        $stream->rewindForRetry();

        $this->assertFalse($stream->started());
        $this->assertFalse($stream->fullyConsumed());
        $this->assertSame('cd', $stream->read(2));
    }

    public function testTracksNativeResourceReads(): void
    {
        $stream = ResourceRequestStream::temp('abcdef');
        $resource = $stream->getResource();

        $this->assertSame('abc', \fread($resource, 3));
        $this->assertTrue($stream->started());
        $this->assertFalse($stream->fullyConsumed());
        $this->assertSame('def', \stream_get_contents($resource));
        $this->assertTrue($stream->fullyConsumed());

        $stream->rewindForRetry();

        $this->assertFalse($stream->started());
        $this->assertFalse($stream->fullyConsumed());
        $this->assertSame('abcdef', $stream->getContents());
    }

    public function testUsesExplicitLengthForOpenResource(): void
    {
        $resource = \fopen('php://temp', 'r+');
        if ($resource === false) {
            $this->fail('Unable to create test resource.');
        }

        \fwrite($resource, 'abcdef');
        \rewind($resource);
        $stream = new ResourceRequestStream($resource, length: 3);

        $this->assertSame(3, $stream->length());
        $this->assertSame(3, $stream->getSize());
        $this->assertSame(\hash('sha256', 'abc'), $stream->hash());
        $this->assertSame('abc', $stream->read(6));
        $this->assertSame('', $stream->read(1));
        $this->assertTrue($stream->eof());
    }

    public function testStringifiesExplicitLengthOnly(): void
    {
        $resource = \fopen('php://temp', 'r+');
        if ($resource === false) {
            $this->fail('Unable to create test resource.');
        }

        \fwrite($resource, 'abcdef');
        \rewind($resource);

        $this->assertSame(
            'abc',
            (string) new ResourceRequestStream($resource, length: 3)
        );
    }

    public function testSeekBeforeReadPreservesExplicitLength(): void
    {
        $resource = \fopen('php://temp', 'r+');
        if ($resource === false) {
            $this->fail('Unable to create test resource.');
        }

        \fwrite($resource, 'abcdef');
        \rewind($resource);
        $stream = new ResourceRequestStream($resource, length: 3);

        $stream->seek(2);

        $this->assertSame(3, $stream->length());
        $this->assertSame('cde', $stream->getContents());
    }

    public function testAcceptsNonSeekableResourceWithExplicitLength(): void
    {
        $command = \escapeshellarg(PHP_BINARY)
            . ' -r '
            . \escapeshellarg("echo 'payload';");
        $resource = \popen($command, 'r');
        if ($resource === false) {
            $this->fail('Unable to open test process pipe.');
        }

        try {
            $stream = new ResourceRequestStream($resource, length: 7);

            $this->assertFalse($stream->isSeekable());
            $this->assertFalse($stream->isReplayable());
            $this->assertTrue($stream->isAvailable());
            $this->assertSame(7, $stream->length());
            $this->assertSame('payload', $stream->getContents());
            $this->assertTrue($stream->fullyConsumed());
            $this->assertFalse($stream->isAvailable());
        } finally {
            if (\is_resource($resource)) {
                \pclose($resource);
            }
        }
    }

    public function testReadZeroReturnsEmptyString(): void
    {
        $stream = ResourceRequestStream::temp('payload');

        $this->assertSame('', $stream->read(0));
        $this->assertSame('pay', $stream->read(3));
    }

    public function testReadRejectsNegativeLength(): void
    {
        $stream = ResourceRequestStream::temp('payload');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Read length must be non-negative.'
        );

        $stream->read(-1);
    }

    public function testReadsNonSeekablePipeInChunks(): void
    {
        $command = \escapeshellarg(PHP_BINARY)
            . ' -r '
            . \escapeshellarg("echo 'abcdef';");
        $resource = \popen($command, 'r');
        if ($resource === false) {
            $this->fail('Unable to open test process pipe.');
        }

        try {
            $stream = new ResourceRequestStream($resource);

            $this->assertFalse($stream->isSeekable());
            $this->assertTrue($stream->isReadable());
            $this->assertSame(0, $stream->tell());
            $this->assertSame('ab', $stream->read(2));
            $this->assertTrue($stream->isReadable());
            $this->assertSame(2, $stream->tell());
            $this->assertSame('cd', $stream->read(2));
            $this->assertSame('ef', $stream->read(2));
            $this->assertSame(6, $stream->tell());
            $this->assertSame('', $stream->read(2));
            $this->assertTrue($stream->eof());
        } finally {
            if (\is_resource($resource)) {
                \pclose($resource);
            }
        }
    }

    public function testReadsNonSeekableSocketToEof(): void
    {
        $pair = \stream_socket_pair(
            STREAM_PF_UNIX,
            STREAM_SOCK_STREAM,
            STREAM_IPPROTO_IP
        );
        if ($pair === false) {
            $this->fail('Unable to create socket pair.');
        }

        [$read, $write] = $pair;
        \fwrite($write, 'abcdef');
        \fclose($write);

        try {
            $stream = new ResourceRequestStream($read);

            $this->assertFalse($stream->isSeekable());
            $this->assertSame('abc', $stream->read(3));
            $this->assertSame('def', $stream->getContents());
            $this->assertTrue($stream->eof());
        } finally {
            if (\is_resource($read)) {
                \fclose($read);
            }
        }
    }

    public function testNonSeekableSeekAndRewindRaise(): void
    {
        $command = \escapeshellarg(PHP_BINARY)
            . ' -r '
            . \escapeshellarg("echo 'abcdef';");
        $resource = \popen($command, 'r');
        if ($resource === false) {
            $this->fail('Unable to open test process pipe.');
        }

        try {
            $stream = new ResourceRequestStream($resource);
            $stream->read(2);

            $this->assertFalse($stream->isSeekable());

            try {
                $stream->seek(0);
                $this->fail('Expected seek to fail.');
            } catch (\RuntimeException $exception) {
                $this->assertSame(
                    'Stream is not seekable.',
                    $exception->getMessage()
                );
            }

            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Stream is not seekable.');
            $stream->rewind();
        } finally {
            if (\is_resource($resource)) {
                \pclose($resource);
            }
        }
    }

    public function testHashesExplicitLengthForLazyResource(): void
    {
        $stream = ResourceRequestStream::create(
            static function () {
                $resource = \fopen('php://temp', 'r+');
                if ($resource === false) {
                    throw new \RuntimeException(
                        'Unable to create test resource.'
                    );
                }

                \fwrite($resource, 'abcdef');
                \rewind($resource);

                return $resource;
            },
            3
        );

        $this->assertSame(\hash('sha256', 'abc'), $stream->hash());
        $this->assertSame('abc', $stream->getContents());
        $this->assertSame('', $stream->getContents());
        $this->assertTrue($stream->fullyConsumed());
    }

    public function testLazilyOpensFreshResources(): void
    {
        $openCount = 0;
        $openedResources = [];
        $stream = ResourceRequestStream::create(
            static function () use (
                &$openCount,
                &$openedResources
            ) {
                ++$openCount;
                $resource = \fopen('php://temp', 'r+');
                if ($resource === false) {
                    throw new \RuntimeException(
                        'Unable to create test resource.'
                    );
                }

                \fwrite($resource, 'payload');
                \rewind($resource);
                $openedResources[] = $resource;

                return $resource;
            },
            7
        );

        $this->assertSame(0, $openCount);
        $this->assertSame(7, $stream->length());
        $this->assertTrue($stream->isReplayable());
        $this->assertTrue($stream->isAvailable());
        $this->assertSame('payload', $stream->getContents());
        $this->assertSame(1, $openCount);
        $this->assertTrue($stream->started());

        $first = $openedResources[0];
        $stream->rewindForRetry();

        $this->assertFalse(\is_resource($first));
        $this->assertSame(1, $openCount);
        $this->assertFalse($stream->started());
        $this->assertSame('payload', $stream->getContents());
        $this->assertSame(2, $openCount);

        $stream->close();

        $this->assertFalse($stream->isAvailable());
        $this->assertFalse($stream->isReplayable());
    }

    public function testHashesWithIndependentResource(): void
    {
        $openCount = 0;
        $stream = ResourceRequestStream::create(
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

        $this->assertSame(\hash('sha256', 'payload'), $stream->hash());
        $this->assertSame(1, $openCount);
        $this->assertFalse($stream->started());
        $this->assertSame('payload', $stream->getContents());
        $this->assertSame(2, $openCount);
    }

    public function testHashFailureRestoresResourcePosition(): void
    {
        $stream = ResourceRequestStream::temp('abcdef');
        $stream->seek(3);
        $failed = false;

        try {
            $stream->hash('not-a-real-hash');
            $this->fail('Expected hashing to fail.');
        } catch (\ValueError) {
            $failed = true;
        }

        $this->assertTrue($failed);
        $this->assertSame(3, $stream->tell());
        $this->assertSame('def', $stream->getContents());
    }

    public function testReopensFileForRetry(): void
    {
        $path = \tempnam(\sys_get_temp_dir(), 'smithy-body-');
        if ($path === false) {
            $this->fail('Unable to create temporary file.');
        }

        \file_put_contents($path, 'payload');
        $stream = null;

        try {
            $stream = ResourceRequestStream::fromFile($path);

            $this->assertSame(7, $stream->length());
            $this->assertSame('payload', $stream->getContents());

            $stream->rewindForRetry();

            $this->assertSame('payload', $stream->getContents());
        } finally {
            $stream?->close();
            @\unlink($path);
        }
    }

    public function testRejectsInvalidOpenerResult(): void
    {
        $stream = ResourceRequestStream::create(
            static fn() => 'not a resource'
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Resource body opener must return a valid PHP stream resource.'
        );

        $stream->getContents();
    }

    public function testRejectsNonStreamResource(): void
    {
        $process = self::openProcessResource();

        try {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage(
                'ResourceRequestStream requires a valid PHP stream resource.'
            );

            new ResourceRequestStream($process);
        } finally {
            if (\is_resource($process)) {
                \proc_close($process);
            }
        }
    }

    public function testRejectsNonStreamResourceFromOpener(): void
    {
        $process = self::openProcessResource();
        $stream = ResourceRequestStream::create(static fn() => $process);

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage(
                'Resource body opener must return a valid PHP stream resource.'
            );

            $stream->getContents();
        } finally {
            if (\is_resource($process)) {
                \proc_close($process);
            }
        }
    }

    /**
     * @return resource
     */
    private static function openProcessResource()
    {
        $process = \proc_open(
            PHP_BINARY . ' -r ' . \escapeshellarg(''),
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w']
            ],
            $pipes
        );

        foreach ($pipes as $pipe) {
            if (\is_resource($pipe)) {
                \fclose($pipe);
            }
        }

        if (!\is_resource($process)) {
            self::fail('Unable to create test process resource.');
        }

        return $process;
    }
}
