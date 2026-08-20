<?php

declare(strict_types=1);

namespace Smithy\Tests\Codec\Cbor;

use PHPUnit\Framework\TestCase;
use Smithy\Codec\Cbor\{
    CborCodecSettings,
    CborSerializer
};
use Smithy\Core\Serde\{
    Schema,
    ShapeType,
    TimestampFormat
};
use Smithy\Core\Shape\{
    BigDecimal,
    BigInteger
};
use Smithy\Tests\Codec\Cbor\Utils\{
    CborSerializerSampleStructFixture,
    CborSerializerSampleUnionFixture
};

/**
 * Cover the CBOR serializer.
 */
final class CborSerializerTest extends TestCase
{
    public function testSerializesStructWithNestedListAndMap(): void
    {
        $serializer = new CborSerializer();
        $value = new CborSerializerSampleStructFixture(
            name: 'abc',
            count: 1,
            active: true,
            tags: ['x', null, 'y'],
            scores: ['a' => 1, 'b' => null]
        );

        $serializer->serializeStruct(
            CborSerializerSampleStructFixture::schema(),
            $value,
            0
        );

        $expected = 'a5646e616d656361626365636f756e740166616374697665f5'
            . '647461677382617861796673636f726573a1616101';
        self::assertSame(
            $expected,
            \bin2hex($serializer->flush())
        );
    }

    public function testSerializesTimestampIntegerAndFractional(): void
    {
        $schema = new Schema(id: 'smithy.test#T', type: ShapeType::Timestamp);
        $serializer = new CborSerializer();

        $serializer->serializeTimestamp($schema, 100.0, 0);
        self::assertSame('c11864', \bin2hex($serializer->flush()));

        $serializer->reset();
        $serializer->serializeTimestamp($schema, 100.5, 0);
        self::assertSame(
            'c1fb4059200000000000',
            \bin2hex($serializer->flush())
        );
    }

    public function testSerializesTimestampUsingCodecSettingsDefault(): void
    {
        $schema = new Schema(id: 'smithy.test#T', type: ShapeType::Timestamp);
        $serializer = new CborSerializer(
            new CborCodecSettings(
                defaultTimestampFormat: TimestampFormat::DateTime
            )
        );

        $serializer->serializeTimestamp($schema, 100.0, 0);
        self::assertSame(
            \bin2hex("\x74" . '1970-01-01T00:01:40Z'),
            \bin2hex($serializer->flush())
        );
    }

    public function testCanIgnoreTimestampFormatTrait(): void
    {
        $schema = new Schema(
            id: 'smithy.test#T',
            type: ShapeType::Timestamp,
            traits: [Schema::T_TIMESTAMP_FORMAT => 'date-time']
        );
        $serializer = new CborSerializer(
            new CborCodecSettings(
                defaultTimestampFormat: TimestampFormat::EpochSeconds,
                honorTimestampFormatTrait: false
            )
        );

        $serializer->serializeTimestamp($schema, 100.0, 0);

        self::assertSame('c11864', \bin2hex($serializer->flush()));
    }

    public function testSerializesFloatUsingSchemaWidth(): void
    {
        $floatSchema = new Schema(id: 'smithy.test#F', type: ShapeType::Float_);
        $doubleSchema = new Schema(
            id: 'smithy.test#D',
            type: ShapeType::Double
        );
        $serializer = new CborSerializer();

        $serializer->serializeFloat($floatSchema, 1.5, 0);
        self::assertSame('fa3fc00000', \bin2hex($serializer->flush()));

        $serializer->reset();
        $serializer->serializeFloat($doubleSchema, 1.5, 0);
        self::assertSame('fb3ff8000000000000', \bin2hex($serializer->flush()));
    }

