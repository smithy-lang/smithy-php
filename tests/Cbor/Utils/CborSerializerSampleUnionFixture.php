<?php

declare(strict_types=1);

namespace Smithy\Tests\Cbor\Utils;

use Smithy\Core\Serde\{
    Schema,
    ShapeSerializerInterface,
    ShapeType
};
use Smithy\Core\Shape\UnionInterface;

/**
 * Provide a union fixture for CBOR serializer tests.
 */
final class CborSerializerSampleUnionFixture implements UnionInterface
{
    private static ?Schema $SCHEMA = null;

    public function __construct(
        private readonly string $activeTag,
        private readonly mixed $activeValue
    ) {}

    public static function schema(): Schema
    {
        if (self::$SCHEMA !== null) {
            return self::$SCHEMA;
        }

        self::$SCHEMA = new Schema(
            id: 'smithy.test#SampleUnion',
            type: ShapeType::Union,
            targetClass: self::class,
            members: [
                new Schema(
                    id: 'smithy.test#SampleUnion$value',
                    type: ShapeType::String_,
                    memberName: 'value',
                    memberIndex: 0
                ),
                new Schema(
                    id: 'smithy.test#SampleUnion$count',
                    type: ShapeType::Integer,
                    memberName: 'count',
                    memberIndex: 1
                )
            ]
        );

        return self::$SCHEMA;
    }

    public function serialize(ShapeSerializerInterface $serializer, int $idx): void
    {
        $serializer->serializeUnion(self::schema(), $this, $idx);
    }

    public function tag(): string
    {
        return $this->activeTag;
    }

    public function value(): mixed
    {
        return $this->activeValue;
    }
}
