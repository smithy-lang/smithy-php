<?php

declare(strict_types=1);

namespace Smithy\Tests\Protocol;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\{
    Schema,
    TimestampFormat
};
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\Document\StructDocument;
use Smithy\Codec\Json\JsonCodec;
use Smithy\Codec\Json\JsonCodecSettings;
use Smithy\Protocol\HttpBindingDeserializer;
use Smithy\Protocol\HttpStructBindingExtensionProvider;
use Smithy\Tests\Protocol\Utils\HttpBindingFixtureSchemas;
use Smithy\Tests\Protocol\Utils\HttpBindingOutputShapeFixture;
use Smithy\Tests\Protocol\Utils\HttpBindingPayloadShapeFixture;
use Smithy\Tests\Protocol\Utils\StringStreamFixture;

#[CoversClass(HttpBindingDeserializer::class)]
final class HttpBindingDeserializerTest extends TestCase
{
    public function testDeserializesHttpHeadersStatusAndBodyMembers(): void
    {
        $schema = HttpBindingFixtureSchemas::response();
        $members = \array_fill(0, $schema->getMemberCount(), null);
        $bindingInfo = $schema->getExtension(
            HttpStructBindingExtensionProvider::key()
        );
        $deserializer = new HttpBindingDeserializer(
            new JsonCodec(),
            $bindingInfo,
            [
                'x-media' => ['X-Media', ['cGxhaW4gdGV4dA==']],
                'x-count' => ['X-Count', ['7']],
                'x-list' => ['X-List', ['"a,b", c']],
                'x-meta-one' => ['X-Meta-One', ['alpha']],
                'x-state' => ['X-State', ['open']],
                'x-priority' => ['X-Priority', ['7']],
                'content-length' => ['Content-Length', ['20']]
            ],
            new StringStreamFixture('{"body":"body text"}'),
            202
        );

        $deserializer->deserialize($schema, $members);

        self::assertSame('plain text', $members[0]);
        self::assertSame(7, $members[1]);
        self::assertSame(['a,b', 'c'], $members[2]);
        self::assertSame(['One' => 'alpha'], $members[3]);
        self::assertSame(202, $members[4]);
        self::assertSame('body text', $members[5]);
        self::assertSame('open', $members[6]?->getValue());
        self::assertSame(7, $members[7]?->getValue());
    }

    public function testDeserializesGeneratedOutputShape(): void
    {
        $schema = HttpBindingFixtureSchemas::response();
        $members = \array_fill(0, $schema->getMemberCount(), null);
        $bindingInfo = $schema->getExtension(
            HttpStructBindingExtensionProvider::key()
        );
        $deserializer = new HttpBindingDeserializer(
            new JsonCodec(),
            $bindingInfo,
            [
                'x-media' => ['X-Media', ['cGxhaW4gdGV4dA==']],
                'x-count' => ['X-Count', ['7']],
                'x-list' => ['X-List', ['"a,b", c']],
                'x-meta-one' => ['X-Meta-One', ['alpha']],
                'x-state' => ['X-State', ['open']],
                'x-priority' => ['X-Priority', ['7']],
                'content-length' => ['Content-Length', ['20']]
            ],
            new StringStreamFixture('{"body":"body text"}'),
            202
        );

        $deserializer->deserialize($schema, $members);
        $output = HttpBindingOutputShapeFixture::fromMembers($members);

        self::assertSame('plain text', $output->media);
        self::assertSame(7, $output->count);
        self::assertSame(['a,b', 'c'], $output->list);
        self::assertSame(['One' => 'alpha'], $output->metadata);
        self::assertSame(202, $output->status);
        self::assertSame('body text', $output->body);
        self::assertSame('open', $output->state?->getValue());
        self::assertSame(7, $output->priority?->getValue());
    }

    public function testDeserializesStringPayloadMember(): void
    {
        $schema = HttpBindingFixtureSchemas::payloadResponse();
        $members = [null];
        $bindingInfo = $schema->getExtension(
            HttpStructBindingExtensionProvider::key()
        );
        $deserializer = new HttpBindingDeserializer(
            new JsonCodec(),
            $bindingInfo,
            ['content-length' => ['Content-Length', ['12']]],
            new StringStreamFixture('payload text')
        );

        $deserializer->deserialize($schema, $members);

        self::assertSame('payload text', $members[0]);
    }

    public function testDeserializesStringPayloadMemberWithUnknownSize(): void
    {
        $schema = HttpBindingFixtureSchemas::payloadResponse();
        $members = [null];
        $bindingInfo = $schema->getExtension(
            HttpStructBindingExtensionProvider::key()
        );
        $deserializer = new HttpBindingDeserializer(
            new JsonCodec(),
            $bindingInfo,
            [],
            new StringStreamFixture('payload text', false)
        );

        $deserializer->deserialize($schema, $members);

        self::assertSame('payload text', $members[0]);
    }

    public function testDeserializesStructurePayloadMember(): void
    {
        $schema = HttpBindingFixtureSchemas::structurePayloadResponse();
        $members = [null];
        $bindingInfo = $schema->getExtension(
            HttpStructBindingExtensionProvider::key()
        );
        $deserializer = new HttpBindingDeserializer(
            new JsonCodec(),
            $bindingInfo,
            ['content-length' => ['Content-Length', ['18']]],
            new StringStreamFixture('{"value":"nested"}')
        );

        $deserializer->deserialize($schema, $members);

        self::assertInstanceOf(
            HttpBindingPayloadShapeFixture::class,
            $members[0]
        );
        self::assertSame('nested', $members[0]->value);
    }

