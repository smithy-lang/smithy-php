<?php

declare(strict_types=1);

namespace Smithy\Tests\Benchmarks\Serde;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Benchmarks\Serde\Support\BenchmarkCaseRepository;

require_once __DIR__
    . '/../../../benchmarks/serde/support/BenchmarkCaseRepository.php';

final class BenchmarkCaseRepositoryTest extends TestCase
{
    #[DataProvider('protocolModels')]
    public function testModelContainsOnlyTheProtocolBenchmarkClosure(
        string $modelName,
        string $protocol,
        string $casePrefix,
        string $serviceName
    ): void
    {
        $model = \json_decode(
            (string) \file_get_contents($this->modelPath($modelName)),
            true,
            512,
            \JSON_THROW_ON_ERROR
        );

        foreach ($model['shapes'] as $shapeId => $shape) {
            self::assertStringStartsWith(
                'com.amazonaws.sdk.benchmark#',
                $shapeId
            );
            foreach ([
                'smithy.test#httpRequestTests',
                'smithy.test#httpResponseTests'
            ] as $traitId) {
                foreach ($shape['traits'][$traitId] ?? [] as $case) {
                    self::assertSame($protocol, $case['protocol']);
                    self::assertStringStartsWith(
                        $casePrefix . '_',
                        $case['id']
                    );
                    self::assertContains(
                        BenchmarkCaseRepository::TAG,
                        $case['tags']
                    );
                }
            }
        }

        $service = $model['shapes'][
            "com.amazonaws.sdk.benchmark#{$serviceName}"
        ];
        self::assertSame(
            [
                ['target' => 'com.amazonaws.sdk.benchmark#WideTypes'],
                ['target' => 'com.amazonaws.sdk.benchmark#GetItem'],
                ['target' => 'com.amazonaws.sdk.benchmark#PutItem']
            ],
            $service['operations']
        );
        self::assertSame([], $service['resources']);
    }

    #[DataProvider('protocolModels')]
    public function testLoadsExpectedRpcV2Cases(
        string $modelName,
        string $protocol,
        string $casePrefix
    ): void
    {
        $repository = new BenchmarkCaseRepository(
            $this->modelPath($modelName),
            $protocol,
            $casePrefix
        );

        self::assertSame(
            self::caseIds(
                BenchmarkCaseRepository::SERIALIZE_CASE_IDS,
                $casePrefix
            ),
            \array_column($repository->requests(), 'id')
        );
        self::assertSame(
            self::caseIds(
                BenchmarkCaseRepository::DESERIALIZE_CASE_IDS,
                $casePrefix
            ),
            \array_column($repository->responses(), 'id')
        );
    }

    #[DataProvider('protocolModels')]
    public function testFiltersCasesByIdSubstring(
        string $modelName,
        string $protocol,
        string $casePrefix
    ): void
    {
        $repository = new BenchmarkCaseRepository(
            $this->modelPath($modelName),
            $protocol,
            $casePrefix
        );

        self::assertSame(
            [
                "{$casePrefix}_PutItemRequest_BinaryData_S",
                "{$casePrefix}_PutItemRequest_BinaryData_M",
                "{$casePrefix}_PutItemRequest_BinaryData_L"
            ],
            \array_column(
                $repository->requests('BinaryData'),
                'id'
            )
        );
    }

    #[DataProvider('protocolModels')]
    public function testPreservesArbitraryPrecisionBenchmarkNumbers(
        string $modelName,
        string $protocol,
        string $casePrefix
    ): void
    {
        $repository = new BenchmarkCaseRepository(
            $this->modelPath($modelName),
            $protocol,
            $casePrefix
        );
        $params = $repository->requests('WideTypesRequest_S')[0][
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

    public function testRpcV2JsonFixturesUseProtocolSpecificEncoding(): void
    {
        $repository = new BenchmarkCaseRepository(
            $this->modelPath('rpcv2-json.json'),
            'smithy.protocols#rpcv2Json',
            'rpcv2Json'
        );
        $wideTypes = \json_decode(
            $repository->responses('WideTypesResponse_S')[0]['body'],
            true,
            512,
            \JSON_THROW_ON_ERROR
        );
        $outOfOrder = \json_decode(
            $repository->responses('OutOfOrder')[0]['body'],
            true,
            512,
            \JSON_THROW_ON_ERROR
        );

        self::assertSame(
            '123456789012345678901',
            $wideTypes['bigIntegerA']
        );
        self::assertSame(
            '1234567890123456789e-9',
            $wideTypes['bigDecimalA']
        );
        self::assertSame('__unknownString', \array_key_first($outOfOrder));
        self::assertArrayHasKey(
            '__unknownNested',
            $outOfOrder['ConsumedCapacity']
        );
    }

    /**
     * @return iterable<string, array{string, string, string, string}>
     */
    public static function protocolModels(): iterable
    {
        yield 'RPC v2 CBOR' => [
            'rpcv2-cbor.json',
            'smithy.protocols#rpcv2Cbor',
            'rpcv2Cbor',
            'SmithyRpcV2CborDataPlane'
        ];
        yield 'RPC v2 JSON' => [
            'rpcv2-json.json',
            'smithy.protocols#rpcv2Json',
            'rpcv2Json',
            'SmithyRpcV2JsonDataPlane'
        ];
    }

    private function modelPath(string $modelName): string
    {
        return __DIR__ . '/../../../benchmarks/serde/model/' . $modelName;
    }

    /**
     * @param list<string> $cborIds
     *
     * @return list<string>
     */
    private static function caseIds(
        array $cborIds,
        string $casePrefix
    ): array
    {
        return \array_map(
            fn(string $id): string => \str_replace(
                'rpcv2Cbor',
                $casePrefix,
                $id
            ),
            $cborIds
        );
    }
}
