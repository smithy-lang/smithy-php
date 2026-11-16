<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Benchmarks\Serde\Support;

/**
 * Loads fixed protocol benchmark cases from a Smithy JSON AST.
 */
final class BenchmarkCaseRepository
{
    public const PROTOCOL = 'smithy.protocols#rpcv2Cbor';
    public const TAG = 'serde-benchmark';

    /** @var array<string, array<string, mixed>> */
    private array $requests = [];

    /** @var array<string, array<string, mixed>> */
    private array $responses = [];

    public function __construct(
        string $modelPath,
        private readonly string $protocol = self::PROTOCOL
    )
    {
        $json = \file_get_contents($modelPath);
        if ($json === false) {
            throw new \RuntimeException(
                "Unable to read benchmark model: {$modelPath}"
            );
        }

        $model = self::decodeModel($json);
        $this->indexCases($model['shapes'] ?? []);
        if ($this->requests === [] && $this->responses === []) {
            throw new \RuntimeException(
                "Benchmark model has no cases for {$protocol}: {$modelPath}"
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function requests(?string $filter = null): array
    {
        return $this->filtered($this->requests, $filter);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function responses(?string $filter = null): array
    {
        return $this->filtered($this->responses, $filter);
    }

    /**
     * Preserve arbitrary-precision Smithy number values before PHP's JSON
     * decoder converts them to native floats.
     *
     * @return array<string, mixed>
     */
    private static function decodeModel(string $json): array
    {
        $probe = \json_decode(
            $json,
            true,
            512,
            JSON_BIGINT_AS_STRING | JSON_THROW_ON_ERROR
        );
        $bigNumberTargets = [
            'smithy.api#BigInteger' => true,
            'smithy.api#BigDecimal' => true,
        ];
        foreach ($probe['shapes'] ?? [] as $shapeId => $shape) {
            if (\in_array(
                $shape['type'] ?? null,
                ['bigInteger', 'bigDecimal'],
                true
            )) {
                $bigNumberTargets[$shapeId] = true;
            }
        }

        $memberNames = [];
        foreach ($probe['shapes'] ?? [] as $shape) {
            foreach ($shape['members'] ?? [] as $name => $member) {
                if (isset($bigNumberTargets[$member['target'] ?? ''])) {
                    $memberNames[$name] = true;
                }
            }
        }

        foreach (\array_keys($memberNames) as $name) {
            $pattern = '/(?<!\\\\)("'
                . \preg_quote($name, '/')
                . '"\s*:\s*)'
                . '(-?(?:0|[1-9]\d*)(?:\.\d+)?(?:[eE][+-]?\d+)?)'
                . '(?=\s*[,}])/';
            $json = \preg_replace($pattern, '$1"$2"', $json) ?? $json;
        }

        return \json_decode(
            $json,
            true,
            512,
            JSON_BIGINT_AS_STRING | JSON_THROW_ON_ERROR
        );
    }

    /**
     * Index the benchmark cases in the order the service declares its
     * operations, so results follow the model instead of a hard-coded list.
     *
     * @param array<string, array<string, mixed>> $shapes
     */
    private function indexCases(array $shapes): void
    {
        foreach ($this->operationShapeIds($shapes) as $shapeId) {
            $operation = self::shapeName($shapeId);
            $traits = $shapes[$shapeId]['traits'] ?? [];
            foreach ($traits['smithy.test#httpRequestTests'] ?? [] as $case) {
                if ($this->isBenchmarkCase($case)) {
                    $case['operation'] = $operation;
                    $this->requests[$case['id']] = $case;
                }
            }
            foreach ($traits['smithy.test#httpResponseTests'] ?? [] as $case) {
                if ($this->isBenchmarkCase($case)) {
                    $case['operation'] = $operation;
                    $this->responses[$case['id']] = $case;
                }
            }
        }
    }

    /**
     * @param array<string, array<string, mixed>> $shapes
     *
     * @return list<string>
     */
    private function operationShapeIds(array $shapes): array
    {
        $ids = [];
        foreach ($shapes as $shape) {
            if (($shape['type'] ?? null) !== 'service') {
                continue;
            }
            foreach ($shape['operations'] ?? [] as $operation) {
                $ids[] = $operation['target'];
            }
        }

        return $ids;
    }

    /**
     * @param array<string, mixed> $case
     */
    private function isBenchmarkCase(array $case): bool
    {
        return ($case['protocol'] ?? null) === $this->protocol
            && \in_array(self::TAG, $case['tags'] ?? [], true)
            && \is_string($case['id'] ?? null);
    }

    /**
     * @param array<string, array<string, mixed>> $indexed
     *
     * @return list<array<string, mixed>>
     */
    private function filtered(array $indexed, ?string $filter): array
    {
        $cases = [];
        foreach ($indexed as $id => $case) {
            if ($filter === null || \stripos($id, $filter) !== false) {
                $cases[] = $case;
            }
        }

        return $cases;
    }

    private static function shapeName(string $shapeId): string
    {
        $separator = \strrpos($shapeId, '#');

        return $separator === false
            ? $shapeId
            : \substr($shapeId, $separator + 1);
    }
}
