<?php

declare(strict_types=1);

namespace Smithy\Tests\Codec\Cbor;

use PHPUnit\Framework\TestCase;
use Smithy\Codec\Cbor\{
    CborCodec,
    CborCodecSettings,
    CborDeserializer,
    CborSerializer
};
use Smithy\Core\Serde\{
    Schema,
    TimestampFormat
};
use Smithy\Core\Shape\ShapeType;


/**
 * Cover the CBOR codec entrypoints.
 */
final class CborCodecTest extends TestCase
{
    public function testCreateSerializerReturnsFreshInstance(): void
    {
        $codec = new CborCodec();

        $first = $codec->createSerializer();
        self::assertInstanceOf(CborSerializer::class, $first);

        $first->serializeInteger(
            new Schema(id: 'smithy.test#I', type: ShapeType::Integer),
            1,
            0
        );
        self::assertSame('01', \bin2hex($first->flush()));

        $second = $codec->createSerializer();
        self::assertNotSame($first, $second);

        $second->serializeInteger(
            new Schema(id: 'smithy.test#I', type: ShapeType::Integer),
            2,
            0
        );
        self::assertSame('02', \bin2hex($second->flush()));
    }

    public function testAcquireSerializerReusesInstanceAndResetsBuffer(): void
    {
        $codec = new CborCodec();

        $first = $codec->acquireSerializer();
        $first->serializeInteger(
            new Schema(id: 'smithy.test#I', type: ShapeType::Integer),
            1,
            0
        );
        self::assertSame('01', \bin2hex($first->flush()));

        $codec->releaseSerializer($first);

        $second = $codec->acquireSerializer();
        self::assertSame($first, $second);

        $second->serializeInteger(
            new Schema(id: 'smithy.test#I', type: ShapeType::Integer),
            2,
            0
        );
        self::assertSame('02', \bin2hex($second->flush()));
    }

    public function testCodecRoundTripsStructMembers(): void
    {
        $schema = new Schema(
            id: 'smithy.test#RoundTrip',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.test#RoundTrip$name',
                    type: ShapeType::String_,
                    memberName: 'name',
                    memberIndex: 0
                ),
                new Schema(
                    id: 'smithy.test#RoundTrip$count',
                    type: ShapeType::Integer,
                    memberName: 'count',
                    memberIndex: 1
                ),
                new Schema(
                    id: 'smithy.test#RoundTrip$active',
                    type: ShapeType::Boolean,
                    memberName: 'active',
                    memberIndex: 2
                )
            ]
        );

        $members = $schema->getMembersByIndex();
        $codec = new CborCodec();

        $serializer = $codec->createSerializer();
        $serializer->beginStruct($schema, 0);
        $serializer->serializeString($members[0], 'alpha', 0);
        $serializer->serializeInteger($members[1], 42, 1);
        $serializer->serializeBoolean($members[2], true, 2);
        $serializer->endStruct();

        $wire = $serializer->flush();

        $deserializer = $codec->createDeserializer($wire);
        self::assertInstanceOf(CborDeserializer::class, $deserializer);

        $fields = [null, null, null];
        $deserializer->deserialize($schema, $fields);

        self::assertSame('alpha', $fields[0]);
        self::assertSame(42, $fields[1]);
        self::assertTrue($fields[2]);
    }

    public function testCodecSettingsControlDefaultTimestampFormat(): void
    {
        $schema = new Schema(
            id: 'smithy.test#TimestampWrap',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.test#TimestampWrap$v',
                    type: ShapeType::Timestamp,
                    memberName: 'v',
                    memberIndex: 0
                )
            ]
        );
        $member = $schema->getMembersByIndex()[0];
        $codec = new CborCodec(
            new CborCodecSettings(
                defaultTimestampFormat: TimestampFormat::DateTime
            )
        );

        $serializer = $codec->createSerializer();
        $serializer->beginStruct($schema, 0);
        $serializer->serializeTimestamp($member, 100.0, 0);
        $serializer->endStruct();

        $wire = $serializer->flush();
        self::assertSame(
            \bin2hex("\xA1\x61\x76\x74" . '1970-01-01T00:01:40Z'),
            \bin2hex($wire)
        );

        $fields = [null];
        $codec->createDeserializer($wire)->deserialize($schema, $fields);

        self::assertSame(100.0, $fields[0]);
    }
}
