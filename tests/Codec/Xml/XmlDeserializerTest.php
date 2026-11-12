<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codec\Xml;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\Document\{
    StructDocument,
    UnionDocument
};
use Smithy\Tests\Codec\Xml\Utils\{
    XmlIntEnumFixture,
    XmlNestedStructFixture,
    XmlSampleStructFixture,
    XmlSampleUnionFixture,
    XmlStringEnumFixture
};
use Smithy\Codec\Xml\XmlDeserializer;

final class XmlDeserializerTest extends TestCase
{
    public function testDeserializesScalarMembersAndEnums(): void
    {
        $deserializer = $this->createDeserializer(
            '<SampleStruct xmlns="urn:test" id="abc"><name>alpha</name>'
            . '<notes xmlns:n="urn:notes">memo</notes><count>3</count>'
            . '<active>true</active><ratio>Infinity</ratio><data>AQI='
            . '</data><created>100</created><status>open</status><code>'
            . '7</code></SampleStruct>'
        );
        $members = \array_fill(
            0,
            XmlSampleStructFixture::schema()->getMemberCount(),
            null
        );

        $deserializer->deserialize(XmlSampleStructFixture::schema(), $members);
        $output = XmlSampleStructFixture::fromMembers($members);

        self::assertSame('abc', $output->id);
        self::assertSame('alpha', $output->name);
        self::assertSame('memo', $output->notes);
        self::assertSame(3, $output->count);
        self::assertTrue($output->active);
        self::assertTrue(\is_infinite($output->ratio));
        self::assertSame("\x01\x02", $output->data);
        self::assertSame(100.0, $output->created);
        self::assertInstanceOf(XmlStringEnumFixture::class, $output->status);
        self::assertSame('open', $output->status->getValue());
        self::assertInstanceOf(XmlIntEnumFixture::class, $output->code);
        self::assertSame(7, $output->code->getValue());
    }

    public function testDeserializesCollectionsNestedStructsAndUnion(): void
    {
        $deserializer = $this->createDeserializer(
            '<SampleStruct xmlns="urn:test"><tags><member>one</member>'
            . '<member>two</member></tags><tag>flat-one</tag><tag>'
            . 'flat-two</tag><scores><entry><key>a</key><value>1</value>'
            . '</entry><entry><key>b</key><value>2</value></entry>'
            . '</scores><scoreEntry><key>x</key><value>ex</value>'
            . '</scoreEntry><scoreEntry><key>y</key><value>why</value>'
            . '</scoreEntry><child><value>kid</value></child><choice>'
            . '<count>2</count></choice><statusHistory><member>open</member>'
            . '<member>closed</member></statusHistory><milestones><entry>'
            . '<key>start</key><value>100</value></entry><entry><key>end'
            . '</key><value>200</value></entry></milestones><codeHistory>'
            . '<member>7</member><member>9</member></codeHistory>'
            . '<checkpoints><member>100</member><member>200</member>'
            . '</checkpoints></SampleStruct>'
        );
        $members = \array_fill(
            0,
            XmlSampleStructFixture::schema()->getMemberCount(),
            null
        );

        $deserializer->deserialize(XmlSampleStructFixture::schema(), $members);
        $output = XmlSampleStructFixture::fromMembers($members);

        self::assertSame(['one', 'two'], $output->tags);
        self::assertSame(['flat-one', 'flat-two'], $output->flatTags);
        self::assertSame(['a' => 1, 'b' => 2], $output->scores);
        self::assertSame(['x' => 'ex', 'y' => 'why'], $output->flatScores);
        self::assertInstanceOf(XmlNestedStructFixture::class, $output->child);
        self::assertSame('kid', $output->child->value);
        self::assertInstanceOf(XmlSampleUnionFixture::class, $output->choice);
        self::assertSame('count', $output->choice->tag());
        self::assertSame(2, $output->choice->value());
        self::assertInstanceOf(
            XmlStringEnumFixture::class,
            $output->statusHistory[0]
        );
        self::assertSame('open', $output->statusHistory[0]->getValue());
        self::assertSame('closed', $output->statusHistory[1]->getValue());
        self::assertSame(
            ['start' => 100.0, 'end' => 200.0],
            $output->milestones
        );
        self::assertInstanceOf(
            XmlIntEnumFixture::class,
            $output->codeHistory[0]
        );
        self::assertSame(7, $output->codeHistory[0]->getValue());
        self::assertSame(9, $output->codeHistory[1]->getValue());
        self::assertSame([100.0, 200.0], $output->checkpoints);
    }

