<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Shape\SensitiveDataRedactor;

#[CoversClass(SensitiveDataRedactor::class)]
final class SensitiveDataRedactorTest extends TestCase
{
    public function testDebugInfoReturnsEmptyArrayForNonShape(): void
    {
        $this->assertSame(
            [],
            SensitiveDataRedactor::debugInfo(new \stdClass())
        );
    }
}
