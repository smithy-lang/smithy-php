<?php

declare(strict_types=1);

namespace Smithy\Tests\Xml;

use PHPUnit\Framework\TestCase;
use Smithy\Xml\{
    XmlCodec,
    XmlDeserializer,
    XmlSerializer
};
use Smithy\Tests\Xml\Utils\XmlNestedStructFixture;

final class XmlCodecTest extends TestCase
{
    public function testCreateSerializerReturnsFreshIndependentInstances(): void
    {
        $codec = new XmlCodec();

        $first = $codec->createSerializer();
        self::assertInstanceOf(XmlSerializer::class, $first);

        $first->serializeStruct(
            XmlNestedStructFixture::schema(),
            new XmlNestedStructFixture('alpha'),
            0
        );

        $second = $codec->createSerializer();
        self::assertInstanceOf(XmlSerializer::class, $second);
        self::assertNotSame($first, $second);

        $second->serializeStruct(
            XmlNestedStructFixture::schema(),
            new XmlNestedStructFixture('beta'),
            0
        );
        self::assertSame(
            '<NestedStruct><value>beta</value></NestedStruct>',
            $second->flush()
        );
        self::assertSame(
            '<NestedStruct><value>alpha</value></NestedStruct>',
            $first->flush()
        );
    }

    public function testSerializerReturnsWriterToCodecPoolOnDestruction(): void
    {
        $codec = new XmlCodec();
        $pool = new \ReflectionProperty(XmlCodec::class, 'writerPool');

        $serializer = $codec->createSerializer();
        self::assertCount(0, $pool->getValue($codec));

        unset($serializer);

        self::assertCount(1, $pool->getValue($codec));

        $serializer = $codec->createSerializer();

        self::assertCount(0, $pool->getValue($codec));
        unset($serializer);
    }

    public function testCreateDeserializerDescendsThroughConfiguredWrappers(): void
    {
        $codec = new XmlCodec(['Response']);
        $deserializer = $codec->createDeserializer(
            '<Envelope><Response><NestedStruct><value>alpha</value>'
            . '</NestedStruct></Response></Envelope>',
            ['NestedStruct']
        );

        self::assertInstanceOf(XmlDeserializer::class, $deserializer);

        $members = [null];
        $deserializer->deserialize(XmlNestedStructFixture::schema(), $members);
        $output = XmlNestedStructFixture::fromMembers($members);

        self::assertSame('alpha', $output->value);
    }

    public function testCreateDeserializerWrapsParseFailures(): void
    {
        $codec = new XmlCodec();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to parse XML:');

        $codec->createDeserializer('<broken');
    }
}
