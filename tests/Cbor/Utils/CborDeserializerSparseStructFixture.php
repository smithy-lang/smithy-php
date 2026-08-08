<?php

declare(strict_types=1);

namespace Smithy\Tests\Cbor\Utils;

use Smithy\Core\Serde\{
    Schema,
    SerializableStructInterface,
    ShapeSerializerInterface,
    ShapeType
};

/**
 * Provide a sparse-aggregate fixture for CBOR deserializer tests.
 */
final class CborDeserializerSparseStructFixture
    implements SerializableStructInterface
{
    private static ?Schema $SCHEMA = null;

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

    public function serialize(ShapeSerializerInterface $serializer, int $idx): void
    {
        $serializer->serializeStruct(self::schema(), $this, $idx);
    }

    public function serializeMembers(ShapeSerializerInterface $serializer): void {}

    public function getMemberValue(Schema $member): mixed
    {
        return null;
    }

    public static function fromMembers(array $fields): static
    {
        return new self();
    }
}
