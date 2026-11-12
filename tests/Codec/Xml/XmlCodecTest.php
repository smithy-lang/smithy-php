<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codec\Xml;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Codec\Exception\CodecException;
use Smithy\Codec\Xml\{
    XmlCodec,
    XmlCodecSettings,
    XmlDeserializer,
    XmlSerializer
};
use Smithy\Http\Message\StringRequestStream;
use Smithy\Tests\Codec\Xml\Utils\{
    XmlNestedStructFixture,
    XmlSampleStructFixture
};

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
        $codec = new XmlCodec(
            new XmlCodecSettings(wrapperElements: ['Response'])
        );
        $deserializer = $codec->createDeserializer(
            new StringRequestStream(
                '<Envelope><Response><NestedStruct><value>alpha</value>'
                . '</NestedStruct></Response></Envelope>'
            ),
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

        $this->expectException(CodecException::class);
        $this->expectExceptionMessage('Failed to parse XML:');

        $codec->createDeserializer(new StringRequestStream('<broken'));
    }

    #[DataProvider('namespacePayloads')]
    public function testDeserializesNamespacesAcrossPayloadEncodings(
        string $body
    ): void
    {
        $schema = XmlSampleStructFixture::schema();
        $members = \array_fill(0, $schema->getMemberCount(), null);
        (new XmlCodec())->createDeserializer(new StringRequestStream($body))
            ->deserialize($schema, $members);
        $output = XmlSampleStructFixture::fromMembers($members);

        self::assertSame('abc', $output->id);
        self::assertSame('alpha', $output->name);
        self::assertSame('kid', $output->child->value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function namespacePayloads(): iterable
    {
        yield 'ordinary XML' => [
            '<SampleStruct id="abc"><name>alpha</name>'
            . '<child><value>kid</value></child></SampleStruct>'
        ];
        yield 'prefix declared on descendant' => [
            '<SampleStruct id="abc"><name>alpha</name>'
            . '<child xmlns:a="urn:test"><a:value>kid</a:value></child>'
            . '</SampleStruct>'
        ];
        yield 'implicit xml attribute prefix' => [
            '<SampleStruct xml:id="abc"><name>alpha</name>'
            . '<child><value>kid</value></child></SampleStruct>'
        ];
        yield 'prefix text in CDATA' => [
            '<SampleStruct id="abc"><name>alpha</name>'
            . '<notes><![CDATA[xmlns:a="urn:test" xml:lang]]></notes>'
            . '<child><value>kid</value></child></SampleStruct>'
        ];
        yield 'UTF-8 BOM' => [
            "\xef\xbb\xbf"
            . '<SampleStruct id="abc"><name>alpha</name>'
            . '<child><value>kid</value></child></SampleStruct>'
        ];
        $xml = '<?xml version="1.0" encoding="UTF-16"?>'
            . '<a:SampleStruct xmlns:a="urn:test" id="abc">'
            . '<a:name>alpha</a:name><a:child><a:value>kid</a:value>'
            . '</a:child></a:SampleStruct>';
        yield 'UTF-16 little endian' => [
            "\xff\xfe" . \implode("\0", \str_split($xml)) . "\0"
        ];
        yield 'UTF-16 big endian' => [
            "\xfe\xff\0" . \implode("\0", \str_split($xml))
        ];
    }
}
