<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Benchmarks\Serde;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Benchmarks\Serde\Support\BenchmarkCaseRepository;

require_once __DIR__
    . '/../../../benchmarks/serde/support/BenchmarkCaseRepository.php';

final class BenchmarkCaseRepositoryTest extends TestCase
{
    /**
     * @param list<string> $operations
     */
    #[DataProvider('protocolModels')]
    public function testModelContainsOnlyTheProtocolBenchmarkClosure(
        string $modelName,
        string $protocol,
        string $casePrefix,
        string $serviceName,
        array $operations
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
            \array_map(
                static fn(string $operation): array => [
                    'target' => "com.amazonaws.sdk.benchmark#{$operation}"
                ],
                $operations
            ),
            $service['operations']
        );
        self::assertSame([], $service['resources']);
    }

    /**
     * @param list<string> $operations
     * @param list<string> $requests
     * @param list<string> $responses
     */
    #[DataProvider('protocolModels')]
    public function testLoadsTheSmithyJavaCases(
        string $modelName,
        string $protocol,
        string $casePrefix,
        string $serviceName,
        array $operations,
        array $requests,
        array $responses
    ): void
    {
        $repository = new BenchmarkCaseRepository(
            $this->modelPath($modelName),
            $protocol
        );

        self::assertEqualsCanonicalizing(
            self::caseIds($requests, $casePrefix),
            \array_column($repository->requests(), 'id')
        );
        self::assertEqualsCanonicalizing(
            self::caseIds($responses, $casePrefix),
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
            $protocol
        );

        self::assertEqualsCanonicalizing(
            [
                "{$casePrefix}_WideTypesRequest_S",
                "{$casePrefix}_WideTypesRequest_M",
                "{$casePrefix}_WideTypesRequest_L"
            ],
            \array_column(
                $repository->requests('WideTypesRequest'),
                'id'
            )
        );
    }

    #[DataProvider('protocolModels')]
    public function testPreservesArbitraryPrecisionBenchmarkNumbers(
        string $modelName,
        string $protocol
    ): void
    {
        $repository = new BenchmarkCaseRepository(
            $this->modelPath($modelName),
            $protocol
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

    public function testRejectsAModelWithoutBenchmarkCasesForTheProtocol(): void
    {
        $this->expectException(\RuntimeException::class);

        new BenchmarkCaseRepository(
            $this->modelPath('rest-xml.json'),
            'aws.protocols#restJson1'
        );
    }

    public function testRestModelsBenchmarkS3AndNotDynamoDb(): void
    {
        foreach (['rest-json.json', 'rest-xml.json'] as $modelName) {
            $model = \json_decode(
                (string) \file_get_contents($this->modelPath($modelName)),
                true,
                512,
                \JSON_THROW_ON_ERROR
            );

            foreach (['GetItem', 'PutItem'] as $operation) {
                self::assertArrayNotHasKey(
                    "com.amazonaws.sdk.benchmark#{$operation}",
                    $model['shapes']
                );
            }
            foreach (['PutObject', 'GetObject', 'CopyObject'] as $operation) {
                self::assertArrayHasKey(
                    "com.amazonaws.sdk.benchmark#{$operation}",
                    $model['shapes']
                );
            }
        }
    }

    public function testRpcV2JsonFixturesUseProtocolSpecificEncoding(): void
    {
        $repository = new BenchmarkCaseRepository(
            $this->modelPath('rpcv2-json.json'),
            'smithy.protocols#rpcv2Json'
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
     * Each model carries the operations smithy-java benchmarks for the
     * protocol. Case names are listed without the protocol prefix.
     *
     * @return iterable<string, array{
     *     string, string, string, string,
     *     list<string>, list<string>, list<string>
     * }>
     */
    public static function protocolModels(): iterable
    {
        $wideRequests = [
            'WideTypesRequest_S',
            'WideTypesRequest_M',
            'WideTypesRequest_L'
        ];
        $wideResponses = [
            'WideTypesResponse_S',
            'WideTypesResponse_M',
            'WideTypesResponse_L'
        ];
        $putItemRequests = [
            'PutItemRequest_Baseline',
            'PutItemRequest_ShallowMap_S',
            'PutItemRequest_ShallowMap_M',
            'PutItemRequest_ShallowMap_L',
            'PutItemRequest_Nested_M',
            'PutItemRequest_Nested_L',
            'PutItemRequest_MixedItem_S',
            'PutItemRequest_MixedItem_M',
            'PutItemRequest_MixedItem_L',
            'PutItemRequest_BinaryData_S',
            'PutItemRequest_BinaryData_M',
            'PutItemRequest_BinaryData_L'
        ];
        $getItemResponses = [
            'GetItemOutput_Baseline',
            'GetItemOutput_S',
            'GetItemOutput_M',
            'GetItemOutput_L',
            'GetItemOutput_OutOfOrder',
            'GetItemOutputBinary_S',
            'GetItemOutputBinary_M',
            'GetItemOutputBinary_L'
        ];
        $dynamoDbOperations = ['WideTypes', 'GetItem', 'PutItem'];
        $awsJsonOperations = [...$dynamoDbOperations, 'Healthcheck'];
        $awsJsonRequests = [
            ...$wideRequests,
            'GetItemInput_Baseline',
            ...$putItemRequests,
            'HealthcheckRequest_Example'
        ];
        $awsJsonResponses = [
            ...$wideResponses,
            ...$getItemResponses,
            'HealthcheckResponse_Example'
        ];
        $restOperations = [
            'WideTypes',
            'CopyObject',
            'PutObject',
            'GetObject'
        ];
        $restRequests = [
            ...$wideRequests,
            'CopyObjectRequest_Baseline',
            'CopyObjectRequest_M',
            'PutObject_S',
            'PutObject_M',
            'PutObject_L'
        ];
        $restResponses = [
            ...$wideResponses,
            'CopyObjectOutput_Baseline',
            'CopyObjectOutput_M',
            'CopyObjectOutput_OutOfOrder',
            'GetObject_S',
            'GetObject_M',
            'GetObject_L'
        ];

        yield 'RPC v2 CBOR' => [
            'rpcv2-cbor.json',
            'smithy.protocols#rpcv2Cbor',
            'rpcv2Cbor',
            'SmithyRpcV2CborDataPlane',
            $dynamoDbOperations,
            [...$wideRequests, ...$putItemRequests],
            [...$wideResponses, ...$getItemResponses]
        ];
        yield 'RPC v2 JSON' => [
            'rpcv2-json.json',
            'smithy.protocols#rpcv2Json',
            'rpcv2Json',
            'SmithyRpcV2JsonDataPlane',
            $dynamoDbOperations,
            [...$wideRequests, ...$putItemRequests],
            [...$wideResponses, ...$getItemResponses]
        ];
        yield 'AWS JSON 1.0' => [
            'aws-json-1-0.json',
            'aws.protocols#awsJson1_0',
            'awsJson10',
            'AwsJson10DataPlane',
            $awsJsonOperations,
            $awsJsonRequests,
            $awsJsonResponses
        ];
        yield 'AWS JSON 1.1' => [
            'aws-json-1-1.json',
            'aws.protocols#awsJson1_1',
            'awsJson11',
            'AwsJson11DataPlane',
            $awsJsonOperations,
            $awsJsonRequests,
            $awsJsonResponses
        ];
        yield 'AWS Query' => [
            'aws-query.json',
            'aws.protocols#awsQuery',
            'awsQuery',
            'AwsQueryDataPlane',
            ['WideTypes', 'GetMetricData', 'PutMetricData'],
            [
                ...$wideRequests,
                'GetMetricDataRequest_S',
                'GetMetricDataRequest_M',
                'GetMetricDataRequest_L',
                'PutMetricDataRequest_Baseline',
                'PutMetricDataRequest_S',
                'PutMetricDataRequest_M',
                'PutMetricDataRequest_L'
            ],
            [
                ...$wideResponses,
                'GetMetricDataResponse_S',
                'GetMetricDataResponse_M',
                'GetMetricDataResponse_L',
                'GetMetricDataResponse_OutOfOrder'
            ]
        ];
        yield 'EC2 Query' => [
            'ec2-query.json',
            'aws.protocols#ec2Query',
            'ec2Query',
            'Ec2QueryDataPlane',
            ['WideTypes'],
            $wideRequests,
            $wideResponses
        ];
        yield 'REST JSON' => [
            'rest-json.json',
            'aws.protocols#restJson1',
            'restJson',
            'RestJsonDataPlane',
            $restOperations,
            $restRequests,
            $restResponses
        ];
        yield 'REST XML' => [
            'rest-xml.json',
            'aws.protocols#restXml',
            'restXml',
            'RestXmlDataPlane',
            $restOperations,
            $restRequests,
            $restResponses
        ];
    }

    private function modelPath(string $modelName): string
    {
        return __DIR__ . '/../../../benchmarks/serde/model/' . $modelName;
    }

    /**
     * @param list<string> $names
     *
     * @return list<string>
     */
    private static function caseIds(array $names, string $casePrefix): array
    {
        return \array_map(
            static fn(string $name): string => "{$casePrefix}_{$name}",
            $names
        );
    }
}
