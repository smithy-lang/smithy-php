<?php

declare(strict_types=1);

namespace Smithy\Tests\Codec\Cbor\Utils;

use Smithy\Core\Serde\{
    Schema,
    SerializableStructInterface,
    ShapeSerializerInterface,
    ShapeType
};
use Smithy\Core\Shape\FromMembersTrait;

/**
 * Provide a structure fixture for CBOR deserializer tests.
 */
final class CborDeserializerSampleStructFixture
    implements SerializableStructInterface
{
    use FromMembersTrait;

    private static ?Schema $SCHEMA = null;

    /**
     * Create a structure fixture.
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

    /** @inheritDoc */
    public function getSchema(): Schema
    {
        return self::schema();
    }

    /** @inheritDoc */
    public function serialize(ShapeSerializerInterface $serializer, int $idx): void
    {
        $serializer->serializeStruct(self::schema(), $this, $idx);
    }

    /** @inheritDoc */
    public function serializeMembers(ShapeSerializerInterface $serializer): void {}

    /** @inheritDoc */
    public function getMemberValue(Schema $member): mixed
    {
        return null;
    }

    /**
     * Create a fixture from positional member values.
     *
     * @param array<int, mixed> $fields Positional member values.
     */
    public static function fromMembers(array $fields): static
    {
        return new self(
            name: $fields[0] ?? null,
            count: $fields[1] ?? null,
            active: $fields[2] ?? null,
            tags: $fields[3] ?? null,
            scores: $fields[4] ?? null
        );
    }
}
