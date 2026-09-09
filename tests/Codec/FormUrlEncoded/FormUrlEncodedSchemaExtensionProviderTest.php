<?php

declare(strict_types=1);

namespace Smithy\Tests\Codec\FormUrlEncoded;

use PHPUnit\Framework\TestCase;
use Smithy\Codec\FormUrlEncoded\{
    FormUrlEncodedSchemaExtension,
    FormUrlEncodedSchemaExtensionProvider
};
use Smithy\Core\Serde\{
    Schema,
    SchemaExtensionKey,
    TimestampFormat
};
use Smithy\Core\Shape\ShapeType;
use Smithy\Tests\Codec\FormUrlEncoded\Utils\{
    FormUrlEncodedSampleStructFixture,
    FormUrlEncodedSampleUnionFixture
};

final class FormUrlEncodedSchemaExtensionProviderTest extends TestCase
{
    private static function key(): SchemaExtensionKey
    {
        return FormUrlEncodedSchemaExtensionProvider::key();
    }

    public function testKeyReturnsSingleton(): void
    {
        self::assertSame(self::key(), self::key());
    }

    public function testStructInfoBuildsResolvedWireNameModes(): void
    {
        /** @var FormUrlEncodedSchemaExtension $info */
        $info = FormUrlEncodedSampleStructFixture::schema()
            ->getExtension(self::key());

        $wireNames = $info->memberWireNamesByMode;
        self::assertSame(
            'name',
            $wireNames[FormUrlEncodedSchemaExtension::WIRE_MODE_RESOLVED][0]
        );
        self::assertSame(
            'wireName',
            $wireNames[FormUrlEncodedSchemaExtension::WIRE_MODE_RESOLVED][1]
        );
        self::assertSame(
            'notesText',
            $wireNames[FormUrlEncodedSchemaExtension::WIRE_MODE_RESOLVED][2]
        );
        self::assertSame(
            'Name',
            $wireNames[
                FormUrlEncodedSchemaExtension::WIRE_MODE_CAPITALIZED_RESOLVED
            ][0]
        );
        self::assertSame(
            'Wire',
            $wireNames[
                FormUrlEncodedSchemaExtension::WIRE_MODE_CAPITALIZED_RESOLVED
            ][1]
        );
        self::assertSame(
            'NotesText',
            $wireNames[
                FormUrlEncodedSchemaExtension::WIRE_MODE_CAPITALIZED_RESOLVED
            ][2]
        );
        self::assertSame(
            TimestampFormat::EpochSeconds,
            $info->tsFormats[3]
        );
    }

    public function testListMemberInfoBuildsPrefixes(): void
    {
        $schema = FormUrlEncodedSampleStructFixture::schema();

        /** @var FormUrlEncodedSchemaExtension $tags */
        $tags = $schema->getMembersByIndex()[11]->getExtension(self::key());
        self::assertSame('member', $tags->listMemberName);
        self::assertFalse($tags->flattened);
        self::assertSame(ShapeType::String_->value, $tags->listMemberType);
        self::assertSame(
            'tags.member.',
            $tags->resolvedMemberListPrefix
        );
        self::assertSame(
            'Tags.',
            $tags->capitalizedResolvedMemberListPrefix
        );

        /** @var FormUrlEncodedSchemaExtension $flatTags */
        $flatTags = $schema->getMembersByIndex()[12]
            ->getExtension(self::key());
        self::assertNull($flatTags->listMemberName);
        self::assertTrue($flatTags->flattened);
        self::assertSame('tag.', $flatTags->resolvedMemberListPrefix);
        self::assertSame(
            'Tag.',
            $flatTags->capitalizedResolvedMemberListPrefix
        );
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
                    Schema::T_TIMESTAMP_FORMAT => 'http-date'
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

    public function testRootCollectionInfoBuildsNamesAndFormats(): void
    {
        $listMember = new Schema(
            id: 'smithy.test#RootList$member',
            type: ShapeType::Timestamp,
            memberName: 'member',
            memberIndex: 0,
            traits: [
                Schema::T_XML_NAME => 'item',
                Schema::T_TIMESTAMP_FORMAT => 'http-date'
            ]
        );
        $list = new Schema(
            id: 'smithy.test#RootList',
            type: ShapeType::List_,
            listMember: $listMember
        );
        /** @var FormUrlEncodedSchemaExtension $listInfo */
        $listInfo = $list->getExtension(self::key());
        self::assertSame('item', $listInfo->listMemberName);
        self::assertSame($listMember, $listInfo->listMemberSchema);
        self::assertSame(
            TimestampFormat::HttpDate,
            $listInfo->listMemberTimestampFormat
        );

        $mapValue = new Schema(
            id: 'smithy.test#RootMap$value',
            type: ShapeType::Timestamp,
            memberName: 'value',
            memberIndex: 1,
            traits: [
                Schema::T_XML_NAME => 'V',
                Schema::T_TIMESTAMP_FORMAT => 'date-time'
            ]
        );
        $map = new Schema(
            id: 'smithy.test#RootMap',
            type: ShapeType::Map,
            mapKey: new Schema(
                id: 'smithy.test#RootMap$key',
                type: ShapeType::String_,
                memberName: 'key',
                memberIndex: 0,
                traits: [Schema::T_XML_NAME => 'K']
            ),
            mapValue: $mapValue
        );
        /** @var FormUrlEncodedSchemaExtension $mapInfo */
        $mapInfo = $map->getExtension(self::key());
        self::assertSame('K', $mapInfo->mapKeyName);
        self::assertSame('K=', $mapInfo->mapKeyAssignment);
        self::assertSame('V', $mapInfo->mapValueName);
        self::assertSame('V=', $mapInfo->mapValueAssignment);
        self::assertSame($mapValue, $mapInfo->mapValueSchema);
        self::assertSame(
            TimestampFormat::DateTime,
            $mapInfo->mapValueTimestampFormat
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
        self::assertSame(
            'value',
            $info->resolvedUnionMemberWireNames['value']
        );
        self::assertSame(
            'countValue',
            $info->resolvedUnionMemberWireNames['count']
        );
        self::assertSame(
            'Counter',
            $info->capitalizedResolvedUnionMemberWireNames['count']
        );
        self::assertSame(
            ['countValue', 'Counter', 'count', 'Count'],
            $info->unionEncodeByMemberIndex[1][1]
        );
        self::assertSame(
            TimestampFormat::EpochSeconds,
            $info->unionMemberTsFormats['created']
        );
    }

    public function testScalarSchemasReturnNull(): void
    {
        $root = new Schema(
            id: 'smithy.test#Scalar',
            type: ShapeType::String_
        );
        $member = new Schema(
            id: 'smithy.test#Input$value',
            type: ShapeType::Timestamp,
            memberName: 'value',
            memberIndex: 0,
            traits: [Schema::T_TIMESTAMP_FORMAT => 'epoch-seconds']
        );

        self::assertNull($root->getExtension(self::key()));
        self::assertNull($member->getExtension(self::key()));
    }
}
