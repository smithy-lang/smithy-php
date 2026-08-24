<?php

declare(strict_types=1);

namespace Smithy\Tests\Codec\Json;

use PHPUnit\Framework\TestCase;
use Smithy\Codec\Json\{
    JsonCodec,
    JsonDeserializer,
    JsonSerializer
};
use Smithy\Http\Message\StringBodyStream;
use Smithy\Tests\Codec\Json\Fixture\SampleStruct;

final class JsonCodecTest extends TestCase
{
    public function testCreateSerializerReturnsIndependentInstances(): void
    {
        $codec = new JsonCodec();

        $first = $codec->createSerializer();
        $second = $codec->createSerializer();

        self::assertInstanceOf(JsonSerializer::class, $first);
        self::assertInstanceOf(JsonSerializer::class, $second);
        self::assertNotSame($first, $second);

        $alpha = new SampleStruct(name: 'alpha');
        $alpha->serialize($first, 0);
        self::assertSame('{"name":"alpha"}', $first->flush());

        $beta = new SampleStruct(name: 'beta');
        $beta->serialize($second, 0);

        self::assertSame('{"name":"beta"}', $second->flush());
    }

    public function testAcquireAndReleaseSerializerReusesInstance(): void
    {
        $codec = new JsonCodec();

        $first = $codec->acquireSerializer();
        self::assertInstanceOf(JsonSerializer::class, $first);

        (new SampleStruct(name: 'alpha'))->serialize($first, 0);
        self::assertSame('{"name":"alpha"}', $first->flush());
        $codec->releaseSerializer($first);

        $second = $codec->acquireSerializer();
        self::assertSame($first, $second);

        (new SampleStruct(name: 'beta'))->serialize($second, 0);
        self::assertSame('{"name":"beta"}', $second->flush());
    }

    public function testCreateDeserializerReturnsJsonDeserializer(): void
    {
        $codec = new JsonCodec();

        self::assertInstanceOf(
            JsonDeserializer::class,
            $codec->createDeserializer(new StringBodyStream('{}'))
        );
    }

    public function testSerializeProducesExpectedJsonShape(): void
    {
        $struct = new SampleStruct(
            name: 'alpha',
            count: 42,
            active: true,
            tags: ['one', 'two'],
            scores: ['a' => 1, 'b' => 2]
        );

        $codec = new JsonCodec();
        $serializer = $codec->createSerializer();
        $struct->serialize($serializer, 0);
        $json = $serializer->flush();

        $decoded = \json_decode($json, true);

        self::assertSame('alpha', $decoded['name']);
        self::assertSame(42, $decoded['Count']);
        self::assertTrue($decoded['active']);
        self::assertSame(['one', 'two'], $decoded['tags']);
        self::assertSame(['a' => 1, 'b' => 2], $decoded['scores']);
    }

    public function testDeserializeProducesEquivalentStruct(): void
    {
        $wire = \json_encode([
            'name' => 'alpha',
            'Count' => 42,
            'active' => true,
            'tags' => ['one', 'two'],
            'scores' => ['a' => 1, 'b' => 2]
        ]);

        $codec = new JsonCodec();
        $deserializer = $codec->createDeserializer(
            new StringBodyStream($wire)
        );
        $fields = \array_fill(0, 5, null);
        $deserializer->deserialize(SampleStruct::schema(), $fields);
        $output = SampleStruct::fromMembers($fields);

        self::assertSame('alpha', $output->name);
        self::assertSame(42, $output->count);
        self::assertTrue($output->active);
        self::assertSame(['one', 'two'], $output->tags);
        self::assertSame(['a' => 1, 'b' => 2], $output->scores);
    }

    public function testRoundTripPreservesAllFields(): void
    {
        $struct = new SampleStruct(
            name: 'alpha',
            count: 42,
            active: false,
            tags: ['x', 'y', 'z'],
            scores: ['one' => 1, 'two' => 2, 'three' => 3]
        );

        $codec = new JsonCodec();
        $serializer = $codec->createSerializer();
        $struct->serialize($serializer, 0);
        $json = $serializer->flush();

        $deserializer = $codec->createDeserializer(
            new StringBodyStream($json)
        );
        $fields = \array_fill(0, 5, null);
        $deserializer->deserialize(SampleStruct::schema(), $fields);
        $roundTripped = SampleStruct::fromMembers($fields);

        self::assertEquals($struct, $roundTripped);
    }

    public function testNullMembersAreOmittedFromOutput(): void
    {
        $struct = new SampleStruct(name: 'just-the-name');

        $codec = new JsonCodec();
        $serializer = $codec->createSerializer();
        $struct->serialize($serializer, 0);
        $json = $serializer->flush();

        $decoded = \json_decode($json, true);
        self::assertSame(['name' => 'just-the-name'], $decoded);
    }

    public function testUnknownWireKeysAreIgnoredOnDeserialize(): void
    {
        $wire = \json_encode([
            'name' => 'alpha',
            'metadata' => ['region' => 'us-west-2']
        ]);

        $codec = new JsonCodec();
        $deserializer = $codec->createDeserializer(
            new StringBodyStream($wire)
        );
        $fields = \array_fill(0, 5, null);
        $deserializer->deserialize(SampleStruct::schema(), $fields);
        $output = SampleStruct::fromMembers($fields);

        self::assertSame('alpha', $output->name);
        self::assertNull($output->count);
    }

    public function testEmptyMapsSerializeAsObjects(): void
    {
        $struct = new SampleStruct(scores: []);
        $codec = new JsonCodec();
        $serializer = $codec->createSerializer();
        $struct->serialize($serializer, 0);
        $json = $serializer->flush();

        self::assertStringContainsString('"scores":{}', $json);
    }
}
