<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Document;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Shape\Document\TimestampValue;

#[CoversClass(TimestampValue::class)]
final class TimestampValueTest extends TestCase
{
    public function testExposesEpochSecondsProperty(): void
    {
        $this->assertSame(
            1_700_000_000.5,
            (new TimestampValue(1_700_000_000.5))->epochSeconds
        );
    }

    public function testAcceptsIntegerViaFloatCoercion(): void
    {
        $this->assertSame(
            1_700_000_000.0,
            (new TimestampValue(1_700_000_000))->epochSeconds
        );
    }
}
