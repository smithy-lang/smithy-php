<?php

declare(strict_types=1);

namespace Smithy\Tests\Http\Message\Utils;

use Psr\Http\Message\StreamInterface;

/**
 * Provide a configurable PSR-7 stream for message tests.
 */
final class GenericStreamFixture implements StreamInterface
{
    private int $offset = 0;

    /**
     * Create a generic test stream.
     */
    public function __construct(
        private readonly string $content,
        private readonly bool $sizeKnown = true,
        private readonly bool $seekable = true
    ) {}

    /** @inheritDoc */
    public function __toString(): string
    {
        return $this->content;
    }

    /** @inheritDoc */
    public function close(): void {}

    /** @inheritDoc */
    public function detach()
    {
        return null;
    }

    /** @inheritDoc */
    public function getSize(): ?int
    {
        return $this->sizeKnown ? \strlen($this->content) : null;
    }

    /** @inheritDoc */
    public function tell(): int
    {
        return $this->offset;
    }

    /** @inheritDoc */
    public function eof(): bool
    {
        return $this->offset >= \strlen($this->content);
    }

    /** @inheritDoc */
    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    /** @inheritDoc */
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (!$this->seekable) {
            throw new \RuntimeException('Stream is not seekable.');
        }

        $length = \strlen($this->content);
        $newOffset = match ($whence) {
            SEEK_SET => $offset,
            SEEK_CUR => $this->offset + $offset,
            SEEK_END => $length + $offset,
            default => throw new \InvalidArgumentException(
                'Invalid seek origin.'
            )
        };

        if ($newOffset < 0 || $newOffset > $length) {
            throw new \RuntimeException('Seek position is outside the stream.');
        }

        $this->offset = $newOffset;
    }

    /** @inheritDoc */
    public function rewind(): void
    {
        $this->seek(0);
    }

    /** @inheritDoc */
    public function isWritable(): bool
    {
        return false;
    }

    /** @inheritDoc */
    public function write(string $string): int
    {
        throw new \RuntimeException('Stream is not writable.');
    }

    /** @inheritDoc */
    public function isReadable(): bool
    {
        return true;
    }

    /** @inheritDoc */
    public function read(int $length): string
    {
        $data = \substr($this->content, $this->offset, $length);
        $this->offset += \strlen($data);

        return $data;
    }

    /** @inheritDoc */
    public function getContents(): string
    {
        $data = \substr($this->content, $this->offset);
        $this->offset = \strlen($this->content);

        return $data;
    }

    /** @inheritDoc */
    public function getMetadata(?string $key = null)
    {
        $metadata = ['fixture' => true];

        return $key === null ? $metadata : ($metadata[$key] ?? null);
    }
}
