<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codec\Json;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Codec\Exception\SerializationException;
use Smithy\Core\Shape\Exception\InvalidShapeValueException;
use Smithy\Core\Serde\{
    Schema,
    TimestampFormat
};
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\{
    BigDecimal,
    BigInteger,
    Document\AbstractDocument
};
use Smithy\Codec\Json\{
    JsonCodecSettings,
    JsonSerializer
};
use Smithy\Tests\Codec\Json\Fixture\{
    JsonBigNumberStructFixture,
    JsonRecursiveUnionFixture,
    SampleStruct
};

final class JsonSerializerTest extends TestCase
{
    public function testSerializeTopLevelBigIntegerNormalizesLeadingZeros(): void
    {
        $serializer = new JsonSerializer();
        $serializer->serializeBigInteger(
            new Schema(
                id: 'smithy.test#BigIntegerValue',
                type: ShapeType::BigInteger
            ),
            new BigInteger('0001'),
            0
        );

        self::assertSame('1', $serializer->flush());
    }

    public function testSerializeTopLevelBigIntegerListUsesNumberTokens(): void
    {
        $schema = new Schema(
            id: 'smithy.test#BigIntegerList',
            type: ShapeType::List_,
            listMember: new Schema(
                id: 'smithy.test#BigIntegerList$member',
                type: ShapeType::BigInteger
            )
        );

        $serializer = new JsonSerializer();
        $serializer->serializeList(
            $schema,
            [new BigInteger('0001'), new BigInteger('-0002')],
            0
        );

        self::assertSame('[1,-2]', $serializer->flush());
    }

    public function testSerializeBigIntegerAsString(): void
    {
        $serializer = new JsonSerializer(new JsonCodecSettings(
            useStringForArbitraryPrecision: true
        ));
        $serializer->serializeBigInteger(
            new Schema(
                id: 'smithy.test#BigIntegerValue',
                type: ShapeType::BigInteger
            ),
            new BigInteger('0001'),
            0
        );

        self::assertSame('"1"', $serializer->flush());
    }

    public function testSerializeBigIntegerListAsStrings(): void
    {
        $schema = new Schema(
            id: 'smithy.test#BigIntegerList',
            type: ShapeType::List_,
            listMember: new Schema(
                id: 'smithy.test#BigIntegerList$member',
                type: ShapeType::BigInteger
            )
        );
        $serializer = new JsonSerializer(new JsonCodecSettings(
            useStringForArbitraryPrecision: true
        ));

        $serializer->serializeList(
            $schema,
            [new BigInteger('0001'), new BigInteger('-0002')],
            0
        );

        self::assertSame('["1","-2"]', $serializer->flush());
    }

    public function testSerializeTopLevelBigDecimalMapUsesNumberTokens(): void
    {
        $schema = new Schema(
            id: 'smithy.test#BigDecimalMap',
            type: ShapeType::Map,
            mapKey: new Schema(
                id: 'smithy.test#BigDecimalMap$key',
                type: ShapeType::String_
            ),
            mapValue: new Schema(
                id: 'smithy.test#BigDecimalMap$value',
                type: ShapeType::BigDecimal
            )
        );

        $serializer = new JsonSerializer();
        $serializer->serializeMap(
            $schema,
            [
                'a' => new BigDecimal('1.5'),
                'b' => new BigDecimal('2E3'),
            ],
            0
        );

        self::assertSame('{"a":1.5,"b":2E3}', $serializer->flush());
    }

    public function testNestedBigNumberListsUseInlineSerializationPath(): void
    {
        $inner = new Schema(
            id: 'smithy.test#InnerBigIntegerList',
            type: ShapeType::List_,
            listMember: new Schema(
                id: 'smithy.test#InnerBigIntegerList$member',
                type: ShapeType::BigInteger
            )
        );
        $outer = new Schema(
            id: 'smithy.test#OuterBigIntegerList',
            type: ShapeType::List_,
            listMember: $inner
        );

        $serializer = new JsonSerializer();
        $serializer->serializeList(
            $outer,
            [[new BigInteger('0001'), new BigInteger('002')]],
            0
        );

        self::assertSame('[[1,2]]', $serializer->flush());
    }

