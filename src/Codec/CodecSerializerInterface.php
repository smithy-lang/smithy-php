<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Codec;

use Smithy\Core\Serde\ShapeSerializerInterface;

/**
 * Serializer that a codec creates to write one payload in its wire format.
 *
 * Protocols push a shape into it, then call {@see flush()} for the encoded
 * bytes.
 */
interface CodecSerializerInterface extends ShapeSerializerInterface
{
    /**
     * Return the encoded payload and reset the serializer for reuse.
     *
     * @return string Encoded payload in the codec's wire format.
     */
    public function flush(): string;
}
