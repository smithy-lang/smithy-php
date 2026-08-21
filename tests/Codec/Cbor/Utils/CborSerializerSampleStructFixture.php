<?php

declare(strict_types=1);

namespace Smithy\Tests\Codec\Cbor\Utils;

use Smithy\Core\Serde\{
    Schema,
    SerializableStructInterface,
    ShapeSerializerInterface
};
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\FromMembersTrait;

/**
 * Provide a structure fixture for CBOR serializer tests.
 */
final class CborSerializerSampleStructFixture
    implements SerializableStructInterface
{
    use FromMembersTrait;

    private static ?Schema $SCHEMA = null;

    /**
     * @param list<?string>|null $tags Tags to serialize.
     * @param array<string, ?int>|null $scores Scores to serialize by key.
     */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?int $count = null,
        public readonly ?bool $active = null,
        /** @var list<?string>|null */
        public readonly ?array $tags = null,
        /** @var array<string, ?int>|null */
        public readonly ?array $scores = null
    ) {}

    /**
     * Return the fixture schema.
     */
    public static function schema(): Schema
    {
        if (self::$SCHEMA !== null) {
            return self::$SCHEMA;
        }

        $listMember = new Schema(
            id: 'smithy.test#Tags$member',
            type: ShapeType::String_,
            memberName: 'member',
            memberIndex: 0
        );
        $mapKey = new Schema(
            id: 'smithy.test#Scores$key',
            type: ShapeType::String_,
            memberName: 'key',
            memberIndex: 0
        );
        $mapValue = new Schema(
            id: 'smithy.test#Scores$value',
            type: ShapeType::Integer,
            memberName: 'value',
            memberIndex: 1
        );

        self::$SCHEMA = new Schema(
            id: 'smithy.test#SampleStruct',
            type: ShapeType::Structure,
            targetClass: self::class,
            members: [
                new Schema(
                    id: 'smithy.test#SampleStruct$name',
                    type: ShapeType::String_,
                    memberName: 'name',
                    memberIndex: 0
                ),
                new Schema(
                    id: 'smithy.test#SampleStruct$count',
                    type: ShapeType::Integer,
                    memberName: 'count',
                    memberIndex: 1
                ),
                new Schema(
                    id: 'smithy.test#SampleStruct$active',
                    type: ShapeType::Boolean,
                    memberName: 'active',
                    memberIndex: 2
                ),
                new Schema(
                    id: 'smithy.test#SampleStruct$tags',
                    type: ShapeType::List_,
                    memberName: 'tags',
                    memberIndex: 3,
                    listMember: $listMember
                ),
                new Schema(
                    id: 'smithy.test#SampleStruct$scores',
                    type: ShapeType::Map,
                    memberName: 'scores',
                    memberIndex: 4,
                    mapKey: $mapKey,
                    mapValue: $mapValue
                )
            ]
        );

        return self::$SCHEMA;
    }

    /**
     * Return the fixture schema.
     */
    public function getSchema(): Schema
    {
        return self::schema();
    }

    /** @inheritDoc */
    public function serialize(ShapeSerializerInterface $serializer, int $idx): void
    {
        $serializer->serializeStruct(self::schema(), $this, $idx);
    }

    /**
     * Serialize the non-null fixture members.
     *
     * @return void
     */
    public function serializeMembers(ShapeSerializerInterface $serializer): void
    {
        $members = self::schema()->getMembersByIndex();
        if ($this->name !== null) {
            $serializer->serializeString($members[0], $this->name, 0);
        }
        if ($this->count !== null) {
            $serializer->serializeInteger($members[1], $this->count, 1);
        }
        if ($this->active !== null) {
            $serializer->serializeBoolean($members[2], $this->active, 2);
        }
        if ($this->tags !== null) {
            $serializer->serializeList(
                $members[3],
                $this->tags,
                3
            );
        }
        if ($this->scores !== null) {
            $serializer->serializeMap($members[4], $this->scores, 4);
        }
    }

    /**
     * Return the modeled value for a member schema.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->name,
            1 => $this->count,
            2 => $this->active,
            3 => $this->tags,
            4 => $this->scores,
            default => null,
        };
    }
}
