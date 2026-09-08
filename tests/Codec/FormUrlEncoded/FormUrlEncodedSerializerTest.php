<?php

declare(strict_types=1);

namespace Smithy\Tests\Codec\FormUrlEncoded;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Codec\Exception\SerializationException;
use Smithy\Codec\FormUrlEncoded\{
    FormUrlEncodedCodecSettings,
    FormUrlEncodedSerializer
};
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\TimestampFormat;
use Smithy\Core\Shape\{
    BigDecimal,
    BigInteger,
    Document\AbstractDocument
};
use Smithy\Core\Shape\ShapeType;
use Smithy\Tests\Codec\FormUrlEncoded\Utils\{
    FormUrlEncodedIntEnumFixture,
    FormUrlEncodedNestedStructFixture,
    FormUrlEncodedSampleStructFixture,
    FormUrlEncodedSampleUnionFixture,
    FormUrlEncodedStringEnumFixture
};

final class FormUrlEncodedSerializerTest extends TestCase
{
    public function testBeginRootStructSerializesScalarMembers(): void
    {
        $serializer = new FormUrlEncodedSerializer();
        $serializer->beginRootStruct(
            FormUrlEncodedSampleStructFixture::schema()
        );

        (new FormUrlEncodedSampleStructFixture(
            name: 'alpha beta',
            renamed: 'a/b',
            notes: 'memo',
            created: 100.0,
            active: true,
            ratio: INF,
            data: "\x01\x02",
            bigInteger: new BigInteger('18446744073709551616'),
            bigDecimal: new BigDecimal('123.45'),
            status: FormUrlEncodedStringEnumFixture::open(),
            code: FormUrlEncodedIntEnumFixture::seven()
        ))->serializeMembers($serializer);

        self::assertSame(
            'name=alpha%20beta&wireName=a%2Fb&notesText=memo&created=100'
            . '&active=true&ratio=Infinity&data=AQI%3D&bigInteger='
            . '18446744073709551616&bigDecimal=123.45&status=open&code=7',
            $serializer->flush()
        );
    }

    public function testSerializeStructHandlesCollectionsAndAggregates(): void
    {
        $serializer = new FormUrlEncodedSerializer();

        $serializer->serializeStruct(
            FormUrlEncodedSampleStructFixture::schema(),
            new FormUrlEncodedSampleStructFixture(
                tags: ['one', 'two'],
                flatTags: ['flat one', 'flat/two'],
                scores: ['a' => 1, 'b' => 2],
                flatScores: ['x' => 'ex', 'y' => 'why'],
                child: new FormUrlEncodedNestedStructFixture('kid'),
                choice: FormUrlEncodedSampleUnionFixture::create('count', 2)
            ),
            0
        );

        self::assertSame(
            'tags.member.1=one&tags.member.2=two&tag.1=flat%20one&tag.2='
            . 'flat%2Ftwo&scores.entry.1.key=a&scores.entry.1.value=1&'
            . 'scores.entry.2.key=b&scores.entry.2.value=2&scoreEntry.1.k='
            . 'x&scoreEntry.1.v=ex&scoreEntry.2.k=y&scoreEntry.2.v=why&'
            . 'child.value=kid&choice.countValue=2',
            $serializer->flush()
        );
    }

    public function testSerializesNestedAggregateCollections(): void
    {
        $listSchema = new Schema(
            id: 'smithy.test#Children',
            type: ShapeType::List_,
            memberName: 'children',
            memberIndex: 0,
            listMember: new Schema(
                id: 'smithy.test#Children$member',
                type: ShapeType::Structure,
                memberName: 'member',
                memberIndex: 0,
                targetClass: FormUrlEncodedNestedStructFixture::class,
                members: FormUrlEncodedNestedStructFixture::schema()
                    ->getMembersByIndex()
            )
        );
        $mapSchema = new Schema(
            id: 'smithy.test#ChildrenByName',
            type: ShapeType::Map,
            memberName: 'childrenByName',
            memberIndex: 1,
            mapKey: new Schema(
                id: 'smithy.test#ChildrenByName$key',
                type: ShapeType::String_,
                memberName: 'key',
                memberIndex: 0
            ),
            mapValue: new Schema(
                id: 'smithy.test#ChildrenByName$value',
                type: ShapeType::Structure,
                memberName: 'value',
                memberIndex: 1,
                targetClass: FormUrlEncodedNestedStructFixture::class,
                members: FormUrlEncodedNestedStructFixture::schema()
                    ->getMembersByIndex()
            )
        );
        $tailSchema = new Schema(
            id: 'smithy.test#AggregateCollections$tail',
            type: ShapeType::String_,
            memberName: 'tail',
            memberIndex: 2
        );
        $root = new Schema(
            id: 'smithy.test#AggregateCollections',
            type: ShapeType::Structure,
            members: [$listSchema, $mapSchema, $tailSchema]
        );

        $serializer = new FormUrlEncodedSerializer();
        $serializer->beginRootStruct($root);
        $serializer->serializeList(
            $listSchema,
            [
                new FormUrlEncodedNestedStructFixture('one'),
                null,
                new FormUrlEncodedNestedStructFixture('three')
            ],
            0
        );
        $serializer->serializeMap(
            $mapSchema,
            [
                'first' => new FormUrlEncodedNestedStructFixture('alpha'),
                'second' => new FormUrlEncodedNestedStructFixture('beta')
            ],
            1
        );
        $serializer->serializeString($tailSchema, 'done', 2);

        self::assertSame(
            'children.member.1.value=one'
                . '&children.member.3.value=three'
                . '&childrenByName.entry.1.key=first'
                . '&childrenByName.entry.1.value.value=alpha'
                . '&childrenByName.entry.2.key=second'
                . '&childrenByName.entry.2.value.value=beta'
                . '&tail=done',
            $serializer->flush()
        );
    }

