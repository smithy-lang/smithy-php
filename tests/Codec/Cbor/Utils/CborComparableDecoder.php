<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codec\Cbor\Utils;

use Smithy\Codec\Exception\DeserializationException;

/**
 * Decode CBOR into a type-preserving tree for semantic comparison.
 *
 * The result normalizes map ordering and encoding widths while retaining
 * data item types and tags.
 */
final class CborComparableDecoder
{
    private const MAJOR_POSINT = 0;
    private const MAJOR_BYTESTRING = 2;
    private const MAJOR_TEXTSTRING = 3;
    private const MAJOR_ARRAY = 4;
    private const MAJOR_MAP = 5;
    private const MAJOR_TAG = 6;
    private const MAJOR_SIMPLE = 7;
    private const INFO_ONE_BYTE = 24;
    private const INFO_INDEFINITE = 31;
    private const SIMPLE_FALSE = 20;
    private const SIMPLE_TRUE = 21;
    private const SIMPLE_NULL = 22;
    private const SIMPLE_UNDEFINED = 23;
    private const SIMPLE_HALF_FLOAT = 25;
    private const SIMPLE_FLOAT = 26;
    private const SIMPLE_DOUBLE = 27;
    private const TAG_EPOCH = 1;
    private const MAX_DEPTH = 128;

    private readonly int $length;
    private int $idx = 0;
    private int $depth = 0;

    private function __construct(private readonly string $payload)
    {
        $this->length = \strlen($payload);
    }

