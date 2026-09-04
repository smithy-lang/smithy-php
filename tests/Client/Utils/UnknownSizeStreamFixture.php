<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Utils;

use Psr\Http\Message\StreamInterface;

final class UnknownSizeStreamFixture implements StreamInterface
{
    private int $offset = 0;

    public function __construct(private readonly string $content) {}

    public function __toString(): string
    {
        return $this->content;
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
        return true;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
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
        $this->offset = 0;
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write(string $string): int
    {
        throw new \RuntimeException(
            'UnknownSizeStreamFixture is not writable.'
        );
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
        $data = \substr($this->content, $this->offset);
        $this->offset = \strlen($this->content);

        return $data;
    }

    public function getMetadata(?string $key = null): mixed
    {
        return $key === null ? [] : null;
    }
}