    public function testSerializeTimestampPrefersSchemaResolvedFormat(): void
    {
        $member = new Schema(
            id: 'smithy.test#Event$createdAt',
            type: ShapeType::Timestamp,
            memberName: 'createdAt',
            memberIndex: 0,
            traits: [Schema::T_TIMESTAMP_FORMAT => 'http-date']
        );
        $schema = new Schema(
            id: 'smithy.test#Event',
            type: ShapeType::Structure,
            members: [$member]
        );

        $serializer = new JsonSerializer();
        $serializer->beginStruct($schema, 0);
        $serializer->serializeTimestamp(
            $member,
            100.0,
            0
        );
        $serializer->endStruct();

        self::assertSame(
            '{"createdAt":"Thu, 01 Jan 1970 00:01:40 GMT"}',
            $serializer->flush()
        );
    }

    public function testSerializeCanIgnoreJsonName(): void
    {
        $member = new Schema(
            id: 'smithy.test#Event$value',
            type: ShapeType::String_,
            memberName: 'value',
            memberIndex: 0,
            traits: [Schema::T_JSON_NAME => 'renamed']
        );
        $schema = new Schema(
            id: 'smithy.test#Event',
            type: ShapeType::Structure,
            members: [$member]
        );
        $serializer = new JsonSerializer(new JsonCodecSettings(
            honorJsonName: false
        ));

        $serializer->beginStruct($schema, 0);
        $serializer->serializeString($member, 'test', 0);
        $serializer->endStruct();

        self::assertSame('{"value":"test"}', $serializer->flush());
    }

    public function testNestedBigNumberMapsUseInlineSerializationPath(): void
    {
        $inner = new Schema(
            id: 'smithy.test#InnerBigDecimalMap',
            type: ShapeType::Map,
            mapKey: new Schema(
                id: 'smithy.test#InnerBigDecimalMap$key',
                type: ShapeType::String_
            ),
            mapValue: new Schema(
                id: 'smithy.test#InnerBigDecimalMap$value',
                type: ShapeType::BigDecimal
            )
        );
        $outer = new Schema(
            id: 'smithy.test#OuterBigDecimalMap',
            type: ShapeType::Map,
            mapKey: new Schema(
                id: 'smithy.test#OuterBigDecimalMap$key',
                type: ShapeType::String_
            ),
            mapValue: $inner
        );

        $serializer = new JsonSerializer();
        $serializer->serializeMap(
            $outer,
            ['outer' => ['a' => new BigDecimal('1.25')]],
            0
        );

        self::assertSame('{"outer":{"a":1.25}}', $serializer->flush());
    }

    public function testNestedBigDecimalMapsSerializeAsStrings(): void
    {
        $inner = new Schema(
            id: 'smithy.test#InnerBigDecimalMap',
            type: ShapeType::Map,
            mapKey: new Schema(
                id: 'smithy.test#InnerBigDecimalMap$key',
                type: ShapeType::String_
            ),
            mapValue: new Schema(
                id: 'smithy.test#InnerBigDecimalMap$value',
                type: ShapeType::BigDecimal
            )
        );
        $outer = new Schema(
            id: 'smithy.test#OuterBigDecimalMap',
            type: ShapeType::Map,
            mapKey: new Schema(
                id: 'smithy.test#OuterBigDecimalMap$key',
                type: ShapeType::String_
            ),
            mapValue: $inner
        );
        $serializer = new JsonSerializer(new JsonCodecSettings(
            useStringForArbitraryPrecision: true
        ));

        $serializer->serializeMap(
            $outer,
            ['outer' => ['a' => new BigDecimal('1.25')]],
            0
        );

        self::assertSame(
            '{"outer":{"a":"1.25"}}',
            $serializer->flush()
        );
    }

