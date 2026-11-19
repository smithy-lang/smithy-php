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
 * The hash identifier is a PHP {@see \hash_algos()} id for every algorithm
 * except CRC64NVME, whose value is computed by {@see Crc64Nvme} because the
 * hash extension does not provide it.
 */
final class ChecksumAlgorithm
{
    public const CRC64NVME_HASH = 'crc64nvme';

    public function __construct(
        public readonly string $name,
        public readonly string $hashAlgorithm,
        public readonly string $header,
    ) {}

    /**
     * Report whether this algorithm is computed by {@see Crc64Nvme} rather
     * than the hash extension.
     */
    public function usesCrc64Nvme(): bool
    {
        return $this->hashAlgorithm === self::CRC64NVME_HASH;
    }

    /**
     * Report whether the runtime can compute this algorithm.
     *
     * CRC64NVME is always available because it is implemented in pure PHP;
     * every other algorithm follows {@see \hash_algos()}.
     */
    public function isAvailable(): bool
    {
        return $this->usesCrc64Nvme()
            || \in_array($this->hashAlgorithm, \hash_algos(), true);
    }
}
