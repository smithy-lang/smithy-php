<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Core\Context;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Context\Context;
use Smithy\Core\Context\ContextKey;

#[CoversClass(Context::class)]
final class ContextTest extends TestCase
{
    public function testEmptyContextHasNoValues(): void
    {
        $key = new ContextKey('missing');
        $context = new Context();

        $this->assertFalse($context->has($key));
        $this->assertNull($context->get($key));
    }

    public function testWithReturnsACopyHoldingTheValue(): void
    {
        $key = new ContextKey('trace');
        $original = new Context();

        $updated = $original->with($key, 'abc');

        $this->assertNotSame($original, $updated);
        $this->assertSame('abc', $updated->get($key));
        $this->assertTrue($updated->has($key));
        $this->assertFalse($original->has($key));
    }

    public function testWithReplacesAnExistingValue(): void
    {
        $key = new ContextKey('count');

        $context = (new Context())->with($key, 1)->with($key, 2);

        $this->assertSame(2, $context->get($key));
    }

    public function testKeysWithTheSameNameAreDistinct(): void
    {
        $first = new ContextKey('shared');
        $second = new ContextKey('shared');

        $context = (new Context())->with($first, 'one');

        $this->assertTrue($context->has($first));
        $this->assertFalse($context->has($second));
        $this->assertNull($context->get($second));
    }

    public function testHasIsTrueForAStoredNull(): void
    {
        $key = new ContextKey('nullable');

        $context = (new Context())->with($key, null);

        $this->assertTrue($context->has($key));
        $this->assertNull($context->get($key));
    }
}
