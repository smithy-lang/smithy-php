<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Http\Checksum;

/**
 * A modeled response checksum selected for payload validation.
 */
final class ResponseChecksumSelection
{
    public function __construct(
        public readonly string $algorithm,
        public readonly string $hashAlgorithm,
        public readonly string $header,
        public readonly string $encodedChecksum,
        public readonly string $checksum,
    ) {}
}