    public function testDeserializesStructurePayloadMemberWithUnknownSize(): void
    {
        $schema = HttpBindingFixtureSchemas::structurePayloadResponse();
        $members = [null];
        $bindingInfo = $schema->getExtension(
            HttpStructBindingExtensionProvider::key()
        );
        $deserializer = new HttpBindingDeserializer(
            new JsonCodec(),
            $bindingInfo,
            [],
            new StringStreamFixture('{"value":"nested"}', false)
        );

        $deserializer->deserialize($schema, $members);

        self::assertInstanceOf(
            HttpBindingPayloadShapeFixture::class,
            $members[0]
        );
        self::assertSame('nested', $members[0]->value);
    }

    public function testContentLengthZeroSkipsStructuredBody(): void
    {
        $schema = HttpBindingFixtureSchemas::structurePayloadResponse();
        $members = [null];
        $bindingInfo = $schema->getExtension(
            HttpStructBindingExtensionProvider::key()
        );
        $deserializer = new HttpBindingDeserializer(
            new JsonCodec(),
            $bindingInfo,
            ['content-length' => ['Content-Length', ['0']]],
            new StringStreamFixture('not-json')
        );

        $deserializer->deserialize($schema, $members);

        self::assertNull($members[0]);
    }

    public function testDeserializesRuntimeStructurePayloadMember(): void
    {
        $payloadSchema = new Schema(
            id: 'smithy.test#RuntimePayload',
            type: ShapeType::Structure,
            memberName: 'payload',
            memberIndex: 0,
            members: [
                new Schema(
                    id: 'smithy.api#String',
                    type: ShapeType::String_,
                    memberName: 'value',
                    memberIndex: 0
                )
            ],
            traits: [Schema::T_HTTP_PAYLOAD => true]
        );
        $schema = new Schema(
            id: 'smithy.test#RuntimeResponse',
            type: ShapeType::Structure,
            members: [$payloadSchema]
        );
        $members = [null];
        $bindingInfo = $schema->getExtension(
            HttpStructBindingExtensionProvider::key()
        );
        $deserializer = new HttpBindingDeserializer(
            new JsonCodec(),
            $bindingInfo,
            ['content-length' => ['Content-Length', ['18']]],
            new StringStreamFixture('{"value":"nested"}')
        );

        $deserializer->deserialize($schema, $members);

        self::assertInstanceOf(StructDocument::class, $members[0]);
        self::assertSame(
            'nested',
            $members[0]->getMemberValue($payloadSchema->getMember('value'))
        );
    }

    public function testDocumentPayloadRetainsModeledSchema(): void
    {
        $payloadSchema = new Schema(
            id: 'smithy.test#RuntimeDocumentPayload',
            type: ShapeType::Document,
            memberName: 'payload',
            memberIndex: 0,
            traits: [Schema::T_HTTP_PAYLOAD => true]
        );
        $schema = new Schema(
            id: 'smithy.test#RuntimeDocumentResponse',
            type: ShapeType::Structure,
            members: [$payloadSchema]
        );
        $members = [null];
        $bindingInfo = $schema->getExtension(
            HttpStructBindingExtensionProvider::key()
        );
        $deserializer = new HttpBindingDeserializer(
            new JsonCodec(),
            $bindingInfo,
            ['content-length' => ['Content-Length', ['13']]],
            new StringStreamFixture('{"value":"x"}')
        );

        $deserializer->deserialize($schema, $members);

        self::assertSame($payloadSchema, $members[0]->schema());
        self::assertSame(
            'smithy.test#RuntimeDocumentPayload',
            $members[0]->discriminator()
        );
    }

    public function testDeserializesBodyMemberWithUnknownSize(): void
    {
        $schema = HttpBindingFixtureSchemas::response();
        $members = \array_fill(0, $schema->getMemberCount(), null);
        $bindingInfo = $schema->getExtension(
            HttpStructBindingExtensionProvider::key()
        );
        $deserializer = new HttpBindingDeserializer(
            new JsonCodec(),
            $bindingInfo,
            [],
            new StringStreamFixture('{"body":"body text"}', false)
        );

        $deserializer->deserialize($schema, $members);

        self::assertSame('body text', $members[5]);
    }

    public function testBodyDeserializationUsesConfiguredCodecSettings(): void
    {
        $schema = HttpBindingFixtureSchemas::timestampResponse();
        $members = [null];
        $bindingInfo = $schema->getExtension(
            HttpStructBindingExtensionProvider::key()
        );
        $deserializer = new HttpBindingDeserializer(
            new JsonCodec(
                new JsonCodecSettings(
                    defaultTimestampFormat: TimestampFormat::DateTime
                )
            ),
            $bindingInfo,
            [],
            new StringStreamFixture('{"createdAt":"1970-01-01T00:01:40Z"}')
        );

        $deserializer->deserialize($schema, $members);

        self::assertSame(100.0, $members[0]);
    }

}
