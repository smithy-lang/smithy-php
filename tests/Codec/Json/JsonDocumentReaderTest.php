<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codec\Json;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Codec\Json\JsonCodec;
use Smithy\Codec\Json\JsonCodecSettings;
use Smithy\Codec\Json\JsonDocumentReader;
use Smithy\Codec\Json\JsonStringDocument;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\TimestampFormat;
use Smithy\Core\Shape\Document\IncompatibleDocumentTypeException;
use Smithy\Core\Shape\Document\ListDocument;
use Smithy\Core\Shape\Document\MapDocument;
use Smithy\Core\Shape\ShapeType;
use Smithy\Http\Message\StringRequestStream;
use Smithy\Tests\Core\Shape\Document\Fixture\WidgetFixture;

#[CoversClass(JsonDocumentReader::class)]
#[CoversClass(JsonStringDocument::class)]
final class JsonDocumentReaderTest extends TestCase
{
    public function testObjectKeepsTypeDiscriminator(): void
    {
        $document = JsonDocumentReader::read(
            ['__type' => 'smithy.test#Widget', 'Name' => 'a'],
            TimestampFormat::EpochSeconds
        );

        self::assertInstanceOf(MapDocument::class, $document);
        self::assertSame('smithy.test#Widget', $document->discriminator());
        self::assertSame('a', $document->getMember('Name')?->asString());
        self::assertSame(
            'smithy.test#Widget',
            $document->getMember('__type')?->asString()
        );
    }

    public function testDiscriminatorDropsUriSuffix(): void
    {
        $document = JsonDocumentReader::read(
            ['__type' => 'smithy.test#Widget:http://internal.example/validate/'],
            TimestampFormat::EpochSeconds
        );

        self::assertSame('smithy.test#Widget', $document?->discriminator());
    }

    public function testObjectWithoutTypeHasNoDiscriminator(): void
    {
        $document = JsonDocumentReader::read(
            ['a' => 1],
            TimestampFormat::EpochSeconds
        );

        self::assertNull($document?->discriminator());
    }

    public function testNonStringTypeIsNotADiscriminator(): void
    {
        $document = JsonDocumentReader::read(
            ['__type' => 5],
            TimestampFormat::EpochSeconds
        );

        self::assertNull($document?->discriminator());
    }

    public function testNestedObjectsAndListsKeepDiscriminators(): void
    {
        $document = JsonDocumentReader::read(
            ['items' => [['__type' => 'smithy.test#Widget']]],
            TimestampFormat::EpochSeconds
        );

        self::assertInstanceOf(MapDocument::class, $document);
        $items = $document->getMember('items');
        self::assertInstanceOf(ListDocument::class, $items);
        self::assertSame(
            'smithy.test#Widget',
            $items->asList()[0]?->discriminator()
        );
    }

    public function testScalarsAndNull(): void
    {
        self::assertNull(
            JsonDocumentReader::read(null, TimestampFormat::EpochSeconds)
        );
        self::assertSame(
            ShapeType::Boolean,
            JsonDocumentReader::read(true, TimestampFormat::EpochSeconds)?->type()
        );
        self::assertSame(
            ShapeType::Integer,
            JsonDocumentReader::read(1, TimestampFormat::EpochSeconds)?->type()
        );
        self::assertInstanceOf(
            JsonStringDocument::class,
            JsonDocumentReader::read('x', TimestampFormat::EpochSeconds)
        );
    }

    public function testStringAsBlobDecodesBase64(): void
    {
        $document = new JsonStringDocument(
            'YWJjZA==',
            TimestampFormat::EpochSeconds
        );

        self::assertSame('abcd', $document->asBlob());
    }

    public function testStringAsBlobRejectsInvalidBase64(): void
    {
        $this->expectException(IncompatibleDocumentTypeException::class);

        (new JsonStringDocument('***', TimestampFormat::EpochSeconds))->asBlob();
    }

    public function testStringAsTimestampParsesDateTime(): void
    {
        $document = new JsonStringDocument(
            '1970-01-01T00:00:10Z',
            TimestampFormat::DateTime
        );

        self::assertSame(10.0, $document->asTimestamp());
    }

    public function testStringAsTimestampParsesHttpDate(): void
    {
        $document = new JsonStringDocument(
            'Thu, 01 Jan 1970 00:00:10 GMT',
            TimestampFormat::HttpDate
        );

        self::assertSame(10.0, $document->asTimestamp());
    }

    public function testStringAsTimestampRejectsEpochSecondsFormat(): void
    {
        $this->expectException(IncompatibleDocumentTypeException::class);

        (new JsonStringDocument('10', TimestampFormat::EpochSeconds))
            ->asTimestamp();
    }

    public function testStringAsTimestampRejectsInvalidValue(): void
    {
        $this->expectException(IncompatibleDocumentTypeException::class);

        (new JsonStringDocument('nope', TimestampFormat::DateTime))
            ->asTimestamp();
    }

    public function testDeserializerReadsDocumentMemberWithDiscriminator(): void
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
        $codec = new JsonCodec(
            new JsonCodecSettings(TimestampFormat::DateTime)
        );
        $members = [];
        $codec->createDeserializer(new StringRequestStream(
            '{"doc":{"__type":"smithy.test#Widget","Name":"a",'
            . '"created":"1970-01-01T00:00:10Z"}}'
        ))->deserialize($schema, $members);

        $document = $members[0];
        self::assertInstanceOf(MapDocument::class, $document);
        self::assertSame('smithy.test#Widget', $document->discriminator());
        self::assertSame(
            10.0,
            $document->getMember('created')?->asTimestamp()
        );
    }

    public function testDocumentConvertsToShapeThroughJsonNames(): void
    {
        $document = JsonDocumentReader::read(
            [
                'Name' => 'a',
                'data' => 'YWJjZA==',
                'created' => '1970-01-01T00:00:10Z',
                'count' => 3,
                'tags' => ['x', 'y']
            ],
            TimestampFormat::DateTime
        );

        $widget = $document?->asShape(WidgetFixture::class);

        self::assertEquals(
            new WidgetFixture('a', 'abcd', 10.0, 3, ['x', 'y']),
            $widget
        );
    }
}
