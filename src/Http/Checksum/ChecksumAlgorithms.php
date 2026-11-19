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
 * considered for response validation in fastest-first order. Both are filtered
 * to the hashes the runtime's {@see \hash_algos()} provides, so adding support
 * for a new algorithm on a future PHP build needs only a table entry.
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
        'XXHASH64' => 'xxh64',
        'XXHASH3' => 'xxh3',
        'XXHASH128' => 'xxh128',
        'SHA1' => 'sha1',
        'SHA256' => 'sha256',
        'SHA512' => 'sha512',
    ];

    /**
     * Return the request algorithms the runtime supports, keyed by name.
     *
     * @return array<string, ChecksumAlgorithm>
     */
    public static function request(): array
    {
        return self::available(self::REQUEST);
    }

    /**
     * Return the runtime-supported response algorithms, fastest first.
     *
     * @return array<string, ChecksumAlgorithm>
     */
    public static function response(): array
    {
        return self::available(self::RESPONSE);
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
            if (isset($modeled[$name])) {
                $supported[$name] = $algorithm;
            }
        }

        return $supported;
    }

    /**
     * Look up a runtime-supported algorithm by name, or null when it is
     * unknown or unavailable on this runtime.
     */
    public static function byName(string $name): ?ChecksumAlgorithm
    {
        return self::request()[\strtoupper($name)]
            ?? self::response()[\strtoupper($name)]
            ?? null;
    }

    /**
     * Resolve a requested algorithm, raising a clear error when it is not a
     * supported request algorithm on this runtime.
     *
     * @throws \InvalidArgumentException when the algorithm is unknown or the
     *     runtime cannot compute it
     */
    public static function resolveRequest(string $name): ChecksumAlgorithm
    {
        $algorithm = self::request()[\strtoupper($name)] ?? null;
        if ($algorithm !== null) {
            return $algorithm;
        }

        throw new \InvalidArgumentException(\sprintf(
            "Unsupported flexible checksum algorithm '%s'. Supported "
            . 'algorithms are: %s.',
            $name,
            \implode(', ', self::supportedRequestNames()),
        ));
    }

    /**
     * Return the names of the request algorithms the runtime supports.
     *
     * @return list<string>
     */
    public static function supportedRequestNames(): array
    {
        return \array_keys(self::request());
    }

    /**
     * Return the default request algorithm, CRC32.
     */
    public static function default(): ChecksumAlgorithm
    {
        return self::resolveRequest(self::DEFAULT);
    }

    /**
     * Build algorithms from a table, keeping only those the runtime supports.
     *
     * @param array<string, string> $table name => hash identifier
     *
     * @return array<string, ChecksumAlgorithm>
     */
    private static function available(array $table): array
    {
        $hashAlgorithms = \array_flip(\hash_algos());
        $algorithms = [];
        foreach ($table as $name => $hashAlgorithm) {
            if (isset($hashAlgorithms[$hashAlgorithm])) {
                $algorithms[$name] = new ChecksumAlgorithm(
                    $name,
                    $hashAlgorithm,
                    self::header($name),
                );
            }
        }

        return $algorithms;
    }

    private static function header(string $name): string
    {
        return 'x-amz-checksum-' . \strtolower($name);
    }
}
