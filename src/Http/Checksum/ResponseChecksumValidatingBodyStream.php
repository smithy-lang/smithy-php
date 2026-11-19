<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Http\Checksum;

use Smithy\Http\Message\RequestStreamInterface;

/**
 * Validate a selected response checksum as the payload is consumed.
 */
final class ResponseChecksumValidatingBodyStream implements RequestStreamInterface
{
    private \HashContext $hashContext;

    private readonly int $startPosition;

    private bool $started = false;

    private bool $validationComplete = false;

    private bool $validated = false;

    private ?ChecksumMismatchException $failure = null;

    public function __construct(
        private readonly RequestStreamInterface $inner,
        private readonly ResponseChecksumSelection $selection,
    ) {
        $this->startPosition = $inner->isSeekable() ? $inner->tell() : 0;
        $this->hashContext = \hash_init($selection->hashAlgorithm);
    }

    public function __toString(): string
    {
        return $this->getContents();
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
        if ($this->validationComplete) {
            $this->inner->seek($offset, $whence);

            return;
        }

        if ($whence === SEEK_CUR && $offset === 0) {
            return;
        }
        if ($whence === SEEK_SET && $offset === $this->startPosition) {
            $this->inner->seek($offset);
            $this->resetValidation();

            return;
        }

        throw new \RuntimeException(
            'Cannot seek a response body before checksum validation completes.'
        );
    }

    public function rewind(): void
    {
        if (!$this->inner->isSeekable()) {
            throw new \RuntimeException('Stream is not seekable.');
        }

        $this->inner->seek($this->startPosition);
        $this->resetValidation();
    }

    public function rewindForRetry(): void
    {
        $this->rewind();
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write(string $string): int
    {
        throw new \RuntimeException(
            'Checksum-validating response bodies are not writable.'
        );
    }

    public function isReadable(): bool
    {
        return $this->inner->isReadable();
    }

    public function read(int $length): string
    {
        $this->throwIfFailed();

        $chunk = $this->inner->read($length);
        $this->update($chunk);

        return $chunk;
    }

    public function getContents(): string
    {
        $this->throwIfFailed();

        $contents = $this->inner->getContents();
        $this->update($contents);

        return $contents;
    }

    public function getMetadata(?string $key = null)
    {
        return $this->inner->getMetadata($key);
    }

    public function hash(string $algo = 'sha256', bool $raw = false): string
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
        return $this->started;
    }

    public function fullyConsumed(): bool
    {
        return $this->inner->fullyConsumed();
    }

    public function isValidationComplete(): bool
    {
        return $this->validationComplete;
    }

    public function isValidated(): bool
    {
        return $this->validated;
    }

    public function getAlgorithm(): string
    {
        return $this->selection->algorithm;
    }

    private function update(string $bytes): void
    {
        if ($this->validationComplete) {
            return;
        }

        if ($bytes !== '') {
            $this->started = true;
            \hash_update($this->hashContext, $bytes);
        }

        if ($this->inner->fullyConsumed() || $this->inner->eof()) {
            $this->completeValidation();
        }
    }

    private function completeValidation(): void
    {
        if ($this->validationComplete) {
            return;
        }

        $calculated = \hash_final($this->hashContext, true);
        $this->validationComplete = true;
        if (\hash_equals($this->selection->checksum, $calculated)) {
            $this->validated = true;

            return;
        }

        $this->failure = new ChecksumMismatchException(
            algorithm: $this->selection->algorithm,
            expectedChecksum: $this->selection->encodedChecksum,
            calculatedChecksum: \base64_encode($calculated),
        );
        throw $this->failure;
    }

    private function resetValidation(): void
    {
        $this->hashContext = \hash_init($this->selection->hashAlgorithm);
        $this->started = false;
        $this->validationComplete = false;
        $this->validated = false;
        $this->failure = null;
    }

    private function throwIfFailed(): void
    {
        if ($this->failure !== null) {
            throw $this->failure;
        }
    }
}
