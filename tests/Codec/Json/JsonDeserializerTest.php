<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codec\Json;

use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\{
    Schema,
    TimestampFormat
};
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\BigInteger;
use Smithy\Core\Shape\Document\{
    StructDocument,
    UnionDocument
};
use Smithy\Codec\Json\{
    JsonCodec,
    JsonCodecSettings
};
use Smithy\Http\Message\StringRequestStream;
use Smithy\Tests\Codec\Json\Fixture\{
    JsonEnumStructFixture,
    JsonIntEnumFixture,
    JsonSampleUnionFixture,
    JsonStringEnumFixture,
    SampleStruct
};

final class JsonDeserializerTest extends TestCase
{
    public function testCodecUsesRawMemberNamesWhenJsonNameIsDisabled(): void
    {
        $struct = new SampleStruct(name: 'alpha', count: 42);

        $codec = new JsonCodec(
            new JsonCodecSettings(honorJsonName: false)
        );
        $serializer = $codec->createSerializer();
        $struct->serialize($serializer, 0);
        $json = $serializer->flush();

        self::assertSame('{"name":"alpha","count":42}', $json);

        $fields = \array_fill(0, 5, null);
        $codec->createDeserializer(new StringRequestStream($json))->deserialize(
            SampleStruct::schema(),
            $fields
        );
        $output = SampleStruct::fromMembers($fields);

        self::assertSame('alpha', $output->name);
        self::assertSame(42, $output->count);
    }

    public function testDeserializerHydratesEnumTargetsForMembersListsAndMaps(): void
    {
        $wire = \json_encode([
            'status' => 'open',
            'priority' => 7,
            'statuses' => ['open', 'closed'],
            'priorities' => ['primary' => 7, 'secondary' => 9]
        ]);

        $codec = new JsonCodec();
        $fields = \array_fill(0, 4, null);
        $codec->createDeserializer(new StringRequestStream($wire))->deserialize(
            JsonEnumStructFixture::schema(),
            $fields
        );
        $output = JsonEnumStructFixture::fromMembers($fields);

        self::assertInstanceOf(JsonStringEnumFixture::class, $output->status);
        self::assertSame('open', $output->status->getValue());
        self::assertInstanceOf(JsonIntEnumFixture::class, $output->priority);
        self::assertSame(7, $output->priority->getValue());
        self::assertCount(2, $output->statuses);
        self::assertInstanceOf(
            JsonStringEnumFixture::class,
            $output->statuses[0]
        );
        self::assertSame('closed', $output->statuses[1]->getValue());
        self::assertInstanceOf(
            JsonIntEnumFixture::class,
            $output->priorities['primary']
        );
        self::assertSame(9, $output->priorities['secondary']->getValue());
    }

    public function testTopLevelUnionDeserializeUsesSyntheticSchemaSlowPath(): void
    {
        $codec = new JsonCodec(
            new JsonCodecSettings(honorJsonName: false)
        );
        $serializer = $codec->createSerializer();
        $serializer->serializeUnion(
            JsonSampleUnionFixture::schema(),
            JsonSampleUnionFixture::create('count', 2),
            0
        );
        $json = $serializer->flush();

        self::assertSame('{"count":2}', $json);

        $members = [null];
        $codec->createDeserializer(new StringRequestStream($json))->deserialize(
            JsonSampleUnionFixture::schema(),
            $members
        );

        self::assertInstanceOf(JsonSampleUnionFixture::class, $members[0]);
        self::assertSame('count', $members[0]->tag());
        self::assertSame(2, $members[0]->value());
    }

    public function testDeserializerPreservesLargeBigIntegerTokens(): void
    {
        $schema = new Schema(
            id: 'smithy.test#BigIntegerContainer',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.test#BigIntegerContainer$value',
                    type: ShapeType::BigInteger,
                    memberName: 'value',
                    memberIndex: 0
                )
            ]
        );

        $fields = [null];
        (new JsonCodec())->createDeserializer(
            new StringRequestStream(
                '{"value":9223372036854775807123}'
            )
        )->deserialize($schema, $fields);

        self::assertInstanceOf(BigInteger::class, $fields[0]);
        self::assertSame('9223372036854775807123', $fields[0]->value);
    }

    public function testDeserializerUsesCodecSettingsDefaultTimestampFormat(): void
    {
        $schema = new Schema(
            id: 'smithy.test#Event',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.test#Event$createdAt',
                    type: ShapeType::Timestamp,
                    memberName: 'createdAt',
                    memberIndex: 0
                )
            ]
        );

        $codec = new JsonCodec(
            new JsonCodecSettings(
                defaultTimestampFormat: TimestampFormat::DateTime
            )
        );
        $fields = [null];
        $codec->createDeserializer(
            new StringRequestStream(
                '{"createdAt":"1970-01-01T00:01:40Z"}'
            )
        )->deserialize($schema, $fields);

        self::assertSame(100.0, $fields[0]);
    }

    public function testDeserializerCanIgnoreTimestampFormatTrait(): void
    {
        $schema = new Schema(
            id: 'smithy.test#Event',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.test#Event$createdAt',
                    type: ShapeType::Timestamp,
                    memberName: 'createdAt',
                    memberIndex: 0,
                    traits: [Schema::T_TIMESTAMP_FORMAT => 'date-time']
                )
            ]
        );

        $codec = new JsonCodec(
            new JsonCodecSettings(
                defaultTimestampFormat: TimestampFormat::EpochSeconds,
                honorTimestampFormatTrait: false
            )
        );
        $fields = [null];
        $codec->createDeserializer(
            new StringRequestStream('{"createdAt":100}')
        )
            ->deserialize($schema, $fields);

        self::assertSame(100.0, $fields[0]);
    }

    public function testDeserializerCreatesDocumentsForRuntimeShapes(): void
    {
        $childSchema = new Schema(
            id: 'smithy.test#Child',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.api#String',
                    type: ShapeType::String_,
                    memberName: 'value',
                    memberIndex: 0
                )
            ]
        );
        $unionSchema = new Schema(
            id: 'smithy.test#Choice',
            type: ShapeType::Union,
            members: [
                new Schema(
                    id: 'smithy.api#Integer',
                    type: ShapeType::Integer,
                    memberName: 'count',
                    memberIndex: 0
                )
            ]
        );
        $schema = new Schema(
            id: 'smithy.test#Container',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: $childSchema->id,
                    type: ShapeType::Structure,
                    memberName: 'child',
                    memberIndex: 0,
                    target: $childSchema
                ),
                new Schema(
                    id: $unionSchema->id,
                    type: ShapeType::Union,
                    memberName: 'choice',
                    memberIndex: 1,
                    target: $unionSchema
                )
            ]
        );
        $members = [null, null];

        (new JsonCodec())->createDeserializer(
            new StringRequestStream(
                '{"child":{"value":"kid"},"choice":{"count":2}}'
            )
        )->deserialize($schema, $members);

        self::assertInstanceOf(StructDocument::class, $members[0]);
        self::assertSame('kid', $members[0]->getMemberValue(
            $childSchema->getMember('value')
        ));
        self::assertInstanceOf(UnionDocument::class, $members[1]);
        self::assertSame('count', $members[1]->tag());
        self::assertSame(2, $members[1]->value());
    }
}
