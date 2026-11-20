<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Core\Checksum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Checksum\HashChecksum;

#[CoversClass(HashChecksum::class)]
final class HashChecksumTest extends TestCase
{
    public function testDigestMatchesHashOfWholeInput(): void
    {
        $checksum = new HashChecksum('crc32b');
        $checksum->update('Hello world');

        $this->assertSame(
            \hash('crc32b', 'Hello world', true),
            $checksum->digest()
        );
    }

    public function testIncrementalUpdatesMatchOneShot(): void
    {
        $checksum = new HashChecksum('sha256');
        $checksum->update('Hello ');
        $checksum->update('world');

        $this->assertSame(
            \hash('sha256', 'Hello world', true),
            $checksum->digest()
        );
    }

    public function testDigestLengthIsRawByteCount(): void
    {
        $this->assertSame(4, (new HashChecksum('crc32b'))->digestLength());
        $this->assertSame(32, (new HashChecksum('sha256'))->digestLength());
    }

    public function testUnknownAlgorithmThrows(): void
    {
        $this->expectException(\ValueError::class);

        new HashChecksum('not-a-real-algorithm');
    }
}
