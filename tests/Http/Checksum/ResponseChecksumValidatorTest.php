<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Http\Checksum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Checksum\ChecksumAlgorithm;
use Smithy\Http\Checksum\ResponseChecksumValidator;

#[CoversClass(ResponseChecksumValidator::class)]
final class ResponseChecksumValidatorTest extends TestCase
{
    public function testOrderMatchesTheSepPriority(): void
    {
        $this->assertSame(
            [
                ChecksumAlgorithm::CRC32C,
                ChecksumAlgorithm::CRC32,
                ChecksumAlgorithm::XXHASH64,
                ChecksumAlgorithm::XXHASH3,
                ChecksumAlgorithm::XXHASH128,
                ChecksumAlgorithm::SHA1,
                ChecksumAlgorithm::SHA256,
                ChecksumAlgorithm::SHA512,
            ],
            ResponseChecksumValidator::RESPONSE_VALIDATION_ORDER
        );
    }

    public function testCandidatesKeepModeledSupportedInPriorityOrder(): void
    {
        $candidates = ResponseChecksumValidator::candidates([
            ChecksumAlgorithm::SHA256,
            ChecksumAlgorithm::CRC32,
            ChecksumAlgorithm::CRC32C,
        ]);

        $this->assertSame(
            [
                ChecksumAlgorithm::CRC32C,
                ChecksumAlgorithm::CRC32,
                ChecksumAlgorithm::SHA256,
            ],
            $candidates
        );
    }

    public function testCandidatesDropUnmodeledAlgorithms(): void
    {
        $candidates = ResponseChecksumValidator::candidates([
            ChecksumAlgorithm::CRC32,
        ]);

        $this->assertSame([ChecksumAlgorithm::CRC32], $candidates);
    }
}
