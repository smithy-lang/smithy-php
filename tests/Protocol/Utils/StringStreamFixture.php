<?php

declare(strict_types=1);

namespace Smithy\Tests\Protocol\Utils;

use Psr\Http\Message\StreamInterface;

final class StringStreamFixture implements StreamInterface
{
    private int $position = 0;

    public function __construct(
        private string $contents
    ) {}

    public function __toString(): string
    {
        return $this->contents;
    }

    public function close(): void {}

    public function detach()
    {
        return null;
    }

    public function getSize(): ?int
    {
        return \strlen($this->contents);
    }

    public function tell(): int
    {
        return $this->position;
    }

    public function eof(): bool
    {
        return $this->position >= \strlen($this->contents);
    }

    public function isSeekable(): bool
    {
        return true;
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        $newPosition = match ($whence) {
            SEEK_CUR => $this->position + $offset,
            SEEK_END => \strlen($this->contents) + $offset,
            default => $offset
        };

        $this->position = \max(0, $newPosition);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function isWritable(): bool
    {
        return true;
    }

    public function write($string): int
    {
        $before = \substr($this->contents, 0, $this->position);
        $after = \substr($this->contents, $this->position + \strlen($string));
        $this->contents = $before . $string . $after;
        $this->position += \strlen($string);

        return \strlen($string);
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read($length): string
    {
        $chunk = \substr($this->contents, $this->position, $length);
        $this->position += \strlen($chunk);

        return $chunk;
    }

    public function getContents(): string
    {
        $contents = \substr($this->contents, $this->position);
        $this->position = \strlen($this->contents);

        return $contents;
    }

    public function getMetadata($key = null): mixed
    {
        return $key === null ? [] : null;
    }
}
