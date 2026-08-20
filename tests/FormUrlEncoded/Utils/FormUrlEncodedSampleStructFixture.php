<?php

declare(strict_types=1);

namespace Smithy\Tests\FormUrlEncoded\Utils;

use Smithy\Core\Serde\{
    Schema,
    SerializableStructInterface,
    ShapeSerializerInterface,
    ShapeType,
    TimestampFormat
};
use Smithy\Core\Shape\{
    BigDecimal,
    BigInteger,
    FromMembersTrait
};

final class FormUrlEncodedSampleStructFixture
    implements SerializableStructInterface
{
    use FromMembersTrait;

    private static ?Schema $SCHEMA = null;

    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $renamed = null,
        public readonly ?string $notes = null,
        public readonly ?float $created = null,
        public readonly ?bool $active = null,
        public readonly ?float $ratio = null,
        public readonly ?string $data = null,
        public readonly ?BigInteger $bigInteger = null,
        public readonly ?BigDecimal $bigDecimal = null,
        public readonly ?FormUrlEncodedStringEnumFixture $status = null,
        public readonly ?FormUrlEncodedIntEnumFixture $code = null,
        /** @var list<string>|null */
        public readonly ?array $tags = null,
        /** @var list<string>|null */
        public readonly ?array $flatTags = null,
        /** @var array<string, int|null>|null */
        public readonly ?array $scores = null,
        /** @var array<string, string>|null */
        public readonly ?array $flatScores = null,
        public readonly ?FormUrlEncodedNestedStructFixture $child = null,
        public readonly ?FormUrlEncodedSampleUnionFixture $choice = null
    ) {}

    public static function schema(): Schema
    {
        if (self::$SCHEMA !== null) {
            return self::$SCHEMA;
        }

        $tagMember = new Schema(
            id: 'smithy.test#FormUrlEncodedSampleStruct$tags$member',
            type: ShapeType::String_,
            memberName: 'member',
            memberIndex: 0
        );
        $flatTagMember = new Schema(
            id: 'smithy.test#FormUrlEncodedSampleStruct$flatTags$member',
            type: ShapeType::String_,
            memberName: 'member',
            memberIndex: 0,
            traits: [Schema::T_XML_NAME => 'item']
        );
        $scoreKey = new Schema(
            id: 'smithy.test#FormUrlEncodedSampleStruct$scores$key',
            type: ShapeType::String_,
            memberName: 'key',
            memberIndex: 0
        );
        $scoreValue = new Schema(
            id: 'smithy.test#FormUrlEncodedSampleStruct$scores$value',
            type: ShapeType::Integer,
            memberName: 'value',
            memberIndex: 1
        );
        $flatScoreKey = new Schema(
            id: 'smithy.test#FormUrlEncodedSampleStruct$flatScores$key',
            type: ShapeType::String_,
            memberName: 'key',
            memberIndex: 0,
            traits: [Schema::T_XML_NAME => 'k']
        );
        $flatScoreValue = new Schema(
            id: 'smithy.test#FormUrlEncodedSampleStruct$flatScores$value',
            type: ShapeType::String_,
            memberName: 'value',
            memberIndex: 1,
            traits: [Schema::T_XML_NAME => 'v']
        );

        self::$SCHEMA = new Schema(
            id: 'smithy.test#FormUrlEncodedSampleStruct',
            type: ShapeType::Structure,
            targetClass: self::class,
            members: [
                new Schema(
                    id: 'smithy.test#FormUrlEncodedSampleStruct$name',
                    type: ShapeType::String_,
                    memberName: 'name',
                    memberIndex: 0
                ),
                new Schema(
                    id: 'smithy.test#FormUrlEncodedSampleStruct$renamed',
                    type: ShapeType::String_,
                    memberName: 'renamed',
                    memberIndex: 1,
                    traits: [
                        Schema::T_XML_NAME => 'wireName',
                        Schema::T_EC2_QUERY_NAME => 'Wire',
                    ]
                ),
                new Schema(
                    id: 'smithy.test#FormUrlEncodedSampleStruct$notes',
                    type: ShapeType::String_,
                    memberName: 'notes',
                    memberIndex: 2,
                    traits: [Schema::T_XML_NAME => 'notesText']
                ),
                new Schema(
                    id: 'smithy.test#FormUrlEncodedSampleStruct$created',
                    type: ShapeType::Timestamp,
                    memberName: 'created',
                    memberIndex: 3,
                    traits: [Schema::T_TIMESTAMP_FORMAT => 'epoch-seconds']
                ),
                new Schema(
                    id: 'smithy.test#FormUrlEncodedSampleStruct$active',
                    type: ShapeType::Boolean,
                    memberName: 'active',
                    memberIndex: 4
                ),
                new Schema(
                    id: 'smithy.test#FormUrlEncodedSampleStruct$ratio',
                    type: ShapeType::Double,
                    memberName: 'ratio',
                    memberIndex: 5
                ),
                new Schema(
                    id: 'smithy.test#FormUrlEncodedSampleStruct$data',
                    type: ShapeType::Blob,
                    memberName: 'data',
                    memberIndex: 6
                ),
                new Schema(
                    id: 'smithy.test#FormUrlEncodedSampleStruct$bigInteger',
                    type: ShapeType::BigInteger,
                    memberName: 'bigInteger',
                    memberIndex: 7
                ),
                new Schema(
                    id: 'smithy.test#FormUrlEncodedSampleStruct$bigDecimal',
                    type: ShapeType::BigDecimal,
                    memberName: 'bigDecimal',
                    memberIndex: 8
                ),
                new Schema(
                    id: 'smithy.test#FormUrlEncodedSampleStruct$status',
                    type: ShapeType::Enum,
                    memberName: 'status',
                    memberIndex: 9,
                    targetClass: FormUrlEncodedStringEnumFixture::class
                ),
                new Schema(
                    id: 'smithy.test#FormUrlEncodedSampleStruct$code',
                    type: ShapeType::IntEnum,
                    memberName: 'code',
                    memberIndex: 10,
                    targetClass: FormUrlEncodedIntEnumFixture::class
                ),
                new Schema(
                    id: 'smithy.test#FormUrlEncodedSampleStruct$tags',
                    type: ShapeType::List_,
                    memberName: 'tags',
                    memberIndex: 11,
                    listMember: $tagMember
                ),
                new Schema(
                    id: 'smithy.test#FormUrlEncodedSampleStruct$flatTags',
                    type: ShapeType::List_,
                    memberName: 'flatTags',
                    memberIndex: 12,
                    listMember: $flatTagMember,
                    traits: [
                        Schema::T_XML_FLATTENED => true,
                        Schema::T_XML_NAME => 'tag',
                    ]
                ),
                new Schema(
                    id: 'smithy.test#FormUrlEncodedSampleStruct$scores',
                    type: ShapeType::Map,
                    memberName: 'scores',
                    memberIndex: 13,
                    mapKey: $scoreKey,
                    mapValue: $scoreValue
                ),
                new Schema(
                    id: 'smithy.test#FormUrlEncodedSampleStruct$flatScores',
                    type: ShapeType::Map,
                    memberName: 'flatScores',
                    memberIndex: 14,
                    mapKey: $flatScoreKey,
                    mapValue: $flatScoreValue,
                    traits: [
                        Schema::T_XML_FLATTENED => true,
                        Schema::T_XML_NAME => 'scoreEntry',
                    ]
                ),
                new Schema(
                    id: 'smithy.test#FormUrlEncodedSampleStruct$child',
                    type: ShapeType::Structure,
                    memberName: 'child',
                    memberIndex: 15,
                    targetClass: FormUrlEncodedNestedStructFixture::class,
                    members: FormUrlEncodedNestedStructFixture::schema()
                        ->getMembersByIndex()
                ),
                new Schema(
                    id: 'smithy.test#FormUrlEncodedSampleStruct$choice',
                    type: ShapeType::Union,
                    memberName: 'choice',
                    memberIndex: 16,
                    targetClass: FormUrlEncodedSampleUnionFixture::class,
                    members: FormUrlEncodedSampleUnionFixture::schema()
                        ->getMembersByIndex()
                )
            ]
        );

        return self::$SCHEMA;
    }

    public function getSchema(): Schema
    {
        return self::schema();
    }

    public function serialize(ShapeSerializerInterface $serializer, int $idx): void
    {
        $serializer->serializeStruct(self::schema(), $this, $idx);
    }

    public function serializeMembers(ShapeSerializerInterface $serializer): void
    {
        $members = self::schema()->getMembersByIndex();

        if ($this->name !== null) {
            $serializer->serializeString($members[0], $this->name, 0);
        }
        if ($this->renamed !== null) {
            $serializer->serializeString($members[1], $this->renamed, 1);
        }
        if ($this->notes !== null) {
            $serializer->serializeString($members[2], $this->notes, 2);
        }
        if ($this->created !== null) {
            $serializer->serializeTimestamp(
                $members[3],
                $this->created,
                3,
                TimestampFormat::HttpDate
            );
        }
        if ($this->active !== null) {
            $serializer->serializeBoolean($members[4], $this->active, 4);
        }
        if ($this->ratio !== null) {
            $serializer->serializeFloat($members[5], $this->ratio, 5);
        }
        if ($this->data !== null) {
            $serializer->serializeBlob($members[6], $this->data, 6);
        }
        if ($this->bigInteger !== null) {
            $serializer->serializeBigInteger(
                $members[7],
                $this->bigInteger,
                7
            );
        }
        if ($this->bigDecimal !== null) {
            $serializer->serializeBigDecimal(
                $members[8],
                $this->bigDecimal,
                8
            );
        }
        if ($this->status !== null) {
            $serializer->serializeString(
                $members[9],
                (string) $this->status->getValue(),
                9
            );
        }
        if ($this->code !== null) {
            $serializer->serializeInteger(
                $members[10],
                (int) $this->code->getValue(),
                10
            );
        }
        if ($this->tags !== null) {
            $serializer->serializeList($members[11], $this->tags, 11);
        }
        if ($this->flatTags !== null) {
            $serializer->serializeList($members[12], $this->flatTags, 12);
        }
        if ($this->scores !== null) {
            $serializer->serializeMap($members[13], $this->scores, 13);
        }
        if ($this->flatScores !== null) {
            $serializer->serializeMap($members[14], $this->flatScores, 14);
        }
        if ($this->child !== null) {
            $serializer->serializeStruct($members[15], $this->child, 15);
        }
        if ($this->choice !== null) {
            $serializer->serializeUnion($members[16], $this->choice, 16);
        }
    }

    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->name,
            1 => $this->renamed,
            2 => $this->notes,
            3 => $this->created,
            4 => $this->active,
            5 => $this->ratio,
            6 => $this->data,
            7 => $this->bigInteger,
            8 => $this->bigDecimal,
            9 => $this->status,
            10 => $this->code,
            11 => $this->tags,
            12 => $this->flatTags,
            13 => $this->scores,
            14 => $this->flatScores,
            15 => $this->child,
            16 => $this->choice,
            default => null,
        };
    }
}
