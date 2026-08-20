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
 * Provide a sparse-aggregate fixture for CBOR deserializer tests.
 */
final class CborDeserializerSparseStructFixture
    implements SerializableStructInterface
{
    use FromMembersTrait;

    private static ?Schema $SCHEMA = null;

    /**
     * Return the fixture schema.
     */
    public static function schema(): Schema
    {
        if (self::$SCHEMA !== null) {
            return self::$SCHEMA;
        }

        $listMember = new Schema(
            id: 'smithy.test#SparseList$member',
            type: ShapeType::String_,
            memberName: 'member',
            memberIndex: 0
        );
        $mapKey = new Schema(
            id: 'smithy.test#SparseMap$key',
            type: ShapeType::String_,
            memberName: 'key',
            memberIndex: 0
        );
        $mapValue = new Schema(
            id: 'smithy.test#SparseMap$value',
            type: ShapeType::String_,
            memberName: 'value',
            memberIndex: 1
        );

        self::$SCHEMA = new Schema(
            id: 'smithy.test#SparseStruct',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.test#SparseStruct$sparseList',
                    type: ShapeType::List_,
                    memberName: 'sparseList',
                    memberIndex: 0,
                    listMember: $listMember,
                    traits: [Schema::T_SPARSE => true]
                ),
                new Schema(
                    id: 'smithy.test#SparseStruct$sparseMap',
                    type: ShapeType::Map,
                    memberName: 'sparseMap',
                    memberIndex: 1,
                    mapKey: $mapKey,
                    mapValue: $mapValue,
                    traits: [Schema::T_SPARSE => true]
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
     * Create an empty sparse fixture.
     *
     * @param array<int, mixed> $fields Positional member values.
     */
    public static function fromMembers(array $fields): static
    {
        return new self();
    }
}
