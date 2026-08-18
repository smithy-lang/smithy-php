<?php

declare(strict_types=1);

namespace Smithy\Tests\FormUrlEncoded\Utils;

use Smithy\Core\Serde\{
    Schema,
    ShapeSerializerInterface,
    ShapeType
};
use Smithy\Core\Shape\UnionInterface;

final class FormUrlEncodedSampleUnionFixture implements UnionInterface
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
            id: 'smithy.test#FormUrlEncodedSampleUnion',
            type: ShapeType::Union,
            targetClass: self::class,
            members: [
                new Schema(
                    id: 'smithy.test#FormUrlEncodedSampleUnion$value',
                    type: ShapeType::String_,
                    memberName: 'value',
                    memberIndex: 0
                ),
                new Schema(
                    id: 'smithy.test#FormUrlEncodedSampleUnion$count',
                    type: ShapeType::Integer,
                    memberName: 'count',
                    memberIndex: 1,
                    traits: [
                        Schema::T_XML_NAME => 'countValue',
                        Schema::T_EC2_QUERY_NAME => 'Counter',
                    ]
                ),
                new Schema(
                    id: 'smithy.test#FormUrlEncodedSampleUnion$created',
                    type: ShapeType::Timestamp,
                    memberName: 'created',
                    memberIndex: 2,
                    traits: [Schema::T_TIMESTAMP_FORMAT => 'epoch-seconds']
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

    public static function create(string $tag, mixed $value): self
    {
        return new self($tag, $value);
    }

    public static function unknown(string $tag, mixed $value): self
    {
        return new self($tag, $value);
    }
}
