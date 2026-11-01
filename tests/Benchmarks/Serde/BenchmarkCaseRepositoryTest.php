<?php

declare(strict_types=1);

namespace Smithy\Tests\Benchmarks\Serde;

use PHPUnit\Framework\TestCase;
use Smithy\Benchmarks\Serde\Support\BenchmarkCaseRepository;

require_once __DIR__ . '/../../../benchmarks/serde/support/BenchmarkCaseRepository.php';

final class BenchmarkCaseRepositoryTest extends TestCase
{
    private string $modelPath;
    private BenchmarkCaseRepository $repository;

    public function testModelContainsOnlyTheCborBenchmarkClosure(): void
    {
        $model = \json_decode(
            (string) \file_get_contents($this->modelPath),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        foreach ($model['shapes'] as $shapeId => $shape) {
            self::assertStringStartsWith(
                'com.amazonaws.sdk.benchmark#',
                $shapeId
            );
            foreach ([
                'smithy.test#httpRequestTests',
                'smithy.test#httpResponseTests',
            ] as $traitId) {
                foreach ($shape['traits'][$traitId] ?? [] as $case) {
                    self::assertSame(
                        BenchmarkCaseRepository::PROTOCOL,
                        $case['protocol']
                    );
                    self::assertContains(
                        BenchmarkCaseRepository::TAG,
                        $case['tags']
                    );
                }
            }
        }

        $service = $model['shapes'][
            'com.amazonaws.sdk.benchmark#SmithyRpcV2CborDataPlane'
        ];
        self::assertSame(
            [
                ['target' => 'com.amazonaws.sdk.benchmark#WideTypes'],
                ['target' => 'com.amazonaws.sdk.benchmark#GetItem'],
                ['target' => 'com.amazonaws.sdk.benchmark#PutItem'],
            ],
            $service['operations']
        );
        self::assertSame([], $service['resources']);
    }

    public function testLoadsTheSameRpcV2CborCasesAsSmithyJava(): void
    {
        self::assertSame(
            BenchmarkCaseRepository::SERIALIZE_CASE_IDS,
            \array_column($this->repository->requests(), 'id')
        );
        self::assertSame(
            BenchmarkCaseRepository::DESERIALIZE_CASE_IDS,
            \array_column($this->repository->responses(), 'id')
        );
    }

    public function testFiltersCasesByIdSubstring(): void
    {
        self::assertSame(
            [
                'rpcv2Cbor_PutItemRequest_BinaryData_S',
                'rpcv2Cbor_PutItemRequest_BinaryData_M',
                'rpcv2Cbor_PutItemRequest_BinaryData_L',
            ],
            \array_column(
                $this->repository->requests('BinaryData'),
                'id'
            )
        );
    }

    public function testPreservesArbitraryPrecisionBenchmarkNumbers(): void
    {
        $params = $this->repository->requests('WideTypesRequest_S')[0][
            'params'
        ];

        self::assertSame(
            '123456789012345678901',
            $params['bigIntegerA']
        );
        self::assertSame(
            '1234567890.123456789',
            $params['bigDecimalA']
        );
    }

    protected function setUp(): void
    {
        $this->modelPath = __DIR__
            . '/../../../benchmarks/serde/model/rpcv2-cbor.json';
        $this->repository = new BenchmarkCaseRepository($this->modelPath);
    }
}
