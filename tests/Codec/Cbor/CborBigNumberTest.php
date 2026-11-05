<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codec\Cbor;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Codec\Cbor\CborBigNumber;

/**
 * Cover CBOR bignum conversion.
 */
final class CborBigNumberTest extends TestCase
{
    #[DataProvider('unsignedBytesProvider')]
    public function testConvertsUnsignedBytes(
        string $bytes,
        string $expected
    ): void
    {
        self::assertSame(
            $expected,
            CborBigNumber::fromUnsignedBytes($bytes)
        );
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function unsignedBytesProvider(): iterable
    {
        yield 'empty' => ['', '0'];
        yield 'zero' => ["\x00", '0'];
        yield 'one' => ["\x01", '1'];
        yield 'two bytes' => ["\x01\x00", '256'];
        yield 'uint32 maximum' => [
            \hex2bin('ffffffff'),
            '4294967295'
        ];
        yield 'uint64 maximum' => [
            \hex2bin('ffffffffffffffff'),
            '18446744073709551615'
        ];
        yield 'uint128 maximum' => [
            \hex2bin('ffffffffffffffffffffffffffffffff'),
            '340282366920938463463374607431768211455'
        ];
        yield 'leading zeroes' => [
            \hex2bin('0000ffffffff'),
            '4294967295'
        ];
    }

    public function testConvertsMaximumDefaultPayloadLength(): void
    {
        $value = CborBigNumber::fromUnsignedBytes(
            \str_repeat("\xFF", CborBigNumber::DEFAULT_MAX_BYTES)
        );

        self::assertMatchesRegularExpression('/^\d{2467}$/', $value);
    }

    public function testRejectsOversizedPayload(): void
    {
        $this->expectException(\LengthException::class);
        $this->expectExceptionMessage(
            'CBOR bignum payload exceeds 1024 bytes.'
        );

        CborBigNumber::fromUnsignedBytes(
            \str_repeat("\xFF", CborBigNumber::DEFAULT_MAX_BYTES + 1)
        );
    }

    #[DataProvider('incrementProvider')]
    public function testIncrementsDecimalString(
        string $value,
        string $expected
    ): void
    {
        self::assertSame($expected, CborBigNumber::increment($value));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function incrementProvider(): iterable
    {
        yield 'empty' => ['', '1'];
        yield 'zero' => ['0', '1'];
        yield 'without carry' => ['123', '124'];
        yield 'with carry' => ['199', '200'];
        yield 'all nines' => ['999', '1000'];
    }
}
