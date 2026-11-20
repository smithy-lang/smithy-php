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
use Smithy\Http\Checksum\ExpectedChecksum;

#[CoversClass(ExpectedChecksum::class)]
final class ExpectedChecksumTest extends TestCase
{
    public function testDecodesDigestLazily(): void
    {
        $expected = new ExpectedChecksum(ChecksumAlgorithm::CRC32, 'i9aeUg==');

        $this->assertSame(ChecksumAlgorithm::CRC32, $expected->algorithm);
        $this->assertSame('i9aeUg==', $expected->base64Digest);
        $this->assertSame(\base64_decode('i9aeUg==', true), $expected->digest());
    }

    public function testMatchesComparesRawDigest(): void
    {
        $expected = new ExpectedChecksum(ChecksumAlgorithm::CRC32, 'i9aeUg==');

        $this->assertTrue(
            $expected->matches(\hash('crc32b', 'Hello world', true))
        );
        $this->assertFalse($expected->matches('nope'));
    }

    public function testMalformedBase64NeverMatches(): void
    {
        $expected = new ExpectedChecksum(
            ChecksumAlgorithm::CRC32,
            'not-base64!'
        );

        $this->assertFalse(
            $expected->matches(\hash('crc32b', 'Hello world', true))
        );
    }
}
