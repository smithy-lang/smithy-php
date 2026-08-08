<?php

declare(strict_types=1);

namespace Smithy\Tests\Cbor;

use PHPUnit\Framework\TestCase;
use Smithy\Cbor\CborDeserializer;
use Smithy\Core\Serde\{
    Schema,
    ShapeType
};
use Smithy\Core\Shape\{
    BigDecimal,
    BigInteger
};
use Smithy\Tests\Cbor\Utils\{
    CborDeserializerSampleStructFixture,
    CborDeserializerSampleUnionFixture,
    CborDeserializerSparseStructFixture
};

/**
 * Cover the CBOR deserializer.
 */
final class CborDeserializerTest extends TestCase
{
    public function testDeserializesDefiniteAndIndefiniteStructMembers(): void
    {
        $payloads = [
            'definite' =>
                'a5646e616d656361626365636f756e740166616374697665f5'
                . '647461677382617861796673636f726573a1616101',
            'indefinite' =>
                'bf646e616d656361626365636f756e740166616374697665f5'
                . '64746167739f61786179ff6673636f726573bf616101ffff'
        ];

        foreach ($payloads as $payload) {
            $members = [null, null, null, null, null];
            $deserializer = new CborDeserializer(\hex2bin($payload));
            $deserializer->deserialize(
                CborDeserializerSampleStructFixture::schema(),
                $members
            );

            self::assertSame('abc', $members[0]);
            self::assertSame(1, $members[1]);
            self::assertTrue($members[2]);
            self::assertSame(['x', 'y'], $members[3]);
            self::assertSame(['a' => 1], $members[4]);
        }
    }

    public function testDeserializesDefiniteAndIndefiniteSparseNulls(): void
    {
        $payloads = [
            'definite' =>
                'a26a7370617273654c697374826161f6'
                . '697370617273654d6170a16178f6',
            'indefinite' =>
                'bf6a7370617273654c6973749f6161f6ff'
                . '697370617273654d6170bf6178f6ffff'
        ];

        foreach ($payloads as $payload) {
            $members = [null, null];
            $deserializer = new CborDeserializer(\hex2bin($payload));
            $deserializer->deserialize(
                CborDeserializerSparseStructFixture::schema(),
                $members
            );

            self::assertSame(['a', null], $members[0]);
            self::assertSame(['x' => null], $members[1]);
        }
    }

    public function testDeserializesDefiniteAndIndefiniteUnionVariants(): void
    {
        $payloads = [
            ['a165636f756e7402', 'count', 2],
            ['bf65636f756e7402ff', 'count', 2],
            ['a167756e6b6e6f776e01', 'unknown', 1],
            ['bf67756e6b6e6f776e01ff', 'unknown', 1]
        ];

        foreach ($payloads as [$payload, $tag, $value]) {
            $members = [null];
            $deserializer = new CborDeserializer(\hex2bin($payload));
            $deserializer->deserialize(
                CborDeserializerSampleUnionFixture::schema(),
                $members
            );

            self::assertInstanceOf(
                CborDeserializerSampleUnionFixture::class,
                $members[0]
            );
            self::assertSame($tag, $members[0]->tag());
            self::assertSame($value, $members[0]->value());
        }
    }

    public function testDeserializesTimestampAndFloats(): void
    {
        $members = [null];
        (new CborDeserializer(\hex2bin('bf6176c11864ff')))->deserialize(
            new Schema(
                id: 'smithy.test#TimestampWrap',
                type: ShapeType::Structure,
                members: [
                    new Schema(
                        id: 'smithy.test#TimestampWrap$v',
                        type: ShapeType::Timestamp,
                        memberName: 'v',
                        memberIndex: 0
                    )
                ]
            ),
            $members
        );
        self::assertSame(100.0, $members[0]);

        $members = [null, null];
        (new CborDeserializer(
            \hex2bin('bf6166fa3fc00000616401ff')
        ))->deserialize(
            new Schema(
                id: 'smithy.test#Nums',
                type: ShapeType::Structure,
                members: [
                    new Schema(
                        id: 'smithy.test#Nums$f',
                        type: ShapeType::Float_,
                        memberName: 'f',
                        memberIndex: 0
                    ),
                    new Schema(
                        id: 'smithy.test#Nums$d',
                        type: ShapeType::Double,
                        memberName: 'd',
                        memberIndex: 1
                    )
                ]
            ),
            $members
        );
        self::assertSame(1.5, $members[0]);
        self::assertSame(1, $members[1]);
    }

    public function testDeserializesBigIntegerAndBigDecimal(): void
    {
        $members = [null, null];
        $de = new CborDeserializer(
            \hex2bin('bf626269c249010000000000000000626264c48221193039ff')
        );
        $de->deserialize(
            new Schema(
                id: 'smithy.test#Bigs',
                type: ShapeType::Structure,
                members: [
                    new Schema(
                        id: 'smithy.test#Bigs$bi',
                        type: ShapeType::BigInteger,
                        memberName: 'bi',
                        memberIndex: 0
                    ),
                    new Schema(
                        id: 'smithy.test#Bigs$bd',
                        type: ShapeType::BigDecimal,
                        memberName: 'bd',
                        memberIndex: 1
                    )
                ]
            ),
            $members
        );

        self::assertInstanceOf(BigInteger::class, $members[0]);
        self::assertSame('18446744073709551616', $members[0]->value);
        self::assertInstanceOf(BigDecimal::class, $members[1]);
        self::assertSame('12345e-2', $members[1]->value);
    }
}
