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

    public function testRequestTableOmitsUnsupportedAlgorithms(): void
    {
        $request = ChecksumAlgorithms::request();

        $this->assertArrayNotHasKey('CRC64NVME', $request);
        $this->assertSame(
            ['CRC32', 'CRC32C', 'SHA1', 'SHA256', 'SHA512',
                'XXHASH64', 'XXHASH3', 'XXHASH128'],
            \array_keys($request)
        );
        foreach ($request as $algorithm) {
            $this->assertTrue($algorithm->isAvailable());
        }
    }

    public function testResponseTableIsFastestFirst(): void
    {
        $this->assertSame(
            ['CRC32C', 'CRC32', 'XXHASH64', 'XXHASH3',
                'XXHASH128', 'SHA1', 'SHA256', 'SHA512'],
            \array_keys(ChecksumAlgorithms::response())
        );
    }

    public function testByNameIsCaseInsensitiveAndReturnsNullForUnknown(): void
    {
        $this->assertSame('CRC32C', ChecksumAlgorithms::byName('crc32c')?->name);
        $this->assertNull(ChecksumAlgorithms::byName('MD5'));
        $this->assertNull(ChecksumAlgorithms::byName('CRC64NVME'));
        $this->assertNull(ChecksumAlgorithms::byName('not-an-algorithm'));
    }

    public function testSupportedKeepsModeledAvailableFastestFirst(): void
    {
        $supported = ChecksumAlgorithms::supported(
            ['SHA256', 'CRC32', 'CRC32C']
        );

        $this->assertSame(
            ['CRC32C', 'CRC32', 'SHA256'],
            \array_keys($supported)
        );
    }

    public function testSupportedDropsUnmodeledAndUnsupportedAlgorithms(): void
    {
        $supported = ChecksumAlgorithms::supported(['CRC32', 'CRC64NVME']);

        $this->assertSame(['CRC32'], \array_keys($supported));
    }

    public function testResolveRequestReturnsSupportedAlgorithm(): void
    {
        $algorithm = ChecksumAlgorithms::resolveRequest('crc32c');

        $this->assertSame('CRC32C', $algorithm->name);
        $this->assertSame('crc32c', $algorithm->hashAlgorithm);
    }

    public function testResolveRequestRejectsUnsupportedAlgorithm(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Unsupported flexible checksum algorithm 'CRC64NVME'. Supported "
            . 'algorithms are: CRC32, CRC32C, SHA1, SHA256, SHA512, '
            . 'XXHASH64, XXHASH3, XXHASH128.'
        );

        ChecksumAlgorithms::resolveRequest('CRC64NVME');
    }

    public function testResolveRequestRejectsUnknownAlgorithm(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("'MD5'");

        ChecksumAlgorithms::resolveRequest('MD5');
    }
}
