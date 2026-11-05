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
use Smithy\Core\Shape\Document\BlobDocument;
use Smithy\Core\Shape\Document\IncompatibleDocumentTypeException;

#[CoversClass(BlobDocument::class)]
final class BlobDocumentTest extends TestCase
{
    public function testTypeIsBlob(): void
    {
        $this->assertSame(ShapeType::Blob, (new BlobDocument(''))->type());
    }

    public function testAsBlobReturnsStoredBytes(): void
    {
        $this->assertSame(
            "\x00\x01\x02",
            (new BlobDocument("\x00\x01\x02"))->asBlob()
        );
    }

    public function testAsStringThrows(): void
    {
        // Blob and string are distinct data-model types even though both are
        // PHP strings.
        $this->expectException(IncompatibleDocumentTypeException::class);
        (new BlobDocument("\x00"))->asString();
    }

    public function testSerializeDispatchesToSerializeBlob(): void
    {
        $schema = new Schema(id: 'com.example#Payload', type: ShapeType::Blob);
        $serializer = $this->createMock(ShapeSerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serializeBlob')
            ->with($schema, "\x00\x01", 0);

        (new BlobDocument("\x00\x01", $schema))->serialize($serializer, 0);
    }

    public function testSerializeWithoutSchemaFallsBackToPreludeSchema(): void
    {
        $captured = null;
        $serializer = $this->createMock(ShapeSerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serializeBlob')
            ->with(
                $this->callback(function (Schema $s) use (&$captured): bool {
                    $captured = $s;

                    return $s->id === 'smithy.api#Blob';
                }),
                "\x00",
                0
            );

        (new BlobDocument("\x00"))->serialize($serializer, 0);
        $this->assertSame(ShapeType::Blob->value, $captured->type);
    }
}
