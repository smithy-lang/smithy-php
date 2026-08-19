<?php

declare(strict_types=1);

namespace Smithy\Tests\Xml;

use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\{
    Schema,
    ShapeType
};
use Smithy\Core\Shape\Document\{
    StructDocument,
    UnionDocument
};
use Smithy\Tests\Xml\Utils\{
    XmlIntEnumFixture,
    XmlNestedStructFixture,
    XmlSampleStructFixture,
    XmlSampleUnionFixture,
    XmlStringEnumFixture
};
use Smithy\Xml\XmlDeserializer;

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
