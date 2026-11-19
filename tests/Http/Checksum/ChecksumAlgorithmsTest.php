<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Http\Checksum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Http\Checksum\ChecksumAlgorithm;
use Smithy\Http\Checksum\ChecksumAlgorithms;

#[CoversClass(ChecksumAlgorithms::class)]
#[CoversClass(ChecksumAlgorithm::class)]
final class ChecksumAlgorithmsTest extends TestCase
{
    public function testDefaultIsCrc32(): void
    {
        $default = ChecksumAlgorithms::default();

        $this->assertSame('CRC32', $default->name);
        $this->assertSame('crc32b', $default->hashAlgorithm);
        $this->assertSame('x-amz-checksum-crc32', $default->header);
    }

    public function testRequestTableIncludesCrc64Nvme(): void
    {
        $request = ChecksumAlgorithms::request();

        $this->assertArrayHasKey('CRC64NVME', $request);
        $crc64 = $request['CRC64NVME'];
        $this->assertSame('crc64nvme', $crc64->hashAlgorithm);
        $this->assertSame('x-amz-checksum-crc64nvme', $crc64->header);
        $this->assertTrue($crc64->usesCrc64Nvme());
        $this->assertSame(
            ['CRC32', 'CRC32C', 'CRC64NVME', 'SHA1', 'SHA256', 'SHA512',
                'XXHASH64', 'XXHASH3', 'XXHASH128'],
            \array_keys($request)
        );
    }

    public function testResponseTableIsFastestFirstAndIncludesCrc64Nvme(): void
    {
        $this->assertSame(
            ['CRC32C', 'CRC32', 'CRC64NVME', 'XXHASH64', 'XXHASH3',
                'XXHASH128', 'SHA1', 'SHA256', 'SHA512'],
            \array_keys(ChecksumAlgorithms::response())
        );
    }

    public function testByNameIsCaseInsensitiveAndReturnsNullForUnknown(): void
    {
        $this->assertSame('CRC32C', ChecksumAlgorithms::byName('crc32c')?->name);
        $this->assertNull(ChecksumAlgorithms::byName('MD5'));
        $this->assertNull(ChecksumAlgorithms::byName('not-an-algorithm'));
    }

    public function testSupportedKeepsModeledAvailableFastestFirst(): void
    {
        $supported = ChecksumAlgorithms::supported(
            ['SHA256', 'CRC32', 'CRC64NVME']
        );

        $this->assertSame(
            ['CRC32', 'CRC64NVME', 'SHA256'],
            \array_keys($supported)
        );
    }

    public function testSupportedDropsUnmodeledAlgorithms(): void
    {
        $supported = ChecksumAlgorithms::supported(['CRC32']);

        $this->assertSame(['CRC32'], \array_keys($supported));
    }

    public function testCrc64NvmeIsAlwaysAvailable(): void
    {
        $crc64 = ChecksumAlgorithms::byName('CRC64NVME');

        $this->assertNotNull($crc64);
        $this->assertTrue($crc64->isAvailable());
    }
}
