<?php

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
            ['DateTime', 'HttpDate', 'EpochSeconds'],
            array_map(static fn(TimestampFormat $c): string => $c->name, TimestampFormat::cases()),
        );
    }

    #[DataProvider('backingValuePairs')]
    public function testIntegerBackingIsStable(string $name, int $value): void
    {
        // The int backings are literal-inlined into hot-path switches in
        // `format()` and `parse()` and downstream serializers — reassigning
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
            'DateTime = 0' => ['DateTime', 0],
            'HttpDate = 1' => ['HttpDate', 1],
            'EpochSeconds = 2' => ['EpochSeconds', 2],
        ];
    }

    // ---- format() ------------------------------------------------------

    public function testFormatDateTimeProducesIso8601UtcString(): void
    {
        // Reference: 2024-01-15T10:30:00Z is epoch 1705314600.
        $this->assertSame(
            '2024-01-15T10:30:00Z',
            TimestampFormat::DateTime->format(1705314600.0),
        );
    }

    public function testFormatHttpDateProducesRfc7231String(): void
    {
        // 2024-01-15T10:30:00Z falls on a Monday.
        $this->assertSame(
            'Mon, 15 Jan 2024 10:30:00 GMT',
            TimestampFormat::HttpDate->format(1705314600.0),
        );
    }

    public function testFormatEpochSecondsPrintsRawFloatValue(): void
    {
        $this->assertSame('1705314600', TimestampFormat::EpochSeconds->format(1705314600.0));
    }

    public function testFormatEpochSecondsPreservesFractionalPart(): void
    {
        // Fractional seconds should render via loose-typed `$value + 0` — PHP
        // prints a float verbatim when adding zero preserves the fraction.
        $this->assertSame('1705314600.5', TimestampFormat::EpochSeconds->format(1705314600.5));
    }

    // ---- parse() ------------------------------------------------------

    public function testParseDateTimeRoundTripsIsoString(): void
    {
        $this->assertSame(1705314600.0, (float) TimestampFormat::DateTime->parse('2024-01-15T10:30:00Z'));
    }

    public function testParseHttpDateRoundTripsRfc7231String(): void
    {
        $this->assertSame(
            1705314600.0,
            (float) TimestampFormat::HttpDate->parse('Mon, 15 Jan 2024 10:30:00 GMT'),
        );
    }

    public function testParseEpochSecondsCoercesNumericString(): void
    {
        $this->assertSame(1705314600.0, TimestampFormat::EpochSeconds->parse('1705314600'));
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
        // Offset-bearing strings route to strtotime; 2024-01-15T10:30:00+00:00
        // is the same instant as ...Z (epoch 1705314600).
        $result = TimestampFormat::DateTime->parse('2024-01-15T10:30:00+00:00');
        $this->assertSame(1705314600.0, (float) $result);
    }

    // ---- fromTraitString() --------------------------------------------

    #[DataProvider('traitStringMappings')]
    public function testFromTraitStringResolvesKnownTraitValues(
        ?string $trait,
        ?TimestampFormat $expected,
    ): void {
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
            'null trait -> null (caller decides default)' => [null, null],
        ];
    }
}
