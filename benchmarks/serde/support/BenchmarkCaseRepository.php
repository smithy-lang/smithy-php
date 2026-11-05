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

    public const SERIALIZE_CASE_IDS = [
        'rpcv2Cbor_WideTypesRequest_S',
        'rpcv2Cbor_WideTypesRequest_M',
        'rpcv2Cbor_WideTypesRequest_L',
        'rpcv2Cbor_PutItemRequest_Baseline',
        'rpcv2Cbor_PutItemRequest_ShallowMap_S',
        'rpcv2Cbor_PutItemRequest_ShallowMap_M',
        'rpcv2Cbor_PutItemRequest_ShallowMap_L',
        'rpcv2Cbor_PutItemRequest_Nested_M',
        'rpcv2Cbor_PutItemRequest_Nested_L',
        'rpcv2Cbor_PutItemRequest_MixedItem_S',
        'rpcv2Cbor_PutItemRequest_MixedItem_M',
        'rpcv2Cbor_PutItemRequest_MixedItem_L',
        'rpcv2Cbor_PutItemRequest_BinaryData_S',
        'rpcv2Cbor_PutItemRequest_BinaryData_M',
        'rpcv2Cbor_PutItemRequest_BinaryData_L'
    ];

    public const DESERIALIZE_CASE_IDS = [
        'rpcv2Cbor_WideTypesResponse_S',
        'rpcv2Cbor_WideTypesResponse_M',
        'rpcv2Cbor_WideTypesResponse_L',
        'rpcv2Cbor_GetItemOutput_Baseline',
        'rpcv2Cbor_GetItemOutput_S',
        'rpcv2Cbor_GetItemOutput_M',
        'rpcv2Cbor_GetItemOutput_L',
        'rpcv2Cbor_GetItemOutput_OutOfOrder',
        'rpcv2Cbor_GetItemOutputBinary_S',
        'rpcv2Cbor_GetItemOutputBinary_M',
        'rpcv2Cbor_GetItemOutputBinary_L'
    ];

    /** @var array<string, array<string, mixed>> */
    private array $requests = [];

    /** @var array<string, array<string, mixed>> */
    private array $responses = [];

    public function __construct(
        string $modelPath,
        private readonly string $protocol = self::PROTOCOL,
        private readonly string $casePrefix = 'rpcv2Cbor'
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
        $this->requireCases($this->serializeCaseIds(), $this->requests);
        $this->requireCases($this->deserializeCaseIds(), $this->responses);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function requests(?string $filter = null): array
    {
        return $this->orderedCases(
            $this->serializeCaseIds(),
            $this->requests,
            $filter
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function responses(?string $filter = null): array
    {
        return $this->orderedCases(
            $this->deserializeCaseIds(),
            $this->responses,
            $filter
        );
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
     * @param array<string, array<string, mixed>> $shapes
     */
    private function indexCases(array $shapes): void
    {
        foreach ($shapes as $shapeId => $shape) {
            if (($shape['type'] ?? null) !== 'operation') {
                continue;
            }

            $operation = self::shapeName($shapeId);
            $traits = $shape['traits'] ?? [];
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
     * @param array<string, mixed> $case
     */
    private function isBenchmarkCase(array $case): bool
    {
        return ($case['protocol'] ?? null) === $this->protocol
            && \in_array(self::TAG, $case['tags'] ?? [], true)
            && \is_string($case['id'] ?? null);
    }

    /**
     * @param list<string> $ids
     * @param array<string, array<string, mixed>> $indexed
     *
     * @return list<array<string, mixed>>
     */
    private function orderedCases(
        array $ids,
        array $indexed,
        ?string $filter
    ): array
    {
        $cases = [];
        foreach ($ids as $id) {
            if ($filter !== null
                && \stripos($id, $filter) === false
            ) {
                continue;
            }
            $cases[] = $indexed[$id];
        }

        return $cases;
    }

    /**
     * @param list<string> $ids
     * @param array<string, array<string, mixed>> $indexed
     */
    private function requireCases(array $ids, array $indexed): void
    {
        foreach ($ids as $id) {
            if (!isset($indexed[$id])) {
                throw new \RuntimeException(
                    "Benchmark case is missing from the model: {$id}"
                );
            }
        }
    }

    /**
     * @return list<string>
     */
    private function serializeCaseIds(): array
    {
        return $this->caseIds(self::SERIALIZE_CASE_IDS);
    }

    /**
     * @return list<string>
     */
    private function deserializeCaseIds(): array
    {
        return $this->caseIds(self::DESERIALIZE_CASE_IDS);
    }

    /**
     * @param list<string> $cborIds
     *
     * @return list<string>
     */
    private function caseIds(array $cborIds): array
    {
        if ($this->casePrefix === 'rpcv2Cbor') {
            return $cborIds;
        }

        return \array_map(
            fn(string $id): string => \str_replace(
                'rpcv2Cbor',
                $this->casePrefix,
                $id
            ),
            $cborIds
        );
    }

    private static function shapeName(string $shapeId): string
    {
        $separator = \strrpos($shapeId, '#');

        return $separator === false
            ? $shapeId
            : \substr($shapeId, $separator + 1);
    }
}
