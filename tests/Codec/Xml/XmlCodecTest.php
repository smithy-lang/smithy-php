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
use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;
use Smithy\Http\Message\StringRequestStream;
use Smithy\Tests\Codec\Xml\Utils\XmlNestedStructFixture;

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

    #[DataProvider('s3GranteeBodies')]
    public function testDeserializesS3GranteeTypeAttribute(
        string $body,
        ?string $expectedType
    ): void
    {
        $schema = new Schema(
            id: 'com.amazonaws.s3#Grantee',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'com.amazonaws.s3#Grantee$ID',
                    type: ShapeType::String_,
                    memberName: 'ID',
                    memberIndex: 0
                ),
                new Schema(
                    id: 'com.amazonaws.s3#Grantee$Type',
                    type: ShapeType::Enum,
                    memberName: 'Type',
                    memberIndex: 1,
                    traits: [
                        Schema::T_REQUIRED => true,
                        Schema::T_XML_ATTRIBUTE => true,
                        Schema::T_XML_NAME => 'xsi:type'
                    ]
                )
            ]
        );
        $codec = new XmlCodec();
        $members = $codec->createMembersBuffer($schema);
        $codec->createDeserializer(
            new StringRequestStream($body),
            ['Grantee']
        )->deserialize($schema, $members);

        self::assertSame(['owner-id', $expectedType], $members);
    }

    /**
     * @return iterable<string, array{string, ?string}>
     */
    public static function s3GranteeBodies(): iterable
    {
        $namespace = 'http://www.w3.org/2001/XMLSchema-instance';
        yield 'local declaration' => [
            '<Grantee xmlns:xsi="' . $namespace . '"'
            . ' xsi:type="CanonicalUser"><ID>owner-id</ID></Grantee>',
            'CanonicalUser'
        ];
        yield 'inherited declaration' => [
            '<AccessControlPolicy xmlns:xsi="' . $namespace . '">'
            . '<Grantee xsi:type="CanonicalUser"><ID>owner-id</ID>'
            . '</Grantee></AccessControlPolicy>',
            'CanonicalUser'
        ];
        yield 'qualified and unqualified attributes' => [
            '<Grantee xmlns:xsi="' . $namespace . '"'
            . ' type="Group" xsi:type="CanonicalUser">'
            . '<ID>owner-id</ID></Grantee>',
            'CanonicalUser'
        ];
        yield 'unqualified attribute only' => [
            '<Grantee type="Group"><ID>owner-id</ID></Grantee>',
            null
        ];
        yield 'missing qualified attribute' => [
            '<Grantee xmlns:xsi="' . $namespace . '">'
            . '<ID>owner-id</ID></Grantee>',
            null
        ];
    }
}
