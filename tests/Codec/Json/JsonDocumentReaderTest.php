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
use Smithy\Core\Shape\Document\AbstractDocument;
use Smithy\Core\Shape\Document\IncompatibleDocumentTypeException;
use Smithy\Core\Shape\Document\ListDocument;
use Smithy\Core\Shape\Document\MapDocument;
use Smithy\Core\Shape\ShapeType;
use Smithy\Http\Message\StringRequestStream;
use Smithy\Tests\Core\Shape\Document\Fixture\StampedFixture;
use Smithy\Tests\Core\Shape\Document\Fixture\WidgetFixture;

#[CoversClass(JsonDocumentReader::class)]
#[CoversClass(JsonStringDocument::class)]
final class JsonDocumentReaderTest extends TestCase
{
    public function testObjectKeepsTypeDiscriminator(): void
    {
        $document = JsonDocumentReader::read(
            ['__type' => 'smithy.test#Widget', 'Name' => 'a'],
            new JsonCodecSettings(TimestampFormat::EpochSeconds)
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
            new JsonCodecSettings(TimestampFormat::EpochSeconds)
        );

        self::assertSame('smithy.test#Widget', $document?->discriminator());
    }

    public function testObjectWithoutTypeHasNoDiscriminator(): void
    {
        $document = JsonDocumentReader::read(
            ['a' => 1],
            new JsonCodecSettings(TimestampFormat::EpochSeconds)
        );

        self::assertNull($document?->discriminator());
    }

    public function testNonStringTypeIsNotADiscriminator(): void
    {
        $document = JsonDocumentReader::read(
            ['__type' => 5],
            new JsonCodecSettings(TimestampFormat::EpochSeconds)
        );

        self::assertNull($document?->discriminator());
    }

    public function testNestedObjectsAndListsKeepDiscriminators(): void
    {
        $document = JsonDocumentReader::read(
            ['items' => [['__type' => 'smithy.test#Widget']]],
            new JsonCodecSettings(TimestampFormat::EpochSeconds)
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
            JsonDocumentReader::read(null, new JsonCodecSettings(TimestampFormat::EpochSeconds))
        );
        self::assertSame(
            ShapeType::Boolean,
            JsonDocumentReader::read(true, new JsonCodecSettings(TimestampFormat::EpochSeconds))?->type()
        );
        self::assertSame(
            ShapeType::Integer,
            JsonDocumentReader::read(1, new JsonCodecSettings(TimestampFormat::EpochSeconds))?->type()
        );
        self::assertInstanceOf(
            JsonStringDocument::class,
            JsonDocumentReader::read('x', new JsonCodecSettings(TimestampFormat::EpochSeconds))
        );
    }

    public function testStringAsBlobDecodesBase64(): void
    {
        $document = new JsonStringDocument(
            'YWJjZA==',
            new JsonCodecSettings(TimestampFormat::EpochSeconds)
        );

        self::assertSame('abcd', $document->asBlob());
    }

    public function testStringAsBlobRejectsInvalidBase64(): void
    {
        $this->expectException(IncompatibleDocumentTypeException::class);

        (new JsonStringDocument('***', new JsonCodecSettings(TimestampFormat::EpochSeconds)))->asBlob();
    }

    public function testStringAsTimestampParsesDateTime(): void
    {
        $document = new JsonStringDocument(
            '1970-01-01T00:00:10Z',
            new JsonCodecSettings(TimestampFormat::DateTime)
        );

        self::assertSame(10.0, $document->asTimestamp());
    }

    public function testStringAsTimestampParsesHttpDate(): void
    {
        $document = new JsonStringDocument(
            'Thu, 01 Jan 1970 00:00:10 GMT',
            new JsonCodecSettings(TimestampFormat::HttpDate)
        );

        self::assertSame(10.0, $document->asTimestamp());
    }

    public function testStringAsTimestampRejectsEpochSecondsFormat(): void
    {
        $this->expectException(IncompatibleDocumentTypeException::class);

        (new JsonStringDocument('10', new JsonCodecSettings(TimestampFormat::EpochSeconds)))
            ->asTimestamp();
    }

    public function testStringAsTimestampRejectsInvalidValue(): void
    {
        $this->expectException(IncompatibleDocumentTypeException::class);

        (new JsonStringDocument('nope', new JsonCodecSettings(TimestampFormat::DateTime)))
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
            new JsonCodecSettings(TimestampFormat::DateTime)
        );

