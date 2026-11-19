<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Http\Checksum;

/**
 * Compute the CRC-64/NVME (a.k.a. CRC-64/Rocksoft) checksum in pure PHP.
 *
 * PHP's hash extension exposes CRC32 variants and the xxHash family but not
 * CRC-64/NVME, so this table-driven implementation fills the gap. It supports
 * incremental updates so streaming bodies hash the payload one chunk at a time.
 *
 * Parameters (reflected form): polynomial 0x9a6c9329ac4bc9b5, initial value
 * all ones, input and output reflected, final xor all ones. The check value
 * for the ASCII string "123456789" is 0xae8b14860a799888.
 *
 * Requires a 64-bit PHP build so a checksum fits in a native integer.
 */
final class Crc64Nvme
{
    /**
     * The reflected CRC-64/NVME polynomial, built from its bytes so the
     * literal survives on a 64-bit build without overflowing to a float.
     */
    private const POLYNOMIAL_BYTES = "\x9a\x6c\x93\x29\xac\x4b\xc9\xb5";

    /**
     * @var list<int>|null
     */
    private static ?array $table = null;

    private int $crc;

    public function __construct()
    {
        $this->crc = -1;
    }

    /**
     * Feed a chunk of bytes into the running checksum.
     */
    public function update(string $bytes): void
    {
        $table = self::table();
        $crc = $this->crc;
        $length = \strlen($bytes);
        for ($i = 0; $i < $length; $i++) {
            $crc = self::unsignedShiftRight($crc, 8)
                ^ $table[($crc ^ \ord($bytes[$i])) & 0xff];
        }
        $this->crc = $crc;
    }

    /**
     * Return the finished checksum as 8 big-endian bytes.
     */
    public function digest(): string
    {
        return \pack('J', $this->crc ^ -1);
    }

    /**
     * Compute the checksum of a complete string in one call.
     */
    public static function hash(string $data): string
    {
        $crc = new self();
        $crc->update($data);

        return $crc->digest();
    }

    /**
     * Logically shift a 64-bit integer right, treating it as unsigned.
     */
    private static function unsignedShiftRight(int $value, int $bits): int
    {
        if ($bits === 0) {
            return $value;
        }

        return ($value >> $bits) & (\PHP_INT_MAX >> ($bits - 1));
    }

    /**
     * Build and cache the 256-entry lookup table.
     *
     * @return list<int>
     */
    private static function table(): array
    {
        if (self::$table !== null) {
            return self::$table;
        }

        $polynomial = \unpack('J', self::POLYNOMIAL_BYTES)[1];
        $table = [];
        for ($i = 0; $i < 256; $i++) {
            $crc = $i;
            for ($bit = 0; $bit < 8; $bit++) {
                $crc = ($crc & 1) === 1
                    ? self::unsignedShiftRight($crc, 1) ^ $polynomial
                    : self::unsignedShiftRight($crc, 1);
            }
            $table[$i] = $crc;
        }

        return self::$table = $table;
    }
}