    public function testDeserializesPrefixedCollectionsAndAttributes(): void
    {
        $deserializer = $this->createDeserializer(<<<'XML'
<a:SampleStruct xmlns:a="urn:test" xmlns:b="urn:other" b:id="abc">
    <a:tags><b:member>one</b:member><a:member>two</a:member></a:tags>
    <a:tag>flat-one</a:tag><b:tag>flat-two</b:tag>
    <a:scores>
        <a:entry><b:key>a</b:key><a:value>1</a:value></a:entry>
    </a:scores>
    <a:scoreEntry><b:key>x</b:key><a:value>ex</a:value></a:scoreEntry>
    <a:milestones>
        <a:entry><b:key>start</b:key><a:value>100</a:value></a:entry>
    </a:milestones>
    <a:checkpoints><b:member>100</b:member></a:checkpoints>
    <a:child><b:value>kid</b:value></a:child>
    <a:choice><b:count>2</b:count></a:choice>
</a:SampleStruct>
XML);
        $members = \array_fill(
            0,
            XmlSampleStructFixture::schema()->getMemberCount(),
            null
        );
        $deserializer->deserialize(XmlSampleStructFixture::schema(), $members);
        $output = XmlSampleStructFixture::fromMembers($members);

        self::assertSame('abc', $output->id);
        self::assertSame(['one', 'two'], $output->tags);
        self::assertSame(['flat-one', 'flat-two'], $output->flatTags);
        self::assertSame(['a' => 1], $output->scores);
        self::assertSame(['x' => 'ex'], $output->flatScores);
        self::assertSame(['start' => 100.0], $output->milestones);
        self::assertSame([100.0], $output->checkpoints);
        self::assertSame('kid', $output->child->value);
        self::assertSame('count', $output->choice->tag());
        self::assertSame(2, $output->choice->value());
    }

