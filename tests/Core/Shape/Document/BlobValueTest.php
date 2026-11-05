<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Document;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Shape\Document\BlobValue;

#[CoversClass(BlobValue::class)]
final class BlobValueTest extends TestCase
{
    public function testExposesBytesProperty(): void
    {
        $this->assertSame(
            "\x00\x01\x02",
            (new BlobValue("\x00\x01\x02"))->bytes
        );
    }

    public function testAcceptsEmptyString(): void
    {
        $this->assertSame('', (new BlobValue(''))->bytes);
    }
}
