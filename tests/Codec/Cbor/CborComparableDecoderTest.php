<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codec\Cbor;

use PHPUnit\Framework\TestCase;
use Smithy\Codec\Exception\DeserializationException;
use Smithy\Tests\Codec\Cbor\Utils\CborComparableDecoder;

/**
 * Cover the test-only comparable CBOR decoder.
 */
final class CborComparableDecoderTest extends TestCase
{
    public function testDecodesValueWithTypeInformation(): void
    {
        $payload = \hex2bin(
            'a16864656661756c7473a16d64656661756c74537472696e67'
                . '63666f6f'
        );

        self::assertSame(
            [
                'type' => 'map',
                'value' => [
                    [
                        'key' => [
                            'type' => 'text-string',
                            'value' => 'defaults'
                        ],
                        'value' => [
                            'type' => 'map',
                            'value' => [
                                [
                                    'key' => [
                                        'type' => 'text-string',
                                        'value' => 'defaultString'
                                    ],
                                    'value' => [
                                        'type' => 'text-string',
                                        'value' => 'foo'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            CborComparableDecoder::decode($payload)
        );
    }

    public function testRejectsExcessivelyNestedTags(): void
    {
        $this->expectException(DeserializationException::class);

        CborComparableDecoder::decode(\str_repeat("\xC0", 129) . "\xF6");
    }

    public function testEpochTagPreservesInexactLargeInteger(): void
    {
        $exact = CborComparableDecoder::decode(
            \hex2bin('c11b0020000000000000')
        );
        $inexact = CborComparableDecoder::decode(
            \hex2bin('c11b0020000000000001')
        );

        self::assertSame('float', $exact['value']['type']);
        self::assertSame('integer', $inexact['value']['type']);
        self::assertNotSame($exact, $inexact);
    }

    public function testEpochTagNormalizesExactIntegerAndFloat(): void
    {
        $integer = CborComparableDecoder::decode(
            \hex2bin('c11b0020000000000000')
        );
        $float = CborComparableDecoder::decode(
            \hex2bin('c1fb4340000000000000')
        );

        self::assertSame($integer, $float);
    }
}
