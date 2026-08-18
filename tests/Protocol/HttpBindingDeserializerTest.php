<?php

declare(strict_types=1);

namespace Smithy\Tests\Protocol;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\TimestampFormat;
use Smithy\Json\JsonCodec;
use Smithy\Json\JsonCodecSettings;
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

        self::assertInstanceOf(HttpBindingPayloadShapeFixture::class, $members[0]);
        self::assertSame('nested', $members[0]->value);
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