    public function testSerializesBigIntegerAndBigDecimal(): void
    {
        $bigIntegerSchema = new Schema(
            id: 'smithy.test#BI',
            type: ShapeType::BigInteger
        );
        $bigDecimalSchema = new Schema(
            id: 'smithy.test#BD',
            type: ShapeType::BigDecimal
        );
        $serializer = new CborSerializer();

        $serializer->serializeBigInteger(
            $bigIntegerSchema,
            new BigInteger('18446744073709551616'),
            0
        );
        self::assertSame(
            'c249010000000000000000',
            \bin2hex($serializer->flush())
        );

        $serializer->reset();
        $serializer->serializeBigDecimal(
            $bigDecimalSchema,
            new BigDecimal('123.45'),
            0
        );
        self::assertSame('c48221193039', \bin2hex($serializer->flush()));
    }

    public function testSerializesUnionAsSingleEntryMap(): void
    {
        $serializer = new CborSerializer();
        $serializer->serializeUnion(
            CborSerializerSampleUnionFixture::schema(),
            new CborSerializerSampleUnionFixture('count', 2),
            0
        );

        self::assertSame('a165636f756e7402', \bin2hex($serializer->flush()));
    }

    public function testSerializesUnionListsAndMaps(): void
    {
        $unionSchema = CborSerializerSampleUnionFixture::schema();
        $listSchema = new Schema(
            id: 'smithy.test#UnionList',
            type: ShapeType::List_,
            listMember: $unionSchema
        );
        $mapSchema = new Schema(
            id: 'smithy.test#UnionMap',
            type: ShapeType::Map,
            mapValue: $unionSchema
        );
        $serializer = new CborSerializer();

        $serializer->serializeList(
            $listSchema,
            [
                new CborSerializerSampleUnionFixture('count', 2),
                new CborSerializerSampleUnionFixture('value', 'x')
            ],
            0
        );
        self::assertSame(
            '82a165636f756e7402a16576616c75656178',
            \bin2hex($serializer->flush())
        );

        $serializer->reset();
        $serializer->serializeMap(
            $mapSchema,
            [
                'k' => new CborSerializerSampleUnionFixture('count', 3)
            ],
            0
        );
        self::assertSame(
            'a1616ba165636f756e7403',
            \bin2hex($serializer->flush())
        );
    }

    public function testSerializesTimestampCollectionsUsingMemberFormat(): void
    {
        $timestampMember = new Schema(
            id: 'smithy.test#TimestampCollection$member',
            type: ShapeType::Timestamp,
            traits: [Schema::T_TIMESTAMP_FORMAT => 'date-time']
        );
        $listSchema = new Schema(
            id: 'smithy.test#TimestampList',
            type: ShapeType::List_,
            listMember: $timestampMember
        );
        $mapSchema = new Schema(
            id: 'smithy.test#TimestampMap',
            type: ShapeType::Map,
            mapValue: $timestampMember
        );
        $serializer = new CborSerializer(
            new CborCodecSettings(honorTimestampFormatTrait: true)
        );

        $serializer->serializeList($listSchema, [0.0], 0);
        self::assertSame(
            \bin2hex("\x81\x74" . '1970-01-01T00:00:00Z'),
            \bin2hex($serializer->flush())
        );

        $serializer->reset();
        $serializer->serializeMap($mapSchema, ['k' => 0.0], 0);
        self::assertSame(
            \bin2hex("\xA1\x61k\x74" . '1970-01-01T00:00:00Z'),
            \bin2hex($serializer->flush())
        );
    }

    public function testRejectsUnknownTypedUnionVariantBeforeMutatingBuffer(): void
    {
        $serializer = new CborSerializer();

        try {
            $serializer->serializeUnion(
                CborSerializerSampleUnionFixture::schema(),
                ['missing' => 'value'],
                0
            );
            self::fail('Expected an exception for an unknown union variant.');
        } catch (\InvalidArgumentException $e) {
            self::assertSame(
                'Unknown union variant: missing',
                $e->getMessage()
            );
        }

        self::assertSame('', $serializer->flush());
    }
}
