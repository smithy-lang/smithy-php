<?php

declare(strict_types=1);

namespace Smithy\Tests\Cbor;

use PHPUnit\Framework\TestCase;
use Smithy\Cbor\CborSerializer;
use Smithy\Core\Serde\{
    Schema,
    ShapeType
};
use Smithy\Core\Shape\{
    BigDecimal,
    BigInteger
};
use Smithy\Tests\Cbor\Utils\{
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
