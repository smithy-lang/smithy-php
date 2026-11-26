<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Document;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\Document\PreludeSchemas;

/**
 * Document-scoped prelude schema table. Every ShapeType that a document can carry maps to a
 * `smithy.api#Xxx` schema singleton that codecs use as a fallback when no explicit schema is
 * attached to the document.
 */
#[CoversClass(PreludeSchemas::class)]
final class PreludeSchemasTest extends TestCase
{
    /**
     * @param non-empty-string $expectedId
     */
    #[DataProvider('typeIdMappings')]
    public function testForReturnsSchemaWithMatchingIdAndType(
        ShapeType $type,
        string $expectedId
    ): void
    {
        $schema = PreludeSchemas::for($type);
        $this->assertSame($expectedId, $schema->id);
        $this->assertSame($type->value, $schema->type);
    }

    #[DataProvider('typeIdMappings')]
    public function testForReturnsSameInstanceAcrossCalls(ShapeType $type): void
    {
        // Codec hot paths compare schemas by reference, so identity is
        // load-bearing.
        $this->assertSame(
            PreludeSchemas::for($type),
            PreludeSchemas::for($type)
        );
    }

    public function testListSchemaDescribesSparseDocumentMembers(): void
    {
        $schema = PreludeSchemas::for(ShapeType::List_);

        $this->assertSame(
            PreludeSchemas::for(ShapeType::Document),
            $schema->listMember
        );
        $this->assertTrue($schema->hasTrait(Schema::T_SPARSE));
    }

    public function testMapSchemaDescribesSparseDocumentValues(): void
    {
        $schema = PreludeSchemas::for(ShapeType::Map);

        $this->assertSame(
            PreludeSchemas::for(ShapeType::String_),
            $schema->mapKey
        );
        $this->assertSame(
            PreludeSchemas::for(ShapeType::Document),
            $schema->mapValue
        );
        $this->assertTrue($schema->hasTrait(Schema::T_SPARSE));
    }

    /**
     * @return iterable<string, array{ShapeType, non-empty-string}>
     */
    public static function typeIdMappings(): iterable
    {
        yield 'String_' => [ShapeType::String_, 'smithy.api#String'];
        yield 'Boolean' => [ShapeType::Boolean, 'smithy.api#Boolean'];
        yield 'Byte' => [ShapeType::Byte, 'smithy.api#Byte'];
        yield 'Short' => [ShapeType::Short, 'smithy.api#Short'];
        yield 'Integer' => [ShapeType::Integer, 'smithy.api#Integer'];
        yield 'Long' => [ShapeType::Long, 'smithy.api#Long'];
        yield 'Float_' => [ShapeType::Float_, 'smithy.api#Float'];
        yield 'Double' => [ShapeType::Double, 'smithy.api#Double'];
        yield 'BigInteger' => [ShapeType::BigInteger, 'smithy.api#BigInteger'];
        yield 'BigDecimal' => [ShapeType::BigDecimal, 'smithy.api#BigDecimal'];
        yield 'Blob' => [ShapeType::Blob, 'smithy.api#Blob'];
        yield 'Timestamp' => [ShapeType::Timestamp, 'smithy.api#Timestamp'];
        yield 'List_' => [ShapeType::List_, 'smithy.api#List'];
        yield 'Map' => [ShapeType::Map, 'smithy.api#Map'];
        yield 'Document' => [ShapeType::Document, 'smithy.api#Document'];
        yield 'Structure' => [ShapeType::Structure, 'smithy.api#Structure'];
        yield 'Union' => [ShapeType::Union, 'smithy.api#Union'];
        yield 'Enum' => [ShapeType::Enum, 'smithy.api#Enum'];
        yield 'IntEnum' => [ShapeType::IntEnum, 'smithy.api#IntEnum'];
    }

    #[DataProvider('nonDataTypes')]
    public function testForRejectsTypesWithoutPreludeSchema(ShapeType $type): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("{$type->name} has no prelude schema.");

        PreludeSchemas::for($type);
    }

    /**
     * @return iterable<string, array{ShapeType}>
     */
    public static function nonDataTypes(): iterable
    {
        yield 'Operation' => [ShapeType::Operation];
        yield 'Resource' => [ShapeType::Resource];
        yield 'Service' => [ShapeType::Service];
    }
}