    public function testTopLevelUnionWithBigNumberAndNestedStructUnwrapsPlaceholders(): void
    {
        $union = new Schema(
            id: 'smithy.test#BigNumberUnion',
            type: ShapeType::Union,
            members: [
                new Schema(
                    id: 'smithy.test#BigNumberUnion$outer',
                    type: ShapeType::Structure,
                    targetClass: JsonBigNumberStructFixture::class,
                    memberName: 'outer',
                    memberIndex: 0
                )
            ]
        );
        $value = new class (
            new JsonBigNumberStructFixture('2.5', new SampleStruct(name: 'x'))
        ) implements \Smithy\Core\Shape\UnionInterface {
            public function __construct(private readonly object $v) {}
            public function getSchema(): Schema { return JsonBigNumberStructFixture::schema(); }
            public function serialize(\Smithy\Core\Serde\ShapeSerializerInterface $s, int $idx): void {}
            public function getMemberIndex(): int { return 0; }
            public function tag(): string { return 'outer'; }
            public function value(): mixed { return $this->v; }
        };

        $serializer = new JsonSerializer();
        $serializer->serializeUnion($union, $value, 0);

        self::assertSame(
            '{"outer":{"big":2.5,"inner":{"name":"x"}}}',
            $serializer->flush()
        );
    }

    public function testTopLevelListOfStructsWithBigNumberAndNestedStructUnwrapsPlaceholders(): void
    {
        $list = new Schema(
            id: 'smithy.test#BigNumberStructList',
            type: ShapeType::List_,
            listMember: JsonBigNumberStructFixture::schema()
        );

        $serializer = new JsonSerializer();
        $serializer->serializeList(
            $list,
            [new JsonBigNumberStructFixture('2.5', new SampleStruct(name: 'x'))],
            0
        );

        self::assertSame(
            '[{"big":2.5,"inner":{"name":"x"}}]',
            $serializer->flush()
        );
    }

    public function testTopLevelStructWithBigNumberAndNestedStructUnwrapsPlaceholders(): void
    {
        $serializer = new JsonSerializer();
        (new JsonBigNumberStructFixture('2.5', new SampleStruct(name: 'x')))
            ->serialize($serializer, 0);

        self::assertSame('{"big":2.5,"inner":{"name":"x"}}', $serializer->flush());
    }

    public function testSerializesRecursiveUnionUsingCanonicalTargetSchema(): void
    {
        $unionReference = new Schema(
            id: JsonRecursiveUnionFixture::SHAPE_ID,
            type: ShapeType::Union,
            targetClass: JsonRecursiveUnionFixture::class,
            members: [
                new Schema(
                    id: 'smithy.test#JsonRecursiveUnionMap',
                    type: ShapeType::Map,
                    memberName: 'M',
                    memberIndex: 0
                ),
                new Schema(
                    id: 'smithy.test#JsonRecursiveUnionList',
                    type: ShapeType::List_,
                    memberName: 'L',
                    memberIndex: 1
                ),
                new Schema(
                    id: 'smithy.api#Blob',
                    type: ShapeType::Blob,
                    memberName: 'B',
                    memberIndex: 2
                )
            ]
        );
        $mapSchema = new Schema(
            id: 'smithy.test#RecursiveUnionMap',
            type: ShapeType::Map,
            mapKey: new Schema(
                id: 'smithy.api#String',
                type: ShapeType::String_
            ),
            mapValue: $unionReference
        );
        $value = new JsonRecursiveUnionFixture('M', [
            'nested' => new JsonRecursiveUnionFixture('L', [
                new JsonRecursiveUnionFixture('B', 'payload')
            ])
        ]);
        $serializer = new JsonSerializer();

        $serializer->serializeMap($mapSchema, ['root' => $value], 0);

        self::assertSame(
            '{"root":{"M":{"nested":{"L":[{"B":"cGF5bG9hZA=="}]}}}}',
            $serializer->flush()
        );
    }

    public function testSerializesNestedDocumentValuesAndNulls(): void
    {
        $document = AbstractDocument::fromValue([
            'foo' => 'bar',
            'items' => [true, null, 1],
            'nested' => ['value' => null]
        ]);
        self::assertNotNull($document);

        $serializer = new JsonSerializer();
        $document->serialize($serializer, 0);

        self::assertSame(
            '{"foo":"bar","items":[true,null,1],"nested":{"value":null}}',
            $serializer->flush()
        );
    }