        $widget = $document?->asShape(WidgetFixture::class);

        self::assertEquals(
            new WidgetFixture('a', 'abcd', 10.0, 3, ['x', 'y']),
            $widget
        );
    }

    public function testRestJsonSettingsReadJsonName(): void
    {
        $document = JsonDocumentReader::read(
            ['Name' => 'json', 'name' => 'member'],
            new JsonCodecSettings(TimestampFormat::DateTime, honorJsonName: true)
        );

        self::assertSame('json', $document?->asShape(WidgetFixture::class)->name);
    }

    public function testAwsJsonSettingsIgnoreJsonName(): void
    {
        $settings = new JsonCodecSettings(
            TimestampFormat::EpochSeconds,
            honorJsonName: false
        );

        $named = JsonDocumentReader::read(['Name' => 'json', 'name' => 'member'], $settings);
        $onlyJsonName = JsonDocumentReader::read(['Name' => 'json'], $settings);

        self::assertSame('member', $named?->asShape(WidgetFixture::class)->name);
        self::assertNull($onlyJsonName?->asShape(WidgetFixture::class)->name);
    }

    public function testUserBuiltDocumentKeepsJsonNameFirstLookup(): void
    {
        $document = AbstractDocument::fromValue(['Name' => 'json', 'name' => 'member']);

        self::assertSame('json', $document?->asShape(WidgetFixture::class)->name);
    }

    public function testMemberTimestampFormatTraitOverridesDefault(): void
    {
        $document = JsonDocumentReader::read(
            [
                'http' => 'Thu, 01 Jan 1970 00:00:10 GMT',
                'epoch' => '20',
                'plain' => '1970-01-01T00:00:30Z'
            ],
            new JsonCodecSettings(TimestampFormat::DateTime)
        );

        $stamped = $document?->asShape(StampedFixture::class);

        self::assertSame(10.0, $stamped?->http);
        self::assertSame(20.0, $stamped?->epoch);
        self::assertSame(30.0, $stamped?->plain);
    }

    public function testTimestampFormatTraitIgnoredWhenNotHonored(): void
    {
        $document = JsonDocumentReader::read(
            [
                'http' => '1970-01-01T00:00:10Z',
                'epoch' => '1970-01-01T00:00:20Z'
            ],
            new JsonCodecSettings(
                TimestampFormat::DateTime,
                honorTimestampFormatTrait: false
            )
        );

        $stamped = $document?->asShape(StampedFixture::class);

        self::assertSame(10.0, $stamped?->http);
        self::assertSame(20.0, $stamped?->epoch);
    }

    public function testNestedStructInheritsSettings(): void
    {
        $document = JsonDocumentReader::read(
            ['child' => ['Name' => 'json', 'name' => 'member', 'created' => '1970-01-01T00:00:05Z']],
            new JsonCodecSettings(TimestampFormat::DateTime, honorJsonName: false)
        );

        $child = $document?->asShape(StampedFixture::class)->child;

        self::assertSame('member', $child?->name);
        self::assertSame(5.0, $child?->created);
    }

    public function testEpochSecondsStringRejectsNonNumericValue(): void
    {
        $document = JsonDocumentReader::read(
            ['epoch' => 'soon'],
            new JsonCodecSettings(TimestampFormat::DateTime)
        );

        $this->expectException(IncompatibleDocumentTypeException::class);

        $document?->asShape(StampedFixture::class);
    }

    public function testReaderAttachesSettingsToEveryDocument(): void
    {
        $settings = new JsonCodecSettings(TimestampFormat::HttpDate);
        $document = JsonDocumentReader::read(['a' => ['b'], 'c' => 'd'], $settings);

        self::assertInstanceOf(MapDocument::class, $document);
        self::assertSame($settings, $document->getDocumentSettings());
        $list = $document->getMember('a');
        self::assertInstanceOf(ListDocument::class, $list);
        self::assertSame($settings, $list->getDocumentSettings());
        $string = $document->getMember('c');
        self::assertInstanceOf(JsonStringDocument::class, $string);
        self::assertSame($settings, $string->getDocumentSettings());
        self::assertNull(AbstractDocument::fromMap([])->getDocumentSettings());
    }
}
