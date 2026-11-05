<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codec\FormUrlEncoded;

use PHPUnit\Framework\TestCase;
use Smithy\Codec\FormUrlEncoded\FormUrlEncodedCodecSettings;
use Smithy\Core\Serde\TimestampFormat;

final class FormUrlEncodedCodecSettingsTest extends TestCase
{
    public function testDefaults(): void
    {
        $settings = new FormUrlEncodedCodecSettings();

        self::assertNull($settings->serviceNamespace);
        self::assertSame(
            TimestampFormat::EpochSeconds,
            $settings->defaultTimestampFormat
        );
        self::assertTrue($settings->honorTimestampFormatTrait);
        self::assertTrue($settings->honorXmlName);
        self::assertFalse($settings->capitalizeResolvedWireNames);
        self::assertFalse($settings->alwaysFlattenLists);
        self::assertTrue($settings->supportsMaps);
    }

    public function testOverrides(): void
    {
        $settings = new FormUrlEncodedCodecSettings(
            serviceNamespace: 'smithy.test',
            defaultTimestampFormat: TimestampFormat::DateTime,
            honorXmlName: false,
            capitalizeResolvedWireNames: true,
            alwaysFlattenLists: true,
            supportsMaps: false,
            honorTimestampFormatTrait: false
        );

        self::assertSame('smithy.test', $settings->serviceNamespace);
        self::assertSame(
            TimestampFormat::DateTime,
            $settings->defaultTimestampFormat
        );
        self::assertFalse($settings->honorTimestampFormatTrait);
        self::assertFalse($settings->honorXmlName);
        self::assertTrue($settings->capitalizeResolvedWireNames);
        self::assertTrue($settings->alwaysFlattenLists);
        self::assertFalse($settings->supportsMaps);
    }
}