    /**
     * Decode a complete CBOR payload.
     *
     * @return array<string, mixed>
     * @throws DeserializationException When the payload is malformed.
     */
    public static function decode(string $payload): array
    {
        $decoder = new self($payload);
        $value = $decoder->readValue();
        if ($decoder->idx !== $decoder->length) {
            $decoder->malformed();
        }

        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function readValue(): array
    {
        $initial = $this->readInitialByte();
        $major = $initial >> 5;
        $info = $initial & 0x1F;

        return match ($major) {
            self::MAJOR_POSINT => [
                'type' => 'integer',
                'value' => $this->readLength($info)
            ],
            self::MAJOR_BYTESTRING,
            self::MAJOR_TEXTSTRING => $this->readString($major, $info),
            self::MAJOR_ARRAY => $this->readArray($info),
            self::MAJOR_MAP => $this->readMap($info),
            self::MAJOR_TAG => $this->readTag($info),
            self::MAJOR_SIMPLE => $this->readSimple($info),
            default => [
                'type' => 'integer',
                'value' => $this->decodeNegative($this->readLength($info))
            ]
        };
    }

    /**
     * @return array{type: string, value: string}
     */
    private function readString(int $major, int $info): array
    {
        $value = '';
        if ($info === self::INFO_INDEFINITE) {
            while (!$this->consumeBreak()) {
                $initial = $this->readInitialByte();
                $chunkInfo = $initial & 0x1F;
                if (($initial >> 5) !== $major
                    || $chunkInfo === self::INFO_INDEFINITE
                ) {
                    $this->malformed();
                }
                $value .= $this->readBytes($this->readLength($chunkInfo));
            }
        } else {
            $value = $this->readBytes($this->readLength($info));
        }

        return [
            'type' => $major === self::MAJOR_BYTESTRING
                ? 'byte-string'
                : 'text-string',
            'value' => $value
        ];
    }

    /**
     * @return array{type: string, value: list<array<string, mixed>>}
     */
    private function readArray(int $info): array
    {
        $this->enter();
        $indefinite = $info === self::INFO_INDEFINITE;
        $remaining = $indefinite ? -1 : $this->readLength($info);
        $values = [];
        while ($indefinite ? !$this->consumeBreak() : $remaining-- > 0) {
            $values[] = $this->readValue();
        }
        $this->depth--;

        return [
            'type' => 'array',
            'value' => $values
        ];
    }

    /**
     * @return array{
     *     type: string,
     *     value: list<array{
     *         key: array<string, mixed>,
     *         value: array<string, mixed>
     *     }>
     * }
     */
    private function readMap(int $info): array
    {
        $this->enter();
        $indefinite = $info === self::INFO_INDEFINITE;
        $remaining = $indefinite ? -1 : $this->readLength($info);
        $entries = [];
        while ($indefinite ? !$this->consumeBreak() : $remaining-- > 0) {
            $entries[] = [
                'key' => $this->readValue(),
                'value' => $this->readValue()
            ];
        }
        $this->depth--;
        \usort(
            $entries,
            static fn(array $left, array $right): int => \strcmp(
                \serialize($left),
                \serialize($right)
            )
        );

        return [
            'type' => 'map',
            'value' => $entries
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function readTag(int $info): array
    {
        $tag = $this->readLength($info);
        $this->enter();
        $value = $this->readValue();
        $this->depth--;
        if ($tag === self::TAG_EPOCH
            && $value['type'] === 'integer'
            && \sprintf('%.0f', (float) $value['value'])
                === (string) $value['value']
        ) {
            $value = [
                'type' => 'float',
                'value' => (float) $value['value']
            ];
        }

        return [
            'type' => 'tag',
            'tag' => $tag,
            'value' => $value
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function readSimple(int $info): array
    {
        if ($info < self::SIMPLE_FALSE) {
            return [
                'type' => 'simple',
                'value' => $info
            ];
        }

        return match ($info) {
            self::SIMPLE_FALSE => [
                'type' => 'boolean',
                'value' => false
            ],
            self::SIMPLE_TRUE => [
                'type' => 'boolean',
                'value' => true
            ],
            self::SIMPLE_NULL => ['type' => 'null'],
            self::SIMPLE_UNDEFINED => ['type' => 'undefined'],
            self::INFO_ONE_BYTE => [
                'type' => 'simple',
                'value' => $this->readLength($info)
            ],
            self::SIMPLE_HALF_FLOAT => [
                'type' => 'float',
                'value' => $this->normalizeFloat($this->readHalfFloat())
            ],
            self::SIMPLE_FLOAT => [
                'type' => 'float',
                'value' => $this->normalizeFloat(
                    \unpack('G', $this->readBytes(4))[1]
                )
            ],
            self::SIMPLE_DOUBLE => [
                'type' => 'float',
                'value' => $this->normalizeFloat(
                    \unpack('E', $this->readBytes(8))[1]
                )
            ],
            default => $this->malformed()
        };
    }

    private function normalizeFloat(float $value): float|string
    {
        if (\is_nan($value)) {
            return 'NaN';
        }

        if (\is_infinite($value)) {
            return $value > 0 ? 'Infinity' : '-Infinity';
        }

        if ($value === 0.0
            && (\ord(\pack('E', $value)[0]) & 0x80) !== 0
        ) {
            return '-0';
        }

        return $value;
    }

    private function readHalfFloat(): float
    {
        $bytes = $this->readBytes(2);
        $bits = (\ord($bytes[0]) << 8) | \ord($bytes[1]);
        $sign = ($bits >> 15) & 0x01;
        $exp = ($bits >> 10) & 0x1F;
        $mant = $bits & 0x3FF;

        return match ($exp) {
            0 => $mant === 0
                ? ($sign ? -0.0 : 0.0)
                : ($sign ? -1 : 1) * (2 ** -14) * ($mant / 1024),
            31 => $mant === 0 ? ($sign ? -INF : INF) : NAN,
            default => (float) (
                ($sign ? -1 : 1)
                * (2 ** ($exp - 15))
                * (1 + $mant / 1024)
            )
        };
    }

    private function decodeNegative(int $unsigned): int
    {
        return $unsigned === PHP_INT_MAX ? PHP_INT_MIN : -1 - $unsigned;
    }

    private function readLength(int $info): int
    {
        if ($info < self::INFO_ONE_BYTE) {
            return $info;
        }

        $width = match ($info) {
            24 => 1,
            25 => 2,
            26 => 4,
            27 => 8,
            default => $this->malformed()
        };
        $bytes = $this->readBytes($width);
        if ($width === 8 && \ord($bytes[0]) > 0x7F) {
            $this->malformed();
        }
        $value = 0;
        for ($i = 0; $i < $width; $i++) {
            $value = ($value << 8) | \ord($bytes[$i]);
        }

        return $value;
    }

    private function readInitialByte(): int
    {
        if ($this->idx >= $this->length) {
            $this->malformed();
        }

        return \ord($this->payload[$this->idx++]);
    }

    private function readBytes(int $length): string
    {
        if ($this->idx + $length > $this->length) {
            $this->malformed();
        }
        $start = $this->idx;
        $this->idx += $length;

        return \substr($this->payload, $start, $length);
    }

    private function consumeBreak(): bool
    {
        if ($this->idx < $this->length
            && \ord($this->payload[$this->idx]) === 0xFF
        ) {
            $this->idx++;

            return true;
        }

        return false;
    }

    /**
     * Guard nested payload depth.
     *
     * @return void
     */
    private function enter(): void
    {
        if (++$this->depth > self::MAX_DEPTH) {
            $this->malformed();
        }
    }

    private function malformed(): never
    {
        throw new DeserializationException('Malformed CBOR payload.');
    }
}
