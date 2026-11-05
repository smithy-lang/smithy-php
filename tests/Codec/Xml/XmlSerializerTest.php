<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codec\Xml;

use PHPUnit\Framework\TestCase;
use Smithy\Codec\Exception\SerializationException;
use Smithy\Core\Serde\{
    Schema,
    TimestampFormat
};
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\{
    BigDecimal,
    BigInteger
};
use Smithy\Core\Shape\Document\AbstractDocument;
use Smithy\Tests\Codec\Xml\Utils\{
    XmlNestedStructFixture,
    XmlSampleStructFixture,
    XmlSampleUnionFixture
};
use Smithy\Codec\Xml\{
    XmlCodecSettings,
    XmlSerializer
};

final class XmlSerializerTest extends TestCase
{
    public function testBeginStructSerializesScalarMembersAndNamespaces(): void
    {
        $serializer = $this->createSerializer();
        $schema = XmlSampleStructFixture::schema();
        $members = $schema->getMembersByIndex();

        $serializer->beginStruct($schema, 0);
        $serializer->serializeString($members[0], 'abc', 0);
        $serializer->serializeString($members[1], 'alpha', 1);
        $serializer->serializeString($members[2], 'memo', 2);
        $serializer->serializeInteger($members[3], 3, 3);
        $serializer->serializeBoolean($members[4], true, 4);
        $serializer->serializeFloat($members[5], INF, 5);
        $serializer->serializeBlob($members[6], "\x01\x02", 6);
        $serializer->serializeTimestamp(
            $members[7],
            100.0,
            7,
            TimestampFormat::EpochSeconds
        );
        $serializer->serializeBigInteger(
            $members[8],
            new BigInteger('18446744073709551616'),
            8
        );
        $serializer->serializeBigDecimal(
            $members[9],
            new BigDecimal('123.45'),
            9
        );
        $serializer->serializeString($members[10], 'open', 10);
        $serializer->serializeInteger($members[11], 7, 11);
        $serializer->endStruct();

        self::assertSame(
            '<SampleStruct xmlns="urn:test" id="abc"><name>alpha</name>'
            . '<notes xmlns:n="urn:notes">memo</notes><count>3</count>'
            . '<active>true</active><ratio>Infinity</ratio><data>AQI='
            . '</data><created>100</created><bigInteger>'
            . '18446744073709551616</bigInteger><bigDecimal>123.45'
            . '</bigDecimal><status>open</status><code>7</code>'
            . '</SampleStruct>',
            $serializer->flush()
        );
    }

    public function testEncodesXmlEndOfLineCharacters(): void
    {
        $cases = [
            'CiAK' => '&#xA; &#xA;',
            'YQ0KIGIKIGMN' => 'a&#xD;&#xA; b&#xA; c&#xD;',
            'YQ3ChSBiwoU=' => 'a&#xD;&#x85; b&#x85;',
            'YQ3igKggYsKFIGPigKg=' =>
                'a&#xD;&#x2028; b&#x85; c&#x2028;',
        ];

        foreach ($cases as $encoded => $escaped) {
            $value = \base64_decode($encoded, true);
            self::assertIsString($value);

            $serializer = $this->createSerializer();
            $serializer->serializeStruct(
                XmlSampleStructFixture::schema(),
                new XmlSampleStructFixture(id: $value, name: $value),
                0
            );
            $xml = $serializer->flush();

            self::assertSame(
                '<SampleStruct xmlns="urn:test" id="' . $escaped . '">'
                    . '<name>' . $escaped . '</name></SampleStruct>',
                $xml
            );

            $parsed = new \SimpleXMLElement($xml);
            self::assertSame($value, (string) $parsed['id']);
            self::assertSame($value, (string) $parsed->name);
        }
    }

    public function testEscapesXmlMarkupOnEndOfLinePath(): void
    {
        $value = "A & <B> \"quoted\" and 'single'\n";
        $escaped = "A &amp; &lt;B&gt; &quot;quoted&quot; and 'single'"
            . '&#xA;';
        $serializer = $this->createSerializer();

        $serializer->serializeStruct(
            XmlSampleStructFixture::schema(),
            new XmlSampleStructFixture(id: $value, name: $value),
            0
        );
        $xml = $serializer->flush();

        self::assertSame(
            '<SampleStruct xmlns="urn:test" id="' . $escaped . '">'
                . '<name>' . $escaped . '</name></SampleStruct>',
            $xml
        );

        $parsed = new \SimpleXMLElement($xml);
        self::assertSame($value, (string) $parsed['id']);
        self::assertSame($value, (string) $parsed->name);
    }

    public function testEncodesXmlEndOfLineCharactersInAggregateValues(): void
    {
        $value = "\r\n";
        $escaped = '&#xD;&#xA;';
        $serializer = $this->createSerializer();

        $serializer->serializeStruct(
            XmlSampleStructFixture::schema(),
            new XmlSampleStructFixture(
                tags: [$value],
                flatTags: [$value],
                flatScores: [$value => $value],
                child: new XmlNestedStructFixture($value),
                choice: XmlSampleUnionFixture::unknown('mystery', $value)
            ),
            0
        );

        self::assertSame(
            '<SampleStruct xmlns="urn:test"><tags><member>' . $escaped
                . '</member></tags><tag>' . $escaped . '</tag><scoreEntry>'
                . '<key>' . $escaped . '</key><value>' . $escaped
                . '</value></scoreEntry><child><value>' . $escaped
                . '</value></child><choice><mystery>' . $escaped
                . '</mystery></choice></SampleStruct>',
            $serializer->flush()
        );
    }

