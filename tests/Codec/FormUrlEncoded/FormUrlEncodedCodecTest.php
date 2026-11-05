<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codec\FormUrlEncoded;

use PHPUnit\Framework\TestCase;
use Smithy\Codec\Exception\CodecException;
use Smithy\Codec\FormUrlEncoded\{
    FormUrlEncodedCodec,
    FormUrlEncodedSerializer
};
use Smithy\Http\Message\StringRequestStream;
use Smithy\Tests\Codec\FormUrlEncoded\Utils\FormUrlEncodedSampleStructFixture;

final class FormUrlEncodedCodecTest extends TestCase
{
    public function testCreateSerializerReturnsIndependentInstances(): void
    {
        $codec = new FormUrlEncodedCodec();

        $first = $codec->createSerializer();
        self::assertInstanceOf(FormUrlEncodedSerializer::class, $first);

        $first->beginRootStruct(FormUrlEncodedSampleStructFixture::schema());
        (new FormUrlEncodedSampleStructFixture(name: 'alpha'))
            ->serializeMembers($first);
        self::assertSame('name=alpha', $first->flush());

        $second = $codec->createSerializer();
        self::assertInstanceOf(FormUrlEncodedSerializer::class, $second);
        self::assertNotSame($first, $second);

        $second->beginRootStruct(FormUrlEncodedSampleStructFixture::schema());
        (new FormUrlEncodedSampleStructFixture(name: 'beta'))
            ->serializeMembers($second);
        self::assertSame('name=beta', $second->flush());
    }

    public function testAcquireAndReleaseSerializerReusesInstance(): void
    {
        $codec = new FormUrlEncodedCodec();

        $first = $codec->acquireSerializer();
        self::assertInstanceOf(FormUrlEncodedSerializer::class, $first);

        $first->beginRootStruct(FormUrlEncodedSampleStructFixture::schema());
        (new FormUrlEncodedSampleStructFixture(name: 'alpha'))
            ->serializeMembers($first);
        self::assertSame('name=alpha', $first->flush());
        $codec->releaseSerializer($first);

        $second = $codec->acquireSerializer();
        self::assertSame($first, $second);

        $second->beginRootStruct(FormUrlEncodedSampleStructFixture::schema());
        (new FormUrlEncodedSampleStructFixture(name: 'beta'))
            ->serializeMembers($second);
        self::assertSame('name=beta', $second->flush());
    }

    public function testCreateDeserializerThrows(): void
    {
        $codec = new FormUrlEncodedCodec();

        $this->expectException(CodecException::class);
        $this->expectExceptionMessage(
            'FormUrlEncodedCodec does not support deserialization.'
        );

        $codec->createDeserializer(
            new StringRequestStream('name=value')
        );
    }
}