    public function testEc2QueryUsesEc2NamingAndFlattenedLists(): void
    {
        $serializer = new FormUrlEncodedSerializer(
            new FormUrlEncodedCodecSettings(
                capitalizeResolvedWireNames: true,
                alwaysFlattenLists: true,
                supportsMaps: false
            )
        );
        $serializer->beginRootStruct(
            FormUrlEncodedSampleStructFixture::schema()
        );

        (new FormUrlEncodedSampleStructFixture(
            name: 'alpha',
            renamed: 'beta',
            notes: 'memo',
            tags: ['one', 'two'],
            flatTags: ['solo'],
            child: new FormUrlEncodedNestedStructFixture('kid'),
            choice: FormUrlEncodedSampleUnionFixture::create('count', 2)
        ))->serializeMembers($serializer);

        self::assertSame(
            'Name=alpha&Wire=beta&NotesText=memo&Tags.1=one&Tags.2=two&'
            . 'Tag.1=solo&Child.Value=kid&Choice.Counter=2',
            $serializer->flush()
        );
    }

    public function testCanIgnoreXmlNameTraits(): void
    {
        $serializer = new FormUrlEncodedSerializer(
            new FormUrlEncodedCodecSettings(honorXmlName: false)
        );
        $serializer->beginRootStruct(
            FormUrlEncodedSampleStructFixture::schema()
        );

        (new FormUrlEncodedSampleStructFixture(
            renamed: 'beta',
            notes: 'memo'
        ))->serializeMembers($serializer);

        self::assertSame(
            'renamed=beta&notes=memo',
            $serializer->flush()
        );
    }

    public function testCanCapitalizeRawMemberNames(): void
    {
        $serializer = new FormUrlEncodedSerializer(
            new FormUrlEncodedCodecSettings(
                honorXmlName: false,
                capitalizeResolvedWireNames: true
            )
        );
        $serializer->beginRootStruct(
            FormUrlEncodedSampleStructFixture::schema()
        );

        (new FormUrlEncodedSampleStructFixture(
            name: 'alpha',
            renamed: 'beta',
            notes: 'memo'
        ))->serializeMembers($serializer);

        self::assertSame(
            'Name=alpha&Renamed=beta&Notes=memo',
            $serializer->flush()
        );
    }

    public function testCanIgnoreTimestampFormatTraits(): void
    {
        $serializer = new FormUrlEncodedSerializer(
            new FormUrlEncodedCodecSettings(
                defaultTimestampFormat: TimestampFormat::DateTime,
                honorTimestampFormatTrait: false
            )
        );
        $serializer->beginRootStruct(
            FormUrlEncodedSampleStructFixture::schema()
        );

        (new FormUrlEncodedSampleStructFixture(created: 0.0))
            ->serializeMembers($serializer);

        self::assertSame(
            'created=' . \rawurlencode(
                TimestampFormat::DateTime->format(0.0)
            ),
            $serializer->flush()
        );
    }

    public function testAwsQueryEmptyListEmitsBareContainer(): void
    {
        $serializer = new FormUrlEncodedSerializer();
        $serializer->beginRootStruct(
            FormUrlEncodedSampleStructFixture::schema()
        );

        (new FormUrlEncodedSampleStructFixture(tags: []))
            ->serializeMembers($serializer);

        self::assertSame('tags=', $serializer->flush());
    }

    public function testEc2QueryRejectsMaps(): void
    {
        $serializer = new FormUrlEncodedSerializer(
            new FormUrlEncodedCodecSettings(
                capitalizeResolvedWireNames: true,
                alwaysFlattenLists: true,
                supportsMaps: false
            )
        );
        $member = FormUrlEncodedSampleStructFixture::schema()
            ->getMembersByIndex()[13];

        $this->expectException(SerializationException::class);
        $this->expectExceptionMessage(
            'This form-urlencoded mode does not support map '
            . 'serialization'
        );

        $serializer->serializeMap($member, ['a' => 1], 13);
    }

