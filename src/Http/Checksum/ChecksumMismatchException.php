<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Http\Checksum;

/**
 * Raised when a response payload does not match its selected checksum.
 */
final class ChecksumMismatchException extends \RuntimeException
{
    public function __construct(
        public readonly string $algorithm,
        public readonly string $expectedChecksum,
        public readonly string $calculatedChecksum,
    ) {
        parent::__construct(
            "Response checksum mismatch for {$algorithm}: expected "
            . "{$expectedChecksum}, calculated {$calculatedChecksum}."
        );
    }
}
