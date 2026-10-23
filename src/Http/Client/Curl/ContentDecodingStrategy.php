<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Http\Client\Curl;

/**
 * Select how an HTTP response content coding is handled.
 *
 * @internal
 */
enum ContentDecodingStrategy
{
    public const MIN_BOUNDED_CURL_VERSION = 0x081400;

    case Disabled;
    case Curl;
    case IdentityOnly;

    /**
     * Select content decoding for the configured limits and cURL runtime.
     */
    public static function select(
        bool $decodeContent,
        bool $bounded,
        int $curlVersionNumber
    ): self
    {
        if (!$decodeContent) {
            return self::Disabled;
        }

        if (!$bounded
            || $curlVersionNumber >= self::MIN_BOUNDED_CURL_VERSION
        ) {
            return self::Curl;
        }

        return self::IdentityOnly;
    }

    /**
     * Determine whether encoded representation headers are removed.
     */
    public function decodesContent(): bool
    {
        return $this === self::Curl;
    }
}
