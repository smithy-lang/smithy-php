<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Core\Context;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Context\ContextKey;

#[CoversClass(ContextKey::class)]
final class ContextKeyTest extends TestCase
{
    public function testKeepsItsName(): void
    {
        $key = new ContextKey('smithy.test.name');

        $this->assertSame('smithy.test.name', $key->name);
    }

    public function testEachKeyHasItsOwnId(): void
    {
        $first = new ContextKey('same');
        $second = new ContextKey('same');

        $this->assertNotSame($first->id, $second->id);
    }
}