    public function testSerializeStructHandlesListsMapsNestedStructsAndUnion(): void
    {
        $serializer = $this->createSerializer();
        $value = new XmlSampleStructFixture(
            tags: ['one', 'two'],
            flatTags: ['flat-one', 'flat-two'],
            scores: ['a' => 1, 'b' => 2],
            flatScores: ['x' => 'ex', 'y' => 'why'],
            child: new XmlNestedStructFixture('kid'),
            choice: XmlSampleUnionFixture::create('count', 2)
        );

        $serializer->serializeStruct(
            XmlSampleStructFixture::schema(),
            $value,
            0
        );

        self::assertSame(
            '<SampleStruct xmlns="urn:test"><tags><member>one</member>'
            . '<member>two</member></tags><tag>flat-one</tag><tag>'
            . 'flat-two</tag><scores><entry><key>a</key><value>1</value>'
            . '</entry><entry><key>b</key><value>2</value></entry>'
            . '</scores><scoreEntry><key>x</key><value>ex</value>'
            . '</scoreEntry><scoreEntry><key>y</key><value>why</value>'
            . '</scoreEntry><child><value>kid</value></child><choice>'
            . '<count>2</count></choice></SampleStruct>',
            $serializer->flush()
        );
    }

    public function testSerializeUnionFallsBackToUnknownTag(): void
    {
        $serializer = $this->createSerializer();

        $serializer->serializeUnion(
            XmlSampleUnionFixture::schema(),
            XmlSampleUnionFixture::unknown('mystery', 'zzz'),
            0
        );

        self::assertSame(
            '<SampleUnion><mystery>zzz</mystery></SampleUnion>',
            $serializer->flush()
        );
    }

    public function testSerializeRootTimestampListUsesSchemaMetadata(): void
    {
        $serializer = $this->createSerializer();
        $schema = new Schema(
            id: 'test#TimestampList',
            type: ShapeType::List_,
            listMember: new Schema(
                id: 'smithy.api#Timestamp',
                type: ShapeType::Timestamp,
                traits: [Schema::T_TIMESTAMP_FORMAT => 'epoch-seconds']
            ),
            traits: [
                Schema::T_XML_NAMESPACE => ['uri' => 'urn:items'],
            ]
        );

        $serializer->serializeList(
            $schema,
            [100.0, 200.0],
            0
        );

        self::assertSame(
            '<TimestampList xmlns="urn:items"><member>100</member><member>'
            . '200</member></TimestampList>',
            $serializer->flush()
        );
    }

    public function testSerializeMapHandlesScalarSchemasWithoutXmlExtensions(): void
    {
        $serializer = $this->createSerializer();
        $schema = new Schema(
            id: 'test#StringMap',
            type: ShapeType::Map,
            memberName: 'entries',
            memberIndex: 0,
            mapKey: new Schema(
                id: 'smithy.api#String',
                type: ShapeType::String_
            ),
            mapValue: new Schema(
                id: 'smithy.api#String',
                type: ShapeType::String_
            )
        );

        $serializer->serializeMap($schema, ['a' => 'b'], 0);

        self::assertSame(
            '<entries><entry><key>a</key><value>b</value></entry></entries>',
            $serializer->flush()
        );
    }

    public function testSerializeMapHandlesMissingKeyAndValueSchemas(): void
    {
        $serializer = $this->createSerializer();
        $schema = new Schema(
            id: 'test#LooseMap',
            type: ShapeType::Map,
            memberName: 'entries',
            memberIndex: 0
        );

        $serializer->serializeMap($schema, ['a' => 1], 0);

        self::assertSame(
            '<entries><entry><key>a</key><value>1</value></entry></entries>',
            $serializer->flush()
        );
    }

    public function testRejectsAbstractDocumentValues(): void
    {
        $serializer = $this->createSerializer();
        $schema = new Schema(
            id: 'com.example#Foo$payload',
            type: ShapeType::Document,
            memberName: 'payload',
            memberIndex: 0
        );

        $this->expectException(SerializationException::class);
        $this->expectExceptionMessageMatches(
            '/XML protocols do not support document-typed members/'
        );

        $serializer->serializeDocument(
            $schema,
            AbstractDocument::fromString('hi'),
            0
        );
    }

    public function testRejectsNullDocumentValue(): void
    {
        $serializer = $this->createSerializer();
        $schema = new Schema(
            id: 'com.example#Foo$payload',
            type: ShapeType::Document,
            memberName: 'payload',
            memberIndex: 0
        );

        $this->expectException(SerializationException::class);

        $serializer->serializeDocument($schema, null, 0);
    }

    private function createSerializer(
        ?XmlCodecSettings $settings = null
    ): XmlSerializer
    {
        $writer = new \XMLWriter();
        $writer->openMemory();

        return new XmlSerializer($writer, $settings);
    }
}