    public function testTopLevelDocumentListWithBigIntegersUsesNumberTokens(): void
    {
        $schema = new Schema(
            id: 'smithy.test#DocumentList',
            type: ShapeType::List_,
            listMember: new Schema(
                id: 'smithy.test#DocumentList$member',
                type: ShapeType::Document
            )
        );

        $serializer = new JsonSerializer();
        $serializer->serializeList(
            $schema,
            [AbstractDocument::fromValue(new BigInteger('0001'))],
            0
        );

        self::assertSame('[1]', $serializer->flush());
    }

    public function testDocumentBigIntegersSerializeAsStrings(): void
    {
        $schema = new Schema(
            id: 'smithy.test#DocumentList',
            type: ShapeType::List_,
            listMember: new Schema(
                id: 'smithy.test#DocumentList$member',
                type: ShapeType::Document
            )
        );
        $serializer = new JsonSerializer(new JsonCodecSettings(
            useStringForArbitraryPrecision: true
        ));

        $serializer->serializeList(
            $schema,
            [AbstractDocument::fromValue(new BigInteger('0001'))],
            0
        );

        self::assertSame('["1"]', $serializer->flush());
    }

    public function testSerializeTimestampUsesCodecSettingsDefault(): void
    {
        $member = new Schema(
            id: 'smithy.test#Event$createdAt',
            type: ShapeType::Timestamp,
            memberName: 'createdAt',
            memberIndex: 0
        );
        $schema = new Schema(
            id: 'smithy.test#Event',
            type: ShapeType::Structure,
            members: [$member]
        );

        $serializer = new JsonSerializer(
            new JsonCodecSettings(
                defaultTimestampFormat: TimestampFormat::DateTime
            )
        );
        $serializer->beginStruct($schema, 0);
        $serializer->serializeTimestamp($member, 100.0, 0);
        $serializer->endStruct();

        self::assertSame(
            '{"createdAt":"1970-01-01T00:01:40Z"}',
            $serializer->flush()
        );
    }

    public function testSerializeTimestampCanIgnoreTimestampFormatTrait(): void
    {
        $member = new Schema(
            id: 'smithy.test#Event$createdAt',
            type: ShapeType::Timestamp,
            memberName: 'createdAt',
            memberIndex: 0,
            traits: [Schema::T_TIMESTAMP_FORMAT => 'date-time']
        );
        $schema = new Schema(
            id: 'smithy.test#Event',
            type: ShapeType::Structure,
            members: [$member]
        );

        $serializer = new JsonSerializer(
            new JsonCodecSettings(
                defaultTimestampFormat: TimestampFormat::EpochSeconds,
                honorTimestampFormatTrait: false
            )
        );
        $serializer->beginStruct($schema, 0);
        $serializer->serializeTimestamp($member, 100.0, 0);
        $serializer->endStruct();

        self::assertSame('{"createdAt":100}', $serializer->flush());
    }

    public function testStringMatchingLivePlaceholderIsRejected(): void
    {
        $big = new Schema(
            id: 'smithy.test#Event$big',
            type: ShapeType::BigInteger,
            memberName: 'big',
            memberIndex: 0
        );
        $name = new Schema(
            id: 'smithy.test#Event$name',
            type: ShapeType::String_,
            memberName: 'name',
            memberIndex: 1
        );
        $schema = new Schema(
            id: 'smithy.test#Event',
            type: ShapeType::Structure,
            members: [$big, $name]
        );
        $serializer = new JsonSerializer();
        $prefix = $this->livePrefix($serializer, $big);

        $serializer->beginStruct($schema, 0);
        $serializer->serializeBigInteger($big, new BigInteger('7'), 0);
        $serializer->serializeString($name, $prefix . '42', 1);
        $serializer->endStruct();

        $this->expectException(SerializationException::class);
        $serializer->flush();
    }

