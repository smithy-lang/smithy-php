<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Auth\Aws\Utils;

use Psr\Http\Message\StreamInterface;

/**
 * A forward-only stream of unknown size that returns short reads, like a
 * pipe or socket.
 */
final class NonSeekableStreamFixture implements StreamInterface
{
    private int $offset = 0;

    public function __construct(
        private readonly string $content,
        private readonly int $maxRead = 7,
    ) {}

    public function __toString(): string
    {
        return $this->getContents();
    }

    public function close(): void {}

    public function detach(): mixed
    {
        return null;
    }

    public function getSize(): ?int
    {
        return null;
    }

    public function tell(): int
    {
        return $this->offset;
    }

    public function eof(): bool
    {
        return $this->offset >= \strlen($this->content);
    }

    public function isSeekable(): bool
    {
        return false;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        throw new \RuntimeException('NonSeekableStreamFixture is forward-only.');
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write(string $string): int
    {
        throw new \RuntimeException('NonSeekableStreamFixture is read-only.');
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read(int $length): string
    {
        $bytes = \substr(
            $this->content,
            $this->offset,
            \min($length, $this->maxRead)
        );
        $this->offset += \strlen($bytes);

        return $bytes;
    }

    public function getContents(): string
    {
        $bytes = \substr($this->content, $this->offset);
        $this->offset = \strlen($this->content);

        return $bytes;
    }

    public function getMetadata(?string $key = null): mixed
    {
        return $key === null ? [] : null;
    }
}
