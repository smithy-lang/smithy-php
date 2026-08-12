<?php

declare(strict_types=1);

namespace Smithy\Tests\Json;

use PHPUnit\Framework\TestCase;
use Smithy\Json\JsonCodec;
use Smithy\Tests\Json\Fixture\SampleStruct;

/**
 * Exercise end-to-end JSON codec round trips.
 */
final class JsonRoundTripTest extends TestCase
{
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
        // count uses its @jsonName wire key.
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
        $deserializer = $codec->createDeserializer($wire);
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

        $deserializer = $codec->createDeserializer($json);
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
        // Unknown wire keys should be ignored.
        $wire = \json_encode([
            'name' => 'alpha',
            'metadata' => ['region' => 'us-west-2']
        ]);

        $codec = new JsonCodec();
        $deserializer = $codec->createDeserializer($wire);
        $fields = \array_fill(0, 5, null);
        $deserializer->deserialize(SampleStruct::schema(), $fields);
        $output = SampleStruct::fromMembers($fields);

        self::assertSame('alpha', $output->name);
        self::assertNull($output->count);
    }

    public function testEmptyMapsSerializeAsObjects(): void
    {
        // Empty maps must render as `{}`.
        $struct = new SampleStruct(scores: []);
        $codec = new JsonCodec();
        $serializer = $codec->createSerializer();
        $struct->serialize($serializer, 0);
        $json = $serializer->flush();

        self::assertStringContainsString('"scores":{}', $json);
    }
}
