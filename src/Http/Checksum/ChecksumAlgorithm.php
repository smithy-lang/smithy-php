<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Http\Checksum;

/**
 * A flexible-checksum algorithm's identity: its modeled name, the hash
 * identifier used to compute it, and the header that carries its value.
 *
 * The hash identifier is a PHP {@see \hash_algos()} id, so an algorithm is
 * available only when the runtime's hash extension provides it.
 */
final class ChecksumAlgorithm
{
    public function __construct(
        public readonly string $name,
        public readonly string $hashAlgorithm,
        public readonly string $header,
    ) {}

    /**
     * Report whether the runtime can compute this algorithm.
     */
    public function isAvailable(): bool
    {
        return \in_array($this->hashAlgorithm, \hash_algos(), true);
    }
}
