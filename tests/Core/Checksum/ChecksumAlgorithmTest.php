<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Core\Checksum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Checksum\Checksum;
use Smithy\Core\Checksum\ChecksumAlgorithm;

#[CoversClass(ChecksumAlgorithm::class)]
final class ChecksumAlgorithmTest extends TestCase
{
    public function testValueIsTheIdentifier(): void
    {
        $this->assertSame('CRC32', ChecksumAlgorithm::CRC32->value);
        $this->assertSame('XXHASH128', ChecksumAlgorithm::XXHASH128->value);
    }

    public function testCreateReturnsAWorkingChecksum(): void
    {
        $checksum = ChecksumAlgorithm::CRC32->create();

        $this->assertInstanceOf(Checksum::class, $checksum);
        $checksum->update('Hello world');
        $this->assertSame(
            \hash('crc32b', 'Hello world', true),
            $checksum->digest()
        );
    }

    public function testDigestLength(): void
    {
        $this->assertSame(4, ChecksumAlgorithm::CRC32->digestLength());
        $this->assertSame(32, ChecksumAlgorithm::SHA256->digestLength());
    }

    public function testSupportedAlgorithmsFollowHashAlgos(): void
    {
        foreach (ChecksumAlgorithm::supported() as $algorithm) {
            $this->assertTrue($algorithm->isSupported());
        }

        $this->assertContains(
            ChecksumAlgorithm::CRC32,
            ChecksumAlgorithm::supported()
        );
    }

    public function testTryFromNameIsCaseInsensitive(): void
    {
        $this->assertSame(
            ChecksumAlgorithm::CRC32C,
            ChecksumAlgorithm::tryFromName('crc32c')
        );
        $this->assertNull(ChecksumAlgorithm::tryFromName('MD5'));
        $this->assertNull(ChecksumAlgorithm::tryFromName('CRC64NVME'));
    }

    public function testResolveRequestReturnsSupportedAlgorithm(): void
    {
        $this->assertSame(
            ChecksumAlgorithm::SHA256,
            ChecksumAlgorithm::resolveRequest('sha256')
        );
    }

    public function testResolveRequestRejectsUnsupportedNamingSupported(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Unsupported flexible checksum algorithm 'CRC64NVME'. Supported "
            . 'algorithms are: CRC32, CRC32C, SHA1, SHA256, SHA512, '
            . 'XXHASH64, XXHASH3, XXHASH128.'
        );

        ChecksumAlgorithm::resolveRequest('CRC64NVME');
    }
}
