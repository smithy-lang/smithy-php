<?php

declare(strict_types=1);

namespace Smithy\Tests\Cbor;

use PHPUnit\Framework\TestCase;
use Smithy\Cbor\{
    CborSchemaExtension,
    CborSchemaExtensionProvider
};
use Smithy\Core\Serde\{
    Schema,
    ShapeType,
    TimestampFormat
};
use Smithy\Tests\Cbor\Utils\{
    CborDeserializerSampleUnionFixture,
    CborSchemaFixture
};

/**
 * Cover the cached CBOR schema extension payload.
 */
final class CborSchemaExtensionTest extends TestCase
{
    public function testMemberSchemaCarriesEncodedNameBytes(): void
    {
        $schema = new Schema(
            id: 'smithy.example#Foo$name',
            type: ShapeType::String_,
            memberName: 'name',
            memberIndex: 0
        );

        /** @var CborSchemaExtension $info */
        $info = $schema->getExtension(CborSchemaExtensionProvider::key());

        self::assertSame("\x64name", $info->memberNameBytes);
    }

    public function testStructBuildsFieldNameTableLookupAndTemplate(): void
    {
        $schema = new Schema(
            id: 'smithy.example#Foo',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.example#Foo$name',
                    type: ShapeType::String_,
                    memberName: 'name',
                    memberIndex: 0
                ),
                new Schema(
                    id: 'smithy.example#Foo$age',
                    type: ShapeType::Integer,
                    memberName: 'age',
                    memberIndex: 1
                )
            ],
            targetClass: CborSchemaFixture::class
        );

        /** @var CborSchemaExtension $info */
        $info = $schema->getExtension(CborSchemaExtensionProvider::key());

        self::assertSame("\x64name", $info->fieldNameTable[0]);
        self::assertSame("\x63age", $info->fieldNameTable[1]);
        self::assertSame(2, $info->memberCount);
        self::assertSame([null, null], $info->membersTemplate);
        self::assertSame(CborSchemaFixture::class, $info->resolvedTargetClass);
        self::assertSame($schema, $info->resolvedTargetSchema);
        self::assertSame(
            ['name', 'age'],
            $info->orderedMemberNameBytes
        );
        self::assertSame([4, 3], $info->orderedMemberNameLengths);
        self::assertSame(
            ['name', 'age'],
            [
                $info->orderedMemberSchemas[0]->memberName,
                $info->orderedMemberSchemas[1]->memberName
            ]
        );
        self::assertCount(2, $info->orderedMemberNameHashes);
        self::assertNotSame(
            $info->orderedMemberNameHashes[0],
            $info->orderedMemberNameHashes[1]
        );
    }

    public function testListSchemasCarryFastPathMetadata(): void
    {
        $schema = new Schema(
            id: 'smithy.example#Names',
            type: ShapeType::List_,
            listMember: new Schema(
                id: 'smithy.example#Names$member',
                type: ShapeType::String_,
                memberName: 'member',
                memberIndex: 0
            ),
            traits: [Schema::T_SPARSE => true]
        );

        /** @var CborSchemaExtension $info */
        $info = $schema->getExtension(CborSchemaExtensionProvider::key());

        self::assertSame(ShapeType::String_->value, $info->listMemberType);
        self::assertSame($schema->listMember, $info->listMemberSchema);
        self::assertTrue($info->sparse);
    }

    public function testMapSchemasCarryFastPathMetadata(): void
    {
        $schema = new Schema(
            id: 'smithy.example#Tags',
            type: ShapeType::Map,
            mapKey: new Schema(
                id: 'smithy.example#Tags$key',
                type: ShapeType::String_
            ),
            mapValue: new Schema(
                id: 'smithy.example#Tags$value',
                type: ShapeType::Boolean
            ),
            traits: [Schema::T_SPARSE => true]
        );

        /** @var CborSchemaExtension $info */
        $info = $schema->getExtension(CborSchemaExtensionProvider::key());

        self::assertSame($schema->mapValue, $info->mapValueSchema);
        self::assertSame(ShapeType::Boolean->value, $info->mapValueType);
        self::assertTrue($info->sparse);
    }

    public function testCollectionsCarryTimestampFormats(): void
    {
        $timestamp = new Schema(
            id: 'smithy.example#Times$member',
            type: ShapeType::Timestamp,
            traits: [Schema::T_TIMESTAMP_FORMAT => 'date-time']
        );
        $list = new Schema(
            id: 'smithy.example#Times',
            type: ShapeType::List_,
            listMember: $timestamp
        );
        $map = new Schema(
            id: 'smithy.example#TimesByName',
            type: ShapeType::Map,
            mapValue: $timestamp
        );

        /** @var CborSchemaExtension $listInfo */
        $listInfo = $list->getExtension(
            CborSchemaExtensionProvider::key()
        );
        /** @var CborSchemaExtension $mapInfo */
        $mapInfo = $map->getExtension(
            CborSchemaExtensionProvider::key()
        );

        self::assertSame(
            TimestampFormat::DateTime,
            $listInfo->listMemberTimestampFormat
        );
        self::assertSame(
            TimestampFormat::DateTime,
            $mapInfo->mapValueTimestampFormat
        );
    }

    public function testUnionSchemasCarryCompleteEntries(): void
    {
        /** @var CborSchemaExtension $info */
        $info = CborDeserializerSampleUnionFixture::schema()->getExtension(
            CborSchemaExtensionProvider::key()
        );

        self::assertSame(
            [1, 'count', ShapeType::Integer->value],
            \array_slice($info->unionDecodeEntries[1], 0, 3)
        );
        self::assertSame(
            "\xA1\x65count",
            $info->unionEncodeByMemberName['count'][3]
        );
        self::assertSame(
            $info->unionEncodeByMemberName['count'],
            $info->unionEncodeByMemberIndex[1]
        );
        self::assertTrue($info->hasIndexedUnionFactory);
    }

    public function testRootScalarSchemasReturnNull(): void
    {
        $schema = new Schema(
            id: 'smithy.example#Scalar',
            type: ShapeType::String_
        );

        self::assertNull(
            $schema->getExtension(CborSchemaExtensionProvider::key())
        );
    }
}
