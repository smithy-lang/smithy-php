<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Codec\Json;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\TimestampFormat;
use Smithy\Core\Shape\Document\IncompatibleDocumentTypeException;
use Smithy\Core\Shape\Document\StringDocument;

/**
 * String document read from JSON.
 *
 * JSON carries blobs as base64 strings and timestamps as strings in the codec's configured
 * format, so this document coerces its string payload to those types on request.
 */
final class JsonStringDocument extends StringDocument
{
    /**
     * @param string $value String payload.
     * @param TimestampFormat $timestampFormat Timestamp format the codec is configured with.
     * @param Schema|null $schema Schema to attach, or null for an untyped document.
     */
    public function __construct(
        string $value,
        private readonly TimestampFormat $timestampFormat,
        ?Schema $schema = null
    )
    {
        parent::__construct($value, $schema);
    }

    /**
     * Base64-decode the string payload.
     *
     * @return string Decoded bytes.
     * @throws IncompatibleDocumentTypeException When the payload is not valid base64.
     */
    public function asBlob(): string
    {
        $bytes = \base64_decode($this->value, true);
        if ($bytes === false) {
            throw new IncompatibleDocumentTypeException(
                'Cannot decode a JSON string document as base64 blob data'
            );
        }

        return $bytes;
    }

    /**
     * Parse the string payload with the codec's string-based timestamp format.
     *
     * @return float Epoch seconds.
     * @throws IncompatibleDocumentTypeException When the codec is configured with epoch seconds
     *         or the payload is not a valid timestamp.
     */
    public function asTimestamp(): float
    {
        if ($this->timestampFormat === TimestampFormat::EpochSeconds) {
            throw new IncompatibleDocumentTypeException(
                'Cannot parse a JSON string document as a timestamp when the codec uses '
                    . 'epoch-seconds'
            );
        }

        try {
            return $this->timestampFormat->parse($this->value);
        } catch (\InvalidArgumentException $e) {
            throw new IncompatibleDocumentTypeException(
                $e->getMessage(),
                0,
                $e
            );
        }
    }
}
