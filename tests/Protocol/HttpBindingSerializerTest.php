<?php

declare(strict_types=1);

namespace Smithy\Tests\Protocol;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\TimestampFormat;
use Smithy\Codec\Json\JsonCodec;
use Smithy\Codec\Json\JsonCodecSettings;
use Smithy\Protocol\HttpBindingSerializer;
use Smithy\Protocol\HttpStructBindingExtensionProvider;
use Smithy\Tests\Protocol\Utils\HttpBindingFixtureSchemas;
use Smithy\Tests\Protocol\Utils\HttpBindingInputShapeFixture;
use Smithy\Tests\Protocol\Utils\HttpBindingPayloadShapeFixture;

#[CoversClass(HttpBindingSerializer::class)]
final class HttpBindingSerializerTest extends TestCase
{
    public function testSerializesMembersIntoHttpBindingLocations(): void
    {
        $schema = HttpBindingFixtureSchemas::request();
        $members = $schema->getMembersByIndex();
        $bindingInfo = $schema->getExtension(
            HttpStructBindingExtensionProvider::key()
        );
        $serializer = new HttpBindingSerializer(new JsonCodec(), $bindingInfo);

        $serializer->serializeString($members[0], 'plain text', 0);
        $serializer->serializeBoolean($members[1], true, 1);
        $serializer->serializeString($members[2], 'a value', 2);
        $serializer->serializeMap($members[3], ['Trace' => 'abc'], 3);
        $serializer->serializeMap(
            $members[4],
            ['mode' => 'ignored', 'extra key' => 'a&b'],
            4
        );
        $serializer->serializeString($members[5], 'body text', 5);

        self::assertSame(
            '/things/a%20value',
            $serializer->serializePath(
                [['/things/', null, false], [null, 2, false]],
                '/things/{id}'
            )
        );
        self::assertSame(
            'mode=true&extra%20key=a%26b&fixed=yes%20value',
            $serializer->serializeQueryString('fixed=yes%20value')
        );
        self::assertSame(
            [
                'x-test' => ['x-test', ['cGxhaW4gdGV4dA==']],
                'x-meta-Trace' => ['x-meta-Trace', ['abc']]
            ],
            $serializer->serializeHeaders()
        );
        self::assertSame('{"body":"body text"}', $serializer->serializeBody());
    }

    public function testSerializesGeneratedInputShape(): void
    {
        $schema = HttpBindingFixtureSchemas::request();
        $bindingInfo = $schema->getExtension(
            HttpStructBindingExtensionProvider::key()
        );
        $serializer = new HttpBindingSerializer(new JsonCodec(), $bindingInfo);
        $input = new HttpBindingInputShapeFixture(
            header: 'plain text',
            mode: true,
            id: 'a value',
            metadata: ['Trace' => 'abc'],
            extra: ['mode' => 'ignored', 'extra key' => 'a&b'],
            body: 'body text'
        );

        $input->serializeMembers($serializer);

        self::assertSame(
            '/things/a%20value',
            $serializer->serializePath(
                [['/things/', null, false], [null, 2, false]],
                '/things/{id}'
            )
        );
        self::assertSame(
            'mode=true&extra%20key=a%26b',
            $serializer->serializeQueryString(null)
        );
        self::assertSame(
            [
                'x-test' => ['x-test', ['cGxhaW4gdGV4dA==']],
                'x-meta-Trace' => ['x-meta-Trace', ['abc']]
            ],
            $serializer->serializeHeaders()
        );
        self::assertSame('{"body":"body text"}', $serializer->serializeBody());
    }

    public function testSerializesGreedyLabelByEncodingEachPathSegment(): void
    {
        $schema = HttpBindingFixtureSchemas::request();
        $members = $schema->getMembersByIndex();
        $bindingInfo = $schema->getExtension(
            HttpStructBindingExtensionProvider::key()
        );
        $serializer = new HttpBindingSerializer(new JsonCodec(), $bindingInfo);

        $serializer->serializeString($members[2], 'a/b c?d', 2);

        self::assertSame(
            '/things/a/b%20c%3Fd',
            $serializer->serializePath(
                [['/things/', null, false], [null, 2, true]],
                '/things/{path+}'
            )
        );
    }

