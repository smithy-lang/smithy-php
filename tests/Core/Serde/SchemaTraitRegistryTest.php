<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Core\Serde;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\{
    Schema,
    SchemaTraitRegistry
};

#[CoversClass(SchemaTraitRegistry::class)]
final class SchemaTraitRegistryTest extends TestCase
{
    public function testMaterializesBuiltInAndUnknownTraits(): void
    {
        $registry = new SchemaTraitRegistry();
        $traits = $registry->materialize([
            'smithy.api#required' => [],
            'smithy.waiters#waitable' => [],
            'example#first' => ['value' => 1],
            'example#second' => 'two'
        ]);

        self::assertTrue($traits['dense'][Schema::T_REQUIRED]);
        self::assertTrue($traits['byId']['smithy.api#required']);
        self::assertTrue($traits['dense'][Schema::T_WAITABLE]);
        self::assertTrue(
            $traits['byId']['smithy.waiters#waitable']
        );

        self::assertSame(
            ['value' => 1],
            $traits['byId']['example#first']
        );
        self::assertSame('two', $traits['byId']['example#second']);
        self::assertArrayNotHasKey('example#first', $traits['dense']);
    }

    public function testSupportsCustomMaterializersAndDenseKeys(): void
    {
        $registry = new SchemaTraitRegistry();
        $registry->register(
            'example#upper',
            99,
            static fn(mixed $value): string => \strtoupper((string) $value)
        );

        $traits = $registry->materialize([
            'example#upper' => 'value'
        ]);

        self::assertSame('VALUE', $traits['dense'][99]);
        self::assertSame('VALUE', $traits['byId']['example#upper']);
    }

    public function testCustomRegistrationWithoutMaterializerPreservesRawValue(): void
    {
        $registry = new SchemaTraitRegistry();
        $registry->register('example#config', 99);

        $traits = $registry->materialize([
            'example#config' => []
        ]);

        self::assertSame([], $traits['dense'][99]);
        self::assertSame([], $traits['byId']['example#config']);
    }

    public function testExposesDefaultTraitKeyMappings(): void
    {
        self::assertSame(
            Schema::T_TIMESTAMP_FORMAT,
            SchemaTraitRegistry::getDefaultKey(
                'smithy.api#timestampFormat'
            )
        );
        self::assertSame(
            'smithy.api#timestampFormat',
            SchemaTraitRegistry::getDefaultId(
                Schema::T_TIMESTAMP_FORMAT
            )
        );
        self::assertSame(
            Schema::T_PAGINATED,
            SchemaTraitRegistry::getDefaultKey(
                'smithy.api#paginated'
            )
        );
        self::assertSame(
            'smithy.api#paginated',
            SchemaTraitRegistry::getDefaultId(
                Schema::T_PAGINATED
            )
        );
        self::assertSame(
            Schema::T_WAITABLE,
            SchemaTraitRegistry::getDefaultKey(
                'smithy.waiters#waitable'
            )
        );
        self::assertSame(
            'smithy.waiters#waitable',
            SchemaTraitRegistry::getDefaultId(
                Schema::T_WAITABLE
            )
        );
        self::assertSame(
            Schema::T_ERRORS,
            SchemaTraitRegistry::getDefaultKey('smithy.api#errors')
        );
        self::assertSame(
            'smithy.api#errors',
            SchemaTraitRegistry::getDefaultId(Schema::T_ERRORS)
        );
    }
}
