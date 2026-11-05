<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codegen;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Codegen\PhpType;

final class PhpTypeTest extends TestCase
{
    public function testConstructorAssignsAllFieldsAndDefaults(): void
    {
        $type = new PhpType(typeHint: 'string');

        $this->assertSame('string', $type->typeHint);
        $this->assertFalse($type->builtIn);
        $this->assertNull($type->fqcn);
        $this->assertNull($type->docType);
    }

    public function testConstructorPreservesAllExplicitFields(): void
    {
        $type = new PhpType(
            typeHint: 'Human',
            builtIn: false,
            fqcn: 'Aws\\DynamoDb\\Shape\\Human',
            docType: 'Human'
        );

        $this->assertSame('Human', $type->typeHint);
        $this->assertFalse($type->builtIn);
        $this->assertSame('Aws\\DynamoDb\\Shape\\Human', $type->fqcn);
        $this->assertSame('Human', $type->docType);
    }

    #[DataProvider('nullableSingleTypeVariants')]
    public function testNullablePrependsQuestionMarkForSingleTypes(
        string $typeHint,
        string $expected
    ): void
    {
        $this->assertSame($expected, (new PhpType($typeHint))->nullable());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function nullableSingleTypeVariants(): array
    {
        return [
            'string' => ['string', '?string'],
            'int' => ['int', '?int'],
            'float' => ['float', '?float'],
            'bool' => ['bool', '?bool'],
            'array' => ['array', '?array'],
            'class type' => ['Human', '?Human'],
            'namespaced class type' => ['Foo\\Bar', '?Foo\\Bar']
        ];
    }

    public function testNullableReturnsMixedUnchanged(): void
    {
        // `?mixed` is a syntax error in PHP. `mixed` already includes null.
        $this->assertSame('mixed', (new PhpType('mixed'))->nullable());
    }

    #[DataProvider('nullableUnionVariants')]
    public function testNullableAppendsNullToUnionTypesWithoutNull(
        string $typeHint,
        string $expected
    ): void
    {
        $this->assertSame($expected, (new PhpType($typeHint))->nullable());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function nullableUnionVariants(): array
    {
        return [
            'int|string appends null' => ['int|string', 'int|string|null'],
            'string|int|float appends null' => [
                'string|int|float',
                'string|int|float|null'
            ],
            'union already ending in null passes through' => [
                'int|null',
                'int|null'
            ],
            'union containing null passes through' => [
                'int|null|string',
                'int|null|string'
            ]
        ];
    }

    public function testRequiresImportOnlyForNonBuiltInWithFqcn(): void
    {
        $builtIn = new PhpType('string', builtIn: true);
        $withoutFqcn = new PhpType('Custom', builtIn: false, fqcn: null);
        $withFqcn = new PhpType(
            'Human',
            builtIn: false,
            fqcn: 'App\\Shape\\Human'
        );

        $this->assertFalse(
            $builtIn->requiresImport(),
            'built-in scalars are never imported'
        );
        $this->assertFalse(
            $withoutFqcn->requiresImport(),
            'user types without an fqcn cannot be imported'
        );
        $this->assertTrue($withFqcn->requiresImport());
    }

    public function testNullableDocTypeFallsBackToTypeHintWhenDocTypeAbsent(): void
    {
        $type = new PhpType('Human');
        $this->assertSame('Human|null', $type->nullableDocType());
    }

    public function testNullableDocTypeUsesExplicitDocTypeWhenSet(): void
    {
        $type = new PhpType(typeHint: 'array', docType: 'list<Human>');
        $this->assertSame('list<Human>|null', $type->nullableDocType());
    }

    public function testNullableDocTypePreservesExistingNullSuffix(): void
    {
        $type = new PhpType(typeHint: 'array', docType: 'list<Human>|null');
        $this->assertSame('list<Human>|null', $type->nullableDocType());
    }

    public function testNullableDocTypeDistinguishesNestedNull(): void
    {
        $type = new PhpType(
            typeHint: 'array',
            docType: 'list<Human|null>'
        );
        $this->assertSame(
            'list<Human|null>|null',
            $type->nullableDocType()
        );
    }
}