    /**
     * @param list<mixed> $values
     * @param list<string> $encodedValues
     * @param array<int, mixed> $traits
     */
    #[DataProvider('scalarCollectionCases')]
    public function testSerializeScalarCollections(
        ShapeType $type,
        array $values,
        array $encodedValues,
        array $traits = []
    ): void
    {
        $listSchema = new Schema(
            id: 'smithy.test#ValuesList',
            type: ShapeType::List_,
            memberName: 'values',
            memberIndex: 0,
            listMember: new Schema(
                id: 'smithy.test#ValuesList$member',
                type: $type,
                memberName: 'member',
                memberIndex: 0,
                traits: $traits
            )
        );
        $mapSchema = new Schema(
            id: 'smithy.test#ValuesMap',
            type: ShapeType::Map,
            memberName: 'values',
            memberIndex: 0,
            mapKey: new Schema(
                id: 'smithy.test#ValuesMap$key',
                type: ShapeType::String_,
                memberName: 'key',
                memberIndex: 0
            ),
            mapValue: new Schema(
                id: 'smithy.test#ValuesMap$value',
                type: $type,
                memberName: 'value',
                memberIndex: 1,
                traits: $traits
            )
        );

        $serializer = new FormUrlEncodedSerializer();
        $serializer->beginRootStruct(new Schema(
            id: 'smithy.test#ListInput',
            type: ShapeType::Structure,
            members: [$listSchema]
        ));
        $serializer->serializeList($listSchema, $values, 0);
        self::assertSame(
            'values.member.1=' . $encodedValues[0]
                . '&values.member.3=' . $encodedValues[1],
            $serializer->flush()
        );

        $serializer->beginRootStruct(new Schema(
            id: 'smithy.test#MapInput',
            type: ShapeType::Structure,
            members: [$mapSchema]
        ));
        $serializer->serializeMap(
            $mapSchema,
            [
                'first' => $values[0],
                'empty' => null,
                'last' => $values[2]
            ],
            0
        );
        self::assertSame(
            'values.entry.1.key=first&values.entry.1.value='
                . $encodedValues[0]
                . '&values.entry.2.key=empty&values.entry.3.key=last'
                . '&values.entry.3.value=' . $encodedValues[1],
            $serializer->flush()
        );
    }

    /**
     * Provide scalar collection serialization cases.
     *
     * @return iterable<
     *     string,
     *     array{ShapeType, list<mixed>, list<string>, array<int, mixed>}
     * >
     */
    public static function scalarCollectionCases(): iterable
    {
        yield 'string' => [
            ShapeType::String_,
            ['a b', null, 'c/d'],
            ['a%20b', 'c%2Fd'],
            []
        ];
        yield 'enum' => [
            ShapeType::Enum,
            ['open', null, 'closed'],
            ['open', 'closed'],
            []
        ];
        yield 'integer' => [
            ShapeType::Integer,
            [1, null, -2],
            ['1', '-2'],
            []
        ];
        yield 'int enum' => [
            ShapeType::IntEnum,
            [7, null, 9],
            ['7', '9'],
            []
        ];
        yield 'boolean' => [
            ShapeType::Boolean,
            [true, null, false],
            ['true', 'false'],
            []
        ];
        yield 'float' => [
            ShapeType::Double,
            [INF, null, NAN],
            ['Infinity', 'NaN'],
            []
        ];
        yield 'timestamp' => [
            ShapeType::Timestamp,
            [0.0, null, 86400.0],
            [
                'Thu%2C%2001%20Jan%201970%2000%3A00%3A00%20GMT',
                'Fri%2C%2002%20Jan%201970%2000%3A00%3A00%20GMT'
            ],
            [Schema::T_TIMESTAMP_FORMAT => 'http-date']
        ];
        yield 'blob' => [
            ShapeType::Blob,
            ["\x01\x02", null, "\xFF"],
            ['AQI%3D', '%2Fw%3D%3D'],
            []
        ];
        yield 'big integer' => [
            ShapeType::BigInteger,
            [
                new BigInteger('18446744073709551616'),
                null,
                new BigInteger('-18446744073709551617')
            ],
            ['18446744073709551616', '-18446744073709551617'],
            []
        ];
        yield 'big decimal' => [
            ShapeType::BigDecimal,
            [
                new BigDecimal('123.45'),
                null,
                new BigDecimal('-0.125')
            ],
            ['123.45', '-0.125'],
            []
        ];
    }

    public function testSerializeUnionSkipsUnknownTags(): void
    {
        $serializer = new FormUrlEncodedSerializer();
        $serializer->beginRootStruct(
            FormUrlEncodedSampleStructFixture::schema()
        );

        $serializer->serializeUnion(
            FormUrlEncodedSampleStructFixture::schema()
                ->getMembersByIndex()[16],
            FormUrlEncodedSampleUnionFixture::unknown('mystery', 'value'),
            16
        );

        self::assertSame('', $serializer->flush());
    }

    public function testSerializeDocumentRejectsValues(): void
    {
        $serializer = new FormUrlEncodedSerializer();

        $this->expectException(SerializationException::class);
        $this->expectExceptionMessage(
            'This form-urlencoded mode does not support document types'
        );

        $serializer->serializeDocument(
            new Schema(
                id: 'smithy.test#Sample$payload',
                type: ShapeType::Document,
                memberName: 'payload',
                memberIndex: 0
            ),
            AbstractDocument::fromString('hi'),
            0
        );
    }
}
