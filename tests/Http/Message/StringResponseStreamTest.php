<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Http\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Http\Message\StringResponseStream;

#[CoversClass(StringResponseStream::class)]
final class StringResponseStreamTest extends TestCase
{
    public function testRejectsNegativeReadLength(): void
    {
        $stream = new StringResponseStream('abcdef');

        try {
            $stream->read(-1);
            $this->fail('Expected InvalidArgumentException.');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('Read length must be non-negative.', $e->getMessage());
        }

        $this->assertSame(0, $stream->tell());
    }
}