    #[DataProvider('mapNamespaceScopes')]
    public function testDeserializesLargeMapsAcrossNamespaceScopes(
        string $declarations,
        string $entryTemplate
    ): void
    {
        $entries = '';
        $scores = [];
        $milestones = [];
        for ($i = 0; $i < 64; $i++) {
            $key = 'key' . $i;
            $entries .= \sprintf($entryTemplate, $key, $i);
            $scores[$key] = $i;
            $milestones[$key] = (float) $i;
        }
        $xml = '<a:SampleStruct ' . $declarations . '>'
            . '<a:scores>' . $entries . '</a:scores>'
            . '<a:milestones>' . $entries . '</a:milestones>'
            . '</a:SampleStruct>';
        $schema = XmlSampleStructFixture::schema();
        $members = \array_fill(0, $schema->getMemberCount(), null);
        $this->createDeserializer($xml)->deserialize($schema, $members);
        $output = XmlSampleStructFixture::fromMembers($members);

        self::assertSame($scores, $output->scores);
        self::assertSame($milestones, $output->milestones);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function mapNamespaceScopes(): iterable
    {
        yield 'one namespace' => [
            'xmlns:a="urn:test"',
            '<a:entry><a:key>%s</a:key><a:value>%d</a:value></a:entry>'
        ];
        yield 'aliases share a URI' => [
            'xmlns:a="urn:test" xmlns:b="urn:test"',
            '<b:entry><a:key>%s</a:key><b:value>%d</b:value></b:entry>'
        ];
        yield 'mixed namespaces' => [
            'xmlns:a="urn:test" xmlns:b="urn:other"',
            '<a:entry><b:key>%s</b:key><a:value>%d</a:value></a:entry>'
        ];
        yield 'prefix rebound on entry' => [
            'xmlns:a="urn:test"',
            '<a:entry xmlns:a="urn:child"><a:key>%s</a:key>'
            . '<a:value>%d</a:value></a:entry>'
        ];
        yield 'default namespace cleared' => [
            'xmlns="urn:test" xmlns:a="urn:test"',
            '<entry xmlns=""><key>%s</key><value>%d</value></entry>'
        ];
        yield 'first duplicate local name wins' => [
            'xmlns:a="urn:test" xmlns:b="urn:other"',
            '<a:entry><b:key>%s</b:key><a:key>ignored</a:key>'
            . '<b:value>%d</b:value><a:value>999</a:value></a:entry>'
        ];
    }

    public function testDeserializesKnownRootUnionVariant(): void
    {
        XmlSampleUnionFixture::$indexedCreations = 0;
        $deserializer = $this->createDeserializer(
            '<SampleUnion><count>2</count></SampleUnion>'
        );
        $members = [null];

        $deserializer->deserialize(XmlSampleUnionFixture::schema(), $members);

        self::assertInstanceOf(XmlSampleUnionFixture::class, $members[0]);
        self::assertSame('count', $members[0]->tag());
        self::assertSame(2, $members[0]->value());
        self::assertSame(1, XmlSampleUnionFixture::$indexedCreations);
    }

    public function testDeserializesUnknownRootUnionVariant(): void
    {
        $deserializer = $this->createDeserializer(
            '<SampleUnion><mystery>zzz</mystery></SampleUnion>'
        );
        $members = [null];

        $deserializer->deserialize(XmlSampleUnionFixture::schema(), $members);

        self::assertInstanceOf(XmlSampleUnionFixture::class, $members[0]);
        self::assertSame('mystery', $members[0]->tag());
        self::assertSame('zzz', $members[0]->value());
    }

    public function testDeserializerCreatesDocumentsForRuntimeShapes(): void
    {
        $childSchema = new Schema(
            id: 'smithy.test#Child',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.api#String',
                    type: ShapeType::String_,
                    memberName: 'value',
                    memberIndex: 0
                )
            ]
        );
        $unionSchema = new Schema(
            id: 'smithy.test#Choice',
            type: ShapeType::Union,
            members: [
                new Schema(
                    id: 'smithy.api#Integer',
                    type: ShapeType::Integer,
                    memberName: 'count',
                    memberIndex: 0
                )
            ]
        );
        $enumSchema = new Schema(
            id: 'smithy.test#Status',
            type: ShapeType::Enum
        );
        $schema = new Schema(
            id: 'smithy.test#Container',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: $childSchema->id,
                    type: ShapeType::Structure,
                    memberName: 'child',
                    memberIndex: 0,
                    target: $childSchema
                ),
                new Schema(
                    id: $unionSchema->id,
                    type: ShapeType::Union,
                    memberName: 'choice',
                    memberIndex: 1,
                    target: $unionSchema
                ),
                new Schema(
                    id: $enumSchema->id,
                    type: ShapeType::Enum,
                    memberName: 'status',
                    memberIndex: 2,
                    target: $enumSchema
                )
            ]
        );
        $members = [null, null, null];

        $this->createDeserializer(
            '<Container><child><value>kid</value></child>'
            . '<choice><count>2</count></choice>'
            . '<status>open</status></Container>'
        )->deserialize($schema, $members);

        self::assertInstanceOf(StructDocument::class, $members[0]);
        self::assertSame('kid', $members[0]->getMemberValue(
            $childSchema->getMember('value')
        ));
        self::assertInstanceOf(UnionDocument::class, $members[1]);
        self::assertSame('count', $members[1]->tag());
        self::assertSame(2, $members[1]->value());
        self::assertSame('open', $members[2]);
    }

    public function testDeserializesClasslessEnumListsAsScalars(): void
    {
        $stringEnum = new Schema(
            id: 'smithy.test#Status',
            type: ShapeType::Enum
        );
        $intEnum = new Schema(
            id: 'smithy.test#Code',
            type: ShapeType::IntEnum
        );
        $schema = new Schema(
            id: 'smithy.test#Container',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.test#Statuses',
                    type: ShapeType::List_,
                    memberName: 'statuses',
                    memberIndex: 0,
                    listMember: $stringEnum
                ),
                new Schema(
                    id: 'smithy.test#Codes',
                    type: ShapeType::List_,
                    memberName: 'codes',
                    memberIndex: 1,
                    listMember: $intEnum
                )
            ]
        );
        $members = [null, null];

        $this->createDeserializer(
            '<Container><statuses><member>open</member><member>closed'
            . '</member></statuses><codes><member>7</member><member>9'
            . '</member></codes></Container>'
        )->deserialize($schema, $members);

        self::assertSame(['open', 'closed'], $members[0]);
        self::assertSame([7, 9], $members[1]);
    }

    private function createDeserializer(string $xml): XmlDeserializer
    {
        return new XmlDeserializer(new \SimpleXMLElement($xml));
    }
}
