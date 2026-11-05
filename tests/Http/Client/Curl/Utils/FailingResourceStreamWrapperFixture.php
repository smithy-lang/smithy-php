<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Http\Client\Curl\Utils;

/**
 * Throw when a user-space resource wrapper is read.
 */
final class FailingResourceStreamWrapperFixture
{
    /** Stream wrapper context supplied by PHP. */
    public mixed $context = null;

    /**
     * Open the failing resource.
     */
    public function stream_open(
        string $path,
        string $mode,
        int $options,
        ?string &$openedPath
    ): bool
    {
        return true;
    }

    /**
     * Fail every source read.
     *
     * @throws \RuntimeException Always.
     */
    public function stream_read(int $length): string
    {
        throw new \RuntimeException('Resource stream read failed.');
    }

    /**
     * Report that the source is before EOF.
     */
    public function stream_eof(): bool
    {
        return false;
    }

    /**
     * Report the initial position.
     */
    public function stream_tell(): int
    {
        return 0;
    }

    /**
     * Accept rewinds to the initial position.
     */
    public function stream_seek(
        int $offset,
        int $whence = SEEK_SET
    ): bool
    {
        return $offset === 0;
    }

    /**
     * Report readable file metadata.
     *
     * @return array{mode: int, size: int}
     */
    public function stream_stat(): array
    {
        return [
            'mode' => 0100444,
            'size' => 7
        ];
    }
}
