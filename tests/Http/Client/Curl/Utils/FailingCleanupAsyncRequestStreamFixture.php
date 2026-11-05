<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Http\Client\Curl\Utils;

use Smithy\Async\EventLoopInterface;
use Smithy\Http\Message\{
    AsyncRequestStream,
    AsyncRequestStreamInterface
};

final class FailingCleanupAsyncRequestStreamFixture implements
    AsyncRequestStreamInterface
{
    private readonly AsyncRequestStream $inner;

    public function __construct(
        private readonly \Throwable $cleanupError,
        EventLoopInterface $eventLoop,
        ?int $expectedLength = null
    )
    {
        $this->inner = new AsyncRequestStream(
            $eventLoop,
            expectedLength: $expectedLength
        );
    }

    public function __toString(): string
    {
        return (string) $this->inner;
    }

    public function close(): void
    {
        $this->inner->close();
    }

    public function detach()
    {
        return $this->inner->detach();
    }

    public function getSize(): ?int
    {
        return $this->inner->getSize();
    }

    public function length(): ?int
    {
        return $this->inner->length();
    }

    public function tell(): int
    {
        return $this->inner->tell();
    }

    public function eof(): bool
    {
        return $this->inner->eof();
    }

    public function isSeekable(): bool
    {
        return $this->inner->isSeekable();
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $this->inner->seek($offset, $whence);
    }

    public function rewind(): void
    {
        $this->inner->rewind();
    }

    public function rewindForRetry(): void
    {
        $this->inner->rewindForRetry();
    }

    public function isWritable(): bool
    {
        return $this->inner->isWritable();
    }

    public function write(string $string): int
    {
        return $this->inner->write($string);
    }

    public function isReadable(): bool
    {
        return $this->inner->isReadable();
    }

    public function read(int $length): string
    {
        return $this->inner->read($length);
    }

    public function getContents(): string
    {
        return $this->inner->getContents();
    }

    public function getMetadata(?string $key = null)
    {
        return $this->inner->getMetadata($key);
    }

    public function hash(
        string $algo = 'sha256',
        bool $raw = false
    ): string
    {
        return $this->inner->hash($algo, $raw);
    }

    public function isReplayable(): bool
    {
        return $this->inner->isReplayable();
    }

    public function isAvailable(): bool
    {
        return $this->inner->isAvailable();
    }

    public function started(): bool
    {
        return $this->inner->started();
    }

    public function fullyConsumed(): bool
    {
        return $this->inner->fullyConsumed();
    }

    public function isReady(): bool
    {
        return $this->inner->isReady();
    }

    public function awaitReadable(): void
    {
        $this->inner->awaitReadable();
    }

    public function subscribe(\Closure $callback): int
    {
        return $this->inner->subscribe($callback);
    }

    public function unsubscribe(int $subscriptionId): void
    {
        $this->inner->unsubscribe($subscriptionId);

        throw $this->cleanupError;
    }

    public function detachConsumer(): void
    {
        $this->inner->detachConsumer();
    }

    public function end(): void
    {
        $this->inner->end();
    }
}
