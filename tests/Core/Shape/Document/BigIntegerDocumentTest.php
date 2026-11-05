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
use Smithy\Core\Shape\BigInteger;
use Smithy\Core\Shape\Document\BigIntegerDocument;
use Smithy\Core\Shape\Document\IncompatibleDocumentTypeException;

#[CoversClass(BigIntegerDocument::class)]
final class BigIntegerDocumentTest extends TestCase
{
    public function testTypeIsBigInteger(): void
    {
        $this->assertSame(
            ShapeType::BigInteger,
            (new BigIntegerDocument(new BigInteger('1')))->type()
        );
    }

    public function testAsBigIntegerReturnsStoredValue(): void
    {
        $value = new BigInteger('12345678901234567890');
        $this->assertSame(
            $value,
            (new BigIntegerDocument($value))->asBigInteger()
        );
    }

    public function testAsIntegerThrows(): void
    {
        $this->expectException(IncompatibleDocumentTypeException::class);
        (new BigIntegerDocument(new BigInteger('1')))->asInteger();
    }

    public function testSerializeDispatchesToSerializeBigInteger(): void
    {
        $schema = new Schema(
            id: 'com.example#Big',
            type: ShapeType::BigInteger
        );
        $value = new BigInteger('99');
        $serializer = $this->createMock(ShapeSerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serializeBigInteger')
            ->with($schema, $value, 0);

        (new BigIntegerDocument($value, $schema))->serialize($serializer, 0);
    }

    public function testSerializeWithoutSchemaFallsBackToPreludeSchema(): void
    {
        $captured = null;
        $value = new BigInteger('99');
        $serializer = $this->createMock(ShapeSerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serializeBigInteger')
            ->with(
                $this->callback(function (Schema $s) use (&$captured): bool {
                    $captured = $s;

                    return $s->id === 'smithy.api#BigInteger';
                }),
                $value,
                0
            );

        (new BigIntegerDocument($value))->serialize($serializer, 0);
        $this->assertSame(ShapeType::BigInteger->value, $captured->type);
    }
}
