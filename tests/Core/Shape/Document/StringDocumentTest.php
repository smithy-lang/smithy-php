<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Document;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\Document\IncompatibleDocumentTypeException;
use Smithy\Core\Shape\Document\StringDocument;

#[CoversClass(StringDocument::class)]
final class StringDocumentTest extends TestCase
{
    public function testTypeIsString(): void
    {
        $this->assertSame(
            ShapeType::String_,
            (new StringDocument('x'))->type()
        );
    }

    public function testAsStringReturnsStoredValue(): void
    {
        $this->assertSame('hello', (new StringDocument('hello'))->asString());
    }

    public function testAsIntegerThrows(): void
    {
        $this->expectException(IncompatibleDocumentTypeException::class);
        (new StringDocument('hello'))->asInteger();
    }

    public function testAsBooleanThrows(): void
    {
        $this->expectException(IncompatibleDocumentTypeException::class);
        (new StringDocument('hello'))->asBoolean();
    }

    public function testSerializeDispatchesToSerializeString(): void
    {
        $schema = new Schema(id: 'com.example#Name', type: ShapeType::String_);
        $doc = new StringDocument('hello', $schema);
        $serializer = $this->createMock(ShapeSerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serializeString')
            ->with($schema, 'hello', 2);

        $doc->serialize($serializer, 2);
    }

    public function testSerializeWithoutSchemaFallsBackToPreludeSchema(): void
    {
        $captured = null;
        $serializer = $this->createMock(ShapeSerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serializeString')
            ->with(
                $this->callback(function (Schema $s) use (&$captured): bool {
                    $captured = $s;

                    return $s->id === 'smithy.api#String';
                }),
                'x',
                0
            );

        (new StringDocument('x'))->serialize($serializer, 0);
        $this->assertSame(ShapeType::String_->value, $captured->type);
    }
}
