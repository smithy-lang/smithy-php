<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Core\Serde;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\TimestampFormat;

final class TimestampFormatTest extends TestCase
{
    public function testExposesThreeStableCases(): void
    {
        $this->assertSame(
            ['EpochSeconds', 'DateTime', 'HttpDate'],
            array_map(
                static fn(TimestampFormat $c): string => $c->name,
                TimestampFormat::cases()
            )
        );
    }

    #[DataProvider('backingValuePairs')]
    public function testIntegerBackingIsStable(string $name, int $value): void
    {
        // The int backings are literal-inlined into hot-path switches in
        // `format()` and `parse()` and downstream serializers, reassigning
        // any of them is an ABI break.
        $this->assertSame($value, TimestampFormat::from($value)->value);
        $this->assertSame($name, TimestampFormat::from($value)->name);
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function backingValuePairs(): array
    {
        return [
            'EpochSeconds = 0' => ['EpochSeconds', 0],
            'DateTime = 1' => ['DateTime', 1],
            'HttpDate = 2' => ['HttpDate', 2]
        ];
    }

    public function testFormatDateTimeProducesIso8601UtcString(): void
    {
        // Reference: 2024-01-15T10:30:00Z is epoch 1705314600.
        $this->assertSame(
            '2024-01-15T10:30:00Z',
            TimestampFormat::DateTime->format(1705314600.0)
        );
    }

    public function testFormatHttpDateProducesRfc7231String(): void
    {
        // 2024-01-15T10:30:00Z falls on a Monday.
        $this->assertSame(
            'Mon, 15 Jan 2024 10:30:00 GMT',
            TimestampFormat::HttpDate->format(1705314600.0)
        );
    }

    public function testFormatEpochSecondsPrintsRawFloatValue(): void
    {
        $this->assertSame(
            '1705314600',
            TimestampFormat::EpochSeconds->format(1705314600.0)
        );
    }

    public function testFormatEpochSecondsPreservesFractionalPart(): void
    {
        // Fractional seconds should render via loose-typed `$value + 0`. PHP
        // prints a float verbatim when adding zero preserves the fraction.
        $this->assertSame(
            '1705314600.5',
            TimestampFormat::EpochSeconds->format(1705314600.5)
        );
    }

    public function testParseDateTimeRoundTripsIsoString(): void
    {
        $this->assertSame(
            1705314600.0,
            (float) TimestampFormat::DateTime->parse('2024-01-15T10:30:00Z')
        );
    }

    public function testParseHttpDateRoundTripsRfc7231String(): void
    {
        $this->assertSame(
            1705314600.0,
            (float) TimestampFormat::HttpDate->parse(
                'Mon, 15 Jan 2024 10:30:00 GMT'
            )
        );
    }

    public function testParseEpochSecondsCoercesNumericString(): void
    {
        $this->assertSame(
            1705314600.0,
            TimestampFormat::EpochSeconds->parse('1705314600')
        );
    }

    public function testParseDateTimeHandlesFractionalSecondsBeforeZ(): void
    {
        // The DateTime branch has a dedicated fractional-seconds path when
        // the string ends in Z and contains a `.` after the seconds field.
        $result = TimestampFormat::DateTime->parse('2024-01-15T10:30:00.125Z');
        $this->assertEqualsWithDelta(1705314600.125, $result, 0.001);
    }

    public function testParseDateTimeHandlesTimezoneOffsetVariant(): void
    {
        // Offset-bearing strings route to strtotime, 2024-01-15T10:30:00+00:00
        // is the same instant as ...Z (epoch 1705314600).
        $result = TimestampFormat::DateTime->parse('2024-01-15T10:30:00+00:00');
        $this->assertSame(1705314600.0, (float) $result);
    }

    public function testParseDateTimeThrowsWhenFallbackParseFails(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Invalid DateTime timestamp 'not-a-timestamp'."
        );

        TimestampFormat::DateTime->parse('not-a-timestamp');
    }

    #[DataProvider('emptyStringParseFormats')]
    public function testParseThrowsOnEmptyString(
        TimestampFormat $format
    ): void
    {
        // date_create_immutable('') returns the current time rather than
        // false, so an empty wire value would silently parse to "now".
        // The fallback must reject it like the old strtotime('') did.
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Invalid {$format->name} timestamp ''."
        );

        $format->parse('');
    }

    /**
     * @return array<string, array{TimestampFormat}>
     */
    public static function emptyStringParseFormats(): array
    {
        return [
            'date-time' => [TimestampFormat::DateTime],
            'http-date' => [TimestampFormat::HttpDate]
        ];
    }

    #[DataProvider('datesOutsideMonthTable')]
    public function testParseFallsBackOutsideMonthTableRange(
        TimestampFormat $format,
        string $value,
        float $expected
    ): void
    {
        $this->assertEqualsWithDelta(
            $expected,
            (float) $format->parse($value),
            0.001
        );
    }

    /**
     * @return array<string, array{TimestampFormat, string, float}>
     */
    public static function datesOutsideMonthTable(): array
    {
        return [
            'date-time before 1970' => [
                TimestampFormat::DateTime,
                '1960-01-02T00:00:00Z',
                -315532800.0
            ],
            // The date_create fallback keeps fractional seconds even for
            // dates before 1970, where U/u arithmetic would be off by one.
            'fractional date-time before 1970' => [
                TimestampFormat::DateTime,
                '1960-01-02T00:00:00.5Z',
                -315532799.5
            ],
            'fractional date-time after 2199' => [
                TimestampFormat::DateTime,
                '2300-01-01T00:00:00.25Z',
                10413792000.25
            ],
            'fractional date-time with offset' => [
                TimestampFormat::DateTime,
                '1960-01-02T00:00:00.5+00:00',
                -315532799.5
            ],
            'http-date after 2199' => [
                TimestampFormat::HttpDate,
                'Mon, 01 Jan 2300 00:00:00 GMT',
                10413792000.0
            ],
            'http-date before 1970' => [
                TimestampFormat::HttpDate,
                'Sat, 02 Jan 1960 00:00:00 GMT',
                -315532800.0
            ]
        ];
    }

    public function testParseMatchesAcrossFormatsAtMonthTableBounds(): void
    {
        $this->assertSame(
            0.0,
            (float) TimestampFormat::DateTime->parse('1970-01-01T00:00:00Z')
        );
        $this->assertSame(
            7258118399.0,
            (float) TimestampFormat::DateTime->parse('2199-12-31T23:59:59Z')
        );
        $this->assertSame(
            7258118399.0,
            (float) TimestampFormat::HttpDate->parse(
                'Tue, 31 Dec 2199 23:59:59 GMT'
            )
        );
    }

    #[DataProvider('traitStringMappings')]
    public function testFromTraitStringResolvesKnownTraitValues(
        ?string $trait,
        ?TimestampFormat $expected
    ): void
    {
        $this->assertSame($expected, TimestampFormat::fromTraitString($trait));
    }

    /**
     * @return array<string, array{?string, ?TimestampFormat}>
     */
    public static function traitStringMappings(): array
    {
        return [
            'date-time' => ['date-time', TimestampFormat::DateTime],
            'http-date' => ['http-date', TimestampFormat::HttpDate],
            'epoch-seconds' => ['epoch-seconds', TimestampFormat::EpochSeconds],
            'unknown trait -> null' => ['bogus', null],
            'empty trait -> null' => ['', null],
            'null trait -> null (caller decides default)' => [null, null]
        ];
    }
}
