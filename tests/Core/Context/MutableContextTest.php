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
use Smithy\Core\Context\MutableContext;

#[CoversClass(MutableContext::class)]
final class MutableContextTest extends TestCase
{
    public function testNewBagHasNoValues(): void
    {
        $key = new ContextKey('missing');
        $bag = new MutableContext();

        $this->assertFalse($bag->has($key));
        $this->assertNull($bag->get($key));
    }

    public function testSetStoresAndReplacesInPlace(): void
    {
        $key = new ContextKey('attempts');
        $bag = new MutableContext();

        $bag->set($key, 1);
        $this->assertSame(1, $bag->get($key));

        $bag->set($key, 2);
        $this->assertSame(2, $bag->get($key));
        $this->assertTrue($bag->has($key));
    }

    public function testKeysWithTheSameNameAreDistinct(): void
    {
        $first = new ContextKey('shared');
        $second = new ContextKey('shared');
        $bag = new MutableContext();

        $bag->set($first, 'one');

        $this->assertFalse($bag->has($second));
    }

    public function testHasIsTrueForAStoredNull(): void
    {
        $key = new ContextKey('nullable');
        $bag = new MutableContext();

        $bag->set($key, null);

        $this->assertTrue($bag->has($key));
    }
}