    public function testSerializePathRejectsMissingLabelValue(): void
    {
        $schema = HttpBindingFixtureSchemas::request();
        $members = $schema->getMembersByIndex();
        $bindingInfo = $schema->getExtension(
            HttpStructBindingExtensionProvider::key()
        );
        $serializer = new HttpBindingSerializer(new JsonCodec(), $bindingInfo);

        $serializer->serializeString($members[2], 'present', 2);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Missing HTTP label value for member index 7.'
        );

        $serializer->serializePath(
            [['/things/', null, false], [null, 7, false]],
            '/things/{missing}'
        );
    }

    public function testSerializesStringPayloadWithContentType(): void
    {
        $schema = HttpBindingFixtureSchemas::payloadRequest();
        $bindingInfo = $schema->getExtension(
            HttpStructBindingExtensionProvider::key()
        );
        $serializer = new HttpBindingSerializer(new JsonCodec(), $bindingInfo);

        $serializer->serializeString(
            $schema->getMemberByIndex(0),
            'payload body',
            0
        );

        self::assertSame('payload body', $serializer->serializeBody());
        self::assertSame('text/custom', $serializer->resolveContentType());
    }

    public function testSerializesStructurePayload(): void
    {
        $schema = HttpBindingFixtureSchemas::structurePayloadRequest();
        $bindingInfo = $schema->getExtension(
            HttpStructBindingExtensionProvider::key()
        );
        $serializer = new HttpBindingSerializer(new JsonCodec(), $bindingInfo);
        $payload = new HttpBindingPayloadShapeFixture('nested');

        $serializer->serializeStruct(
            $schema->getMemberByIndex(0),
            $payload,
            0
        );

        self::assertSame('{"value":"nested"}', $serializer->serializeBody());
        self::assertNull($serializer->resolveContentType());
    }

    public function testBodySerializationUsesConfiguredCodecSettings(): void
    {
        $schema = HttpBindingFixtureSchemas::timestampRequest();
        $bindingInfo = $schema->getExtension(
            HttpStructBindingExtensionProvider::key()
        );
        $serializer = new HttpBindingSerializer(
            new JsonCodec(
                new JsonCodecSettings(
                    defaultTimestampFormat: TimestampFormat::DateTime
                )
            ),
            $bindingInfo
        );

        $serializer->serializeTimestamp(
            $schema->getMemberByIndex(0),
            100.0,
            0
        );

        self::assertSame(
            '{"createdAt":"1970-01-01T00:01:40Z"}',
            $serializer->serializeBody()
        );
    }

    public function testSerializesClasslessEnumListsAsRawValues(): void
    {
        $schema = HttpBindingFixtureSchemas::classlessEnumLists();
        $members = $schema->getMembersByIndex();
        $bindingInfo = $schema->getExtension(
            HttpStructBindingExtensionProvider::key()
        );
        $serializer = new HttpBindingSerializer(new JsonCodec(), $bindingInfo);

        $serializer->serializeList($members[0], ['open', 'closed'], 0);
        $serializer->serializeList($members[1], [7, 9], 1);
        $serializer->serializeList($members[2], ['open', 'closed'], 2);
        $serializer->serializeList($members[3], [7, 9], 3);

        self::assertSame(
            [
                'x-enums' => ['x-enums', ['open, closed']],
                'x-int-enums' => ['x-int-enums', ['7, 9']]
            ],
            $serializer->serializeHeaders()
        );
        self::assertSame(
            'enum=open&enum=closed&intEnum=7&intEnum=9',
            $serializer->serializeQueryString(null)
        );
    }

}
