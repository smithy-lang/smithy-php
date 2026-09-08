<?php

declare(strict_types=1);

namespace Smithy\Tests\Codec;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Codec\AbstractCodecSettings;
use Smithy\Core\Serde\TimestampFormat;
use Smithy\Tests\Codec\Utils\CodecSettingsFixture;

#[CoversClass(AbstractCodecSettings::class)]
final class AbstractCodecSettingsTest extends TestCase
{
    public function testUsesDefaults(): void
    {
        $settings = new CodecSettingsFixture();

        self::assertNull($settings->serviceNamespace);
        self::assertSame(
            TimestampFormat::EpochSeconds,
            $settings->defaultTimestampFormat
        );
        self::assertTrue($settings->honorTimestampFormatTrait);
    }

    public function testStoresConfiguredValues(): void
    {
        $settings = new CodecSettingsFixture(
            serviceNamespace: 'smithy.example',
            defaultTimestampFormat: TimestampFormat::HttpDate,
            honorTimestampFormatTrait: false
        );

        self::assertSame(
            'smithy.example',
            $settings->serviceNamespace
        );
        self::assertSame(
            TimestampFormat::HttpDate,
            $settings->defaultTimestampFormat
        );
        self::assertFalse($settings->honorTimestampFormatTrait);
    }

    public function testExplicitNullUsesDefaultTimestampFormat(): void
    {
        $settings = new CodecSettingsFixture(
            defaultTimestampFormat: null
        );

        self::assertSame(
            TimestampFormat::EpochSeconds,
            $settings->defaultTimestampFormat
        );
    }
}
