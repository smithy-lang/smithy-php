<?php

declare(strict_types=1);

namespace Smithy\Tests\FormUrlEncoded;

use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\{
    Schema,
    SchemaExtensionKey,
    ShapeType,
    TimestampFormat
};
use Smithy\FormUrlEncoded\{
    FormUrlEncodedSchemaExtension,
    FormUrlEncodedSchemaExtensionProvider
};
use Smithy\Tests\FormUrlEncoded\Utils\{
    FormUrlEncodedSampleStructFixture,
    FormUrlEncodedSampleUnionFixture,
    FormUrlEncodedStringEnumFixture
};

final class FormUrlEncodedSchemaExtensionTest extends TestCase
{
    private static function key(): SchemaExtensionKey
    {
        return FormUrlEncodedSchemaExtensionProvider::key();
    }

    public function testStructInfoBuildsAwsAndEc2WireNames(): void
    {
        /** @var FormUrlEncodedSchemaExtension $info */
        $info = FormUrlEncodedSampleStructFixture::schema()
            ->getExtension(self::key());

        self::assertSame('name', $info->memberWireNames[0]);
        self::assertSame('wireName', $info->memberWireNames[1]);
        self::assertSame('notesText', $info->memberWireNames[2]);
        self::assertSame('Name', $info->ec2MemberWireNames[0]);
        self::assertSame('Wire', $info->ec2MemberWireNames[1]);
        self::assertSame('NotesText', $info->ec2MemberWireNames[2]);
        self::assertSame(
            TimestampFormat::EpochSeconds,
            $info->tsFormats[3]
        );
        self::assertSame([12 => true, 14 => true], $info->memberIsFlattened);
    }

    public function testListMemberInfoBuildsPrefixes(): void
    {
        $schema = FormUrlEncodedSampleStructFixture::schema();

        /** @var FormUrlEncodedSchemaExtension $tags */
        $tags = $schema->getMembersByIndex()[11]->getExtension(self::key());
        self::assertSame('member', $tags->listMemberName);
        self::assertFalse($tags->flattened);
        self::assertSame(ShapeType::String_->value, $tags->listMemberType);
        self::assertSame('tags.member.', $tags->memberListPrefix);
        self::assertSame('Tags.', $tags->ec2MemberListPrefix);

        /** @var FormUrlEncodedSchemaExtension $flatTags */
        $flatTags = $schema->getMembersByIndex()[12]
            ->getExtension(self::key());
        self::assertNull($flatTags->listMemberName);
        self::assertTrue($flatTags->flattened);
        self::assertSame('tag.', $flatTags->memberListPrefix);
        self::assertSame('Tag.', $flatTags->ec2MemberListPrefix);
    }

    public function testMapMemberInfoBuildsNamesPrefixesAndFormats(): void
    {
        $schema = FormUrlEncodedSampleStructFixture::schema();

        /** @var FormUrlEncodedSchemaExtension $flatScores */
        $flatScores = $schema->getMembersByIndex()[14]
            ->getExtension(self::key());
        self::assertSame('k', $flatScores->mapKeyName);
        self::assertSame('k=', $flatScores->mapKeyAssignment);
        self::assertSame('v', $flatScores->mapValueName);
        self::assertSame('v=', $flatScores->mapValueAssignment);
        self::assertSame('scoreEntry', $flatScores->mapEntryName);
        self::assertSame('scoreEntry.', $flatScores->memberMapEntryPrefix);
        self::assertSame(
            ShapeType::String_->value,
            $flatScores->mapValueType
        );

        $mapSchema = new Schema(
            id: 'smithy.test#TimestampMap',
            type: ShapeType::Map,
            memberName: 'events',
            memberIndex: 0,
            traits: [Schema::T_XML_NAME => 'Events'],
            mapKey: new Schema(
                id: 'smithy.test#TimestampMap$key',
                type: ShapeType::String_,
                memberName: 'key',
                memberIndex: 0,
                traits: [Schema::T_XML_NAME => 'K']
            ),
            mapValue: new Schema(
                id: 'smithy.test#TimestampMap$value',
                type: ShapeType::Timestamp,
                memberName: 'value',
                memberIndex: 1,
                traits: [
                    Schema::T_XML_NAME => 'V',
                    Schema::T_TIMESTAMP_FORMAT => 'http-date',
                ]
            )
        );

        /** @var FormUrlEncodedSchemaExtension $mapInfo */
        $mapInfo = $mapSchema->getExtension(self::key());
        self::assertSame('K', $mapInfo->mapKeyName);
        self::assertSame('V', $mapInfo->mapValueName);
        self::assertSame(
            TimestampFormat::HttpDate,
            $mapInfo->mapValueTimestampFormat
        );
        self::assertSame(
            ShapeType::Timestamp->value,
            $mapInfo->mapValueType
        );
        self::assertSame('Events.entry.', $mapInfo->memberMapEntryPrefix);
    }

    public function testScalarMemberInfoCarriesTimestampAndResolvedTarget(): void
    {
        $schema = FormUrlEncodedSampleStructFixture::schema();

        /** @var FormUrlEncodedSchemaExtension $created */
        $created = $schema->getMembersByIndex()[3]->getExtension(self::key());
        self::assertSame(
            TimestampFormat::EpochSeconds,
            $created->timestampFormat
        );

        /** @var FormUrlEncodedSchemaExtension $status */
        $status = $schema->getMembersByIndex()[9]->getExtension(self::key());
        self::assertSame(
            FormUrlEncodedStringEnumFixture::class,
            $status->resolvedTargetClass
        );
    }

    public function testUnionInfoBuildsVariantLookups(): void
    {
        /** @var FormUrlEncodedSchemaExtension $info */
        $info = FormUrlEncodedSampleUnionFixture::schema()
            ->getExtension(self::key());

        self::assertSame(
            ['value', 'count', 'created'],
            array_keys($info->unionMembersByName)
        );
        self::assertSame('value', $info->unionMemberWireNames['value']);
        self::assertSame('countValue', $info->unionMemberWireNames['count']);
        self::assertSame('Counter', $info->ec2UnionMemberWireNames['count']);
        self::assertSame(
            ['countValue', 'Counter', 'count', 'Count'],
            $info->unionEncodeByMemberIndex[1][1]
        );
        self::assertSame(
            TimestampFormat::EpochSeconds,
            $info->unionMemberTsFormats['created']
        );
    }

    public function testRootScalarSchemasReturnNull(): void
    {
        $schema = new Schema(
            id: 'smithy.test#Scalar',
            type: ShapeType::String_
        );

        self::assertNull($schema->getExtension(self::key()));
    }
}
