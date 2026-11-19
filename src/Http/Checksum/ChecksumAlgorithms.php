<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Http\Checksum;

/**
 * The catalogue of flexible-checksum algorithms.
 *
 * {@see request()} lists the algorithms available for request payload
 * checksums with CRC32 as the default. {@see response()} lists the algorithms
 * considered for response validation in fastest-first order. Both include
 * CRC64NVME, which {@see Crc64Nvme} computes in pure PHP.
 */
final class ChecksumAlgorithms
{
    public const DEFAULT = 'CRC32';

    /**
     * Request algorithms keyed by modeled name.
     *
     * @var array<string, string> name => hash identifier
     */
    private const REQUEST = [
        'CRC32' => 'crc32b',
        'CRC32C' => 'crc32c',
        'CRC64NVME' => ChecksumAlgorithm::CRC64NVME_HASH,
        'SHA1' => 'sha1',
        'SHA256' => 'sha256',
        'SHA512' => 'sha512',
        'XXHASH64' => 'xxh64',
        'XXHASH3' => 'xxh3',
        'XXHASH128' => 'xxh128',
    ];

    /**
     * Response algorithms keyed by modeled name, fastest first.
     *
     * @var array<string, string> name => hash identifier
     */
    private const RESPONSE = [
        'CRC32C' => 'crc32c',
        'CRC32' => 'crc32b',
        'CRC64NVME' => ChecksumAlgorithm::CRC64NVME_HASH,
        'XXHASH64' => 'xxh64',
        'XXHASH3' => 'xxh3',
        'XXHASH128' => 'xxh128',
        'SHA1' => 'sha1',
        'SHA256' => 'sha256',
        'SHA512' => 'sha512',
    ];

    /**
     * Return the request algorithms keyed by modeled name.
     *
     * @return array<string, ChecksumAlgorithm>
     */
    public static function request(): array
    {
        return self::build(self::REQUEST);
    }

    /**
     * Return the response algorithms, fastest first, keyed by modeled name.
     *
     * @return array<string, ChecksumAlgorithm>
     */
    public static function response(): array
    {
        return self::build(self::RESPONSE);
    }

    /**
     * Return the response algorithms that are both modeled and available on
     * this runtime, preserving fastest-first order.
     *
     * @param list<string> $modeledNames modeled response algorithm names
     *
     * @return array<string, ChecksumAlgorithm>
     */
    public static function supported(array $modeledNames): array
    {
        $modeled = [];
        foreach ($modeledNames as $name) {
            $modeled[\strtoupper($name)] = true;
        }

        $supported = [];
        foreach (self::response() as $name => $algorithm) {
            if (isset($modeled[$name]) && $algorithm->isAvailable()) {
                $supported[$name] = $algorithm;
            }
        }

        return $supported;
    }

    /**
     * Look up an algorithm by its modeled name, or null when it is unknown.
     */
    public static function byName(string $name): ?ChecksumAlgorithm
    {
        $name = \strtoupper($name);
        $hashAlgorithm = self::REQUEST[$name] ?? self::RESPONSE[$name] ?? null;
        if ($hashAlgorithm === null) {
            return null;
        }

        return new ChecksumAlgorithm(
            $name,
            $hashAlgorithm,
            self::header($name),
        );
    }

    /**
     * Return the default request algorithm, CRC32.
     */
    public static function default(): ChecksumAlgorithm
    {
        $algorithm = self::byName(self::DEFAULT);
        \assert($algorithm !== null);

        return $algorithm;
    }

    /**
     * @param array<string, string> $table name => hash identifier
     *
     * @return array<string, ChecksumAlgorithm>
     */
    private static function build(array $table): array
    {
        $algorithms = [];
        foreach ($table as $name => $hashAlgorithm) {
            $algorithms[$name] = new ChecksumAlgorithm(
                $name,
                $hashAlgorithm,
                self::header($name),
            );
        }

        return $algorithms;
    }

    private static function header(string $name): string
    {
        return 'x-amz-checksum-' . \strtolower($name);
    }
}
