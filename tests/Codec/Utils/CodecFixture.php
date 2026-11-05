<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codec\Utils;

use Psr\Http\Message\StreamInterface;
use Smithy\Codec\AbstractCodec;
use Smithy\Core\Serde\{
    ShapeDeserializerInterface,
    ShapeSerializerInterface
};

/**
 * Expose inherited codec behavior for direct testing.
 */
final class CodecFixture extends AbstractCodec
{
    public ?StreamInterface $deserializerSource = null;

    public function __construct(
        private readonly ?ShapeDeserializerInterface $deserializer = null
    ) {}

    /** @inheritDoc */
    public function createSerializer(): ShapeSerializerInterface
    {
        throw new \LogicException('No serializer configured.');
    }

    /** @inheritDoc */
    public function createDeserializer(
        StreamInterface $source
    ): ShapeDeserializerInterface
    {
        $this->deserializerSource = $source;

        return $this->deserializer
            ?? throw new \LogicException('No deserializer configured.');
    }
}