    public function testMapKeyMatchingLivePlaceholderIsRejected(): void
    {
        $big = new Schema(
            id: 'smithy.test#Event$big',
            type: ShapeType::BigInteger,
            memberName: 'big',
            memberIndex: 0
        );
        $map = new Schema(
            id: 'smithy.test#Event$m',
            type: ShapeType::Map,
            memberName: 'm',
            memberIndex: 1,
            mapKey: new Schema(id: 'smithy.api#String', type: ShapeType::String_),
            mapValue: new Schema(id: 'smithy.api#String', type: ShapeType::String_)
        );
        $schema = new Schema(
            id: 'smithy.test#Event',
            type: ShapeType::Structure,
            members: [$big, $map]
        );
        $serializer = new JsonSerializer();
        $prefix = $this->livePrefix($serializer, $big);

        $serializer->beginStruct($schema, 0);
        $serializer->serializeBigInteger($big, new BigInteger('7'), 0);
        $serializer->serializeMap($map, [$prefix . '42' => 'v'], 1);
        $serializer->endStruct();

        $this->expectException(SerializationException::class);
        $serializer->flush();
    }

    /**
     * @return array<string, array{0: ShapeType, 1: string}>
     */
    public static function invalidBigNumberProvider(): array
    {
        return [
            'plus sign' => [ShapeType::BigInteger, '+5'],
            'leading space' => [ShapeType::BigInteger, ' 5'],
            'text' => [ShapeType::BigInteger, 'abc'],
            'trailing newline' => [ShapeType::BigInteger, "1\n"],
            'INF' => [ShapeType::BigDecimal, 'INF'],
            'NAN' => [ShapeType::BigDecimal, 'NAN'],
            'trailing dot' => [ShapeType::BigDecimal, '1.'],
            'leading dot' => [ShapeType::BigDecimal, '.5'],
            'decimal trailing newline' => [ShapeType::BigDecimal, "1\n"],
        ];
    }

    #[DataProvider('invalidBigNumberProvider')]
    public function testInvalidRawBigNumberThrowsBeforePlaceholderIsEmitted(
        ShapeType $type,
        string $value
    ): void
    {
        $list = new Schema(
            id: 'smithy.test#Event$bl',
            type: ShapeType::List_,
            memberName: 'bl',
            memberIndex: 0,
            listMember: new Schema(id: 'smithy.test#Num', type: $type)
        );
        $schema = new Schema(
            id: 'smithy.test#Event',
            type: ShapeType::Structure,
            members: [$list]
        );
        $serializer = new JsonSerializer();
        $serializer->beginStruct($schema, 0);

        try {
            $serializer->serializeList($list, [$value], 0);
            self::fail('Expected SerializationException.');
        } catch (SerializationException $e) {
            self::assertStringNotContainsString('__SMITHY_BIGNUM_', $e->getMessage());
        }
    }

    public function testBigDecimalWithTrailingNewlineIsRejectedAtConstruction(): void
    {
        $this->expectException(InvalidShapeValueException::class);
        new BigDecimal("1\n");
    }

    public function testSerializeListWithoutMemberSchemaKeepsNulls(): void
    {
        $serializer = new JsonSerializer();
        $serializer->serializeList(
            new Schema(id: 'smithy.test#Untyped', type: ShapeType::List_),
            ['a', null, 'b'],
            0
        );

        self::assertSame('["a",null,"b"]', $serializer->flush());
    }

    public function testSerializeMapWithoutValueSchemaKeepsNulls(): void
    {
        $serializer = new JsonSerializer();
        $serializer->serializeMap(
            new Schema(id: 'smithy.test#Untyped', type: ShapeType::Map),
            ['a' => 'x', 'b' => null],
            0
        );

        self::assertSame('{"a":"x","b":null}', $serializer->flush());
    }

    /**
     * Write one big number so the per-process prefix exists, then read it.
     */
    private function livePrefix(JsonSerializer $serializer, Schema $big): string
    {
        $serializer->serializeBigInteger($big, new BigInteger('1'), 0);
        $serializer->flush();

        return (new \ReflectionProperty(JsonSerializer::class, 'bigNumberPrefix'))
            ->getValue();
    }
}
