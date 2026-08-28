<?php

declare(strict_types=1);

namespace Smithy\Tests\Http\Client\Curl\Utils;

use Psr\Http\Message\StreamInterface;

class TestStreamFixture implements StreamInterface
{
    private int $offset = 0;

    public function __construct(
        private readonly string $content,
        private readonly bool $sizeKnown,
        private readonly bool $seekable
    ) {}

    public function __toString(): string
    {
        return \substr($this->content, $this->offset);
    }

    public function close(): void {}

    public function detach()
    {
        return null;
    }

    public function getSize(): ?int
    {
        return $this->sizeKnown ? \strlen($this->content) : null;
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
        return $this->seekable;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (!$this->seekable) {
            throw new \RuntimeException('Stream is not seekable.');
        }

        $length = \strlen($this->content);
        $this->offset = match ($whence) {
            SEEK_SET => $offset,
            SEEK_CUR => $this->offset + $offset,
            SEEK_END => $length + $offset,
            default => throw new \InvalidArgumentException('Invalid whence.')
        };
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
        throw new \RuntimeException('Stream is not writable.');
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read(int $length): string
    {
        $data = \substr($this->content, $this->offset, $length);
        $this->offset += \strlen($data);

        return $data;
    }

    public function getContents(): string
    {
        $contents = \substr($this->content, $this->offset);
        $this->offset = \strlen($this->content);

        return $contents;
    }

    public function getMetadata(?string $key = null)
    {
        return $key === null ? [] : null;
    }
}
