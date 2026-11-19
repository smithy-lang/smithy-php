<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Core\Serde;

use PHPUnit\Framework\TestCase;
use Smithy\Codec\Json\JsonCodec;
use Smithy\Codec\Json\JsonCodecSettings;
use Smithy\Codec\Json\JsonDocumentReader;
use Smithy\Core\Serde\CompositeTypeRegistry;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\TimestampFormat;
use Smithy\Core\Serde\TypeRegistryDeserializeTrait;
use Smithy\Core\Serde\TypeRegistryInterface;
use Smithy\Core\Shape\Document\AbstractDocument;
use Smithy\Core\Shape\ShapeType;
use Smithy\Http\Message\StringRequestStream;
use Smithy\Tests\Codec\Json\Fixture\JsonSampleUnionFixture;
use Smithy\Tests\Core\Shape\Document\Fixture\WidgetBoxFixture;
use Smithy\Tests\Core\Shape\Document\Fixture\WidgetFixture;

/**
 * Convert JSON documents carrying `__type` into modeled shapes through `asShape()` and registries.
 */
final class DocumentShapeEndToEndTest extends TestCase
{
    private const BOX_JSON = '{
        "__type": "smithy.test#WidgetBox:http://internal.example/",
        "main": {"Name": "m", "data": "YWJjZA==", "created": "1970-01-01T00:00:05Z", "tags": ["x"]},
        "items": [{"Name": "a"}, null, {"Name": "b", "created": "1970-01-01T00:01:00Z"}],
        "byName": {"x": {"count": 1}, "y": null}
    }';

    public function testRegistryBuildsNestedShapeFromJsonDocument(): void
    {
        $box = $this->registry()->deserialize($this->boxDocument());

        self::assertInstanceOf(WidgetBoxFixture::class, $box);
        self::assertEquals(
            new WidgetFixture('m', 'abcd', 5.0, null, ['x']),
            $box->main
        );
    }

    public function testListAndMapOfStructs(): void
    {
        $box = $this->boxDocument()->asShape(WidgetBoxFixture::class);

        self::assertEquals(
            [new WidgetFixture('a'), null, new WidgetFixture('b', null, 60.0)],
            $box->items
        );
        self::assertEquals(
            ['x' => new WidgetFixture(count: 1), 'y' => null],
            $box->byName
        );
    }

    public function testBlobAndTimestampMembersDecodeFromJsonStrings(): void
    {
        $widget = $this->boxDocument()->asShape(WidgetBoxFixture::class)->main;

        self::assertSame('abcd', $widget?->data);
        self::assertSame(5.0, $widget?->created);
    }

    public function testUnionDocumentBuildsActiveVariant(): void
    {
        $document = JsonDocumentReader::read(
            ['__type' => 'smithy.test#SampleUnion', 'count' => 7],
            TimestampFormat::DateTime
        );

        $union = $this->registry()->deserialize($document);

        self::assertInstanceOf(JsonSampleUnionFixture::class, $union);
        self::assertSame('count', $union->tag());
        self::assertSame(7, $union->value());
    }

    public function testUnknownUnionVariantIsPreserved(): void
    {
        $union = JsonDocumentReader::read(
            ['hamster' => ['x' => 1]],
            TimestampFormat::DateTime
        )?->asShape(JsonSampleUnionFixture::class);

        self::assertSame('hamster', $union?->tag());
    }

    public function testComposedRegistryDeserializesJsonDocument(): void
    {
        $composed = CompositeTypeRegistry::compose(
            $this->registry(['smithy.test#Other' => WidgetFixture::class]),
            $this->registry()
        );

        self::assertInstanceOf(
            WidgetBoxFixture::class,
            $composed->deserialize($this->boxDocument())
        );
    }

    public function testDocumentReadFromJsonStreamConvertsToShape(): void
    {
        $schema = new Schema(
            id: 'smithy.test#Holder',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.test#Holder$doc',
                    type: ShapeType::Document,
                    memberName: 'doc',
                    memberIndex: 0
                )
            ]
        );
        $members = [];
        (new JsonCodec(new JsonCodecSettings(TimestampFormat::DateTime)))
            ->createDeserializer(new StringRequestStream(
                '{"doc":' . self::BOX_JSON . '}'
            ))->deserialize($schema, $members);

        $box = $this->registry()->deserialize($members[0]);

        self::assertInstanceOf(WidgetBoxFixture::class, $box);
        self::assertSame('abcd', $box->main?->data);
    }

    private function boxDocument(): AbstractDocument
    {
        $document = JsonDocumentReader::read(
            \json_decode(self::BOX_JSON, true, 512, \JSON_THROW_ON_ERROR),
            TimestampFormat::DateTime
        );
        self::assertNotNull($document);
        self::assertSame('smithy.test#WidgetBox', $document->discriminator());

        return $document;
    }

    /**
     * @param array<string, class-string> $extra
     */
    private function registry(array $extra = []): TypeRegistryInterface
    {
        $classes = $extra + [
            'smithy.test#WidgetBox' => WidgetBoxFixture::class,
            'smithy.test#Widget' => WidgetFixture::class,
            'smithy.test#SampleUnion' => JsonSampleUnionFixture::class
        ];

        return new class($classes) implements TypeRegistryInterface {
            use TypeRegistryDeserializeTrait;

            /** @param array<string, class-string> $classes */
            public function __construct(private readonly array $classes) {}

            public function schemaFor(string $shapeId): ?Schema
            {
                $class = $this->classes[$shapeId] ?? null;

                return $class !== null ? $class::schema() : null;
            }

            public function classFor(string $shapeId): ?string
            {
                return $this->classes[$shapeId] ?? null;
            }
        };
    }
}
