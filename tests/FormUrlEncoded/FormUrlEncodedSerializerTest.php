<?php

declare(strict_types=1);

namespace Smithy\Tests\FormUrlEncoded;

use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\{
    Schema,
    ShapeType
};
use Smithy\Core\Shape\{
    BigDecimal,
    BigInteger,
    Document\AbstractDocument
};
use Smithy\FormUrlEncoded\{
    FormUrlEncodedCodecSettings,
    FormUrlEncodedSerializer
};
use Smithy\Tests\FormUrlEncoded\Utils\{
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

    public function testSerializeStructHandlesListsMapsNestedStructsAndUnion(): void
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

    public function testEc2QueryUsesEc2NamingAndFlattenedLists(): void
    {
        $serializer = new FormUrlEncodedSerializer(
            new FormUrlEncodedCodecSettings(
                useAlternateWireNames: true,
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
                useAlternateWireNames: true,
                alwaysFlattenLists: true,
                supportsMaps: false
            )
        );
        $member = FormUrlEncodedSampleStructFixture::schema()
            ->getMembersByIndex()[13];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'This form-urlencoded mode does not support map '
            . 'serialization'
        );

        $serializer->serializeMap($member, ['a' => 1], 13);
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

        $this->expectException(\RuntimeException::class);
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
