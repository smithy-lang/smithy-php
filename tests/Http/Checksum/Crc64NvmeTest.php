<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Http\Checksum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Http\Checksum\Crc64Nvme;

#[CoversClass(Crc64Nvme::class)]
final class Crc64NvmeTest extends TestCase
{
    public function testWellKnownCheckValue(): void
    {
        $this->assertSame(
            'ae8b14860a799888',
            \bin2hex(Crc64Nvme::hash('123456789'))
        );
    }

    public function testEmptyInput(): void
    {
        $this->assertSame(
            '0000000000000000',
            \bin2hex(Crc64Nvme::hash(''))
        );
    }

    #[DataProvider('vectors')]
    public function testKnownVectors(string $input, string $expectedHex): void
    {
        $this->assertSame($expectedHex, \bin2hex(Crc64Nvme::hash($input)));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function vectors(): array
    {
        return [
            'check value' => ['123456789', 'ae8b14860a799888'],
            'empty' => ['', '0000000000000000'],
        ];
    }

    public function testIncrementalMatchesOneShot(): void
    {
        $data = \str_repeat('smithy-php-', 5000);

        $incremental = new Crc64Nvme();
        foreach (\str_split($data, 97) as $chunk) {
            $incremental->update($chunk);
        }

        $this->assertSame(
            Crc64Nvme::hash($data),
            $incremental->digest()
        );
    }
}
