<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Codec\FormUrlEncoded;

use Psr\Http\Message\StreamInterface;
use Smithy\Codec\AbstractCodec;
use Smithy\Codec\Exception\CodecException;
use Smithy\Core\Serde\ShapeDeserializerInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;

/**
 * Encode awsQuery-family request bodies as
 * `application/x-www-form-urlencoded`.
 *
 * Responses are XML and are handled by {@see \Smithy\Codec\Xml\XmlCodec},
 * so this codec never produces a deserializer.
 */
final class FormUrlEncodedCodec extends AbstractCodec
{
    /**
     * Idle codec-owned serializers available for internal reuse.
     *
     * @var list<FormUrlEncodedSerializer>
     */
    private array $serializerPool = [];
    private readonly FormUrlEncodedCodecSettings $settings;

    public function __construct(?FormUrlEncodedCodecSettings $settings = null)
    {
        $this->settings = $settings ?? new FormUrlEncodedCodecSettings();
    }

    /**
     * Create a form-urlencoded serializer.
     */
    public function createSerializer(): ShapeSerializerInterface
    {
        return new FormUrlEncodedSerializer($this->settings);
    }

    /**
     * Reject deserializer creation.
     *
     * @throws CodecException
     */
    public function createDeserializer(
        StreamInterface $source
    ): ShapeDeserializerInterface
    {
        throw new CodecException(
            'FormUrlEncodedCodec does not support deserialization. '
            . 'awsQuery responses are XML. Route them through XmlCodec instead.'
        );
    }

    /**
     * Borrow a reusable serializer.
     */
    public function acquireSerializer(): FormUrlEncodedSerializer
    {
        $serializer = \array_pop($this->serializerPool)
            ?? new FormUrlEncodedSerializer($this->settings);
        $serializer->reset();

        return $serializer;
    }

    /**
     * Return a borrowed serializer to the pool.
     *
     * @return void
     */
    public function releaseSerializer(ShapeSerializerInterface $serializer): void
    {
        if (!$serializer instanceof FormUrlEncodedSerializer) {
            return;
        }

        $serializer->reset();
        $this->serializerPool[] = $serializer;
    }
}
