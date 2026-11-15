#!/usr/bin/env php
<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

use Smithy\Benchmarks\Serde\Support\BenchmarkCaseRepository;
use Smithy\Benchmarks\Serde\Support\BenchmarkEnvironment;
use Smithy\Benchmarks\Serde\Support\BenchmarkRunner;
use Smithy\Benchmarks\Serde\Support\BenchmarkValueComparator;
use Smithy\Benchmarks\Serde\Support\BenchmarkWireComparator;
use Smithy\Benchmarks\Serde\Support\GeneratedCodeLoader;
use Smithy\Benchmarks\Serde\Support\ResultWriter;
use Smithy\Codec\AbstractCodec;
use Smithy\Codec\Cbor\CborCodec;
use Smithy\Codec\Json\{
    JsonCodec,
    JsonCodecSettings
};
use Smithy\Core\Serde\{
    SerializableStructInterface,
    TimestampFormat
};
use Smithy\Core\Shape\OperationInterface;
use Smithy\Core\Shape\Utils\ArrayShapeSerializer;
use Smithy\Http\Message\{
    HttpTransportRequest,
    HttpTransportResponse,
    Response
};
use Smithy\Protocol\Aws\{
    AwsJson1_0Protocol,
    AwsJson1_1Protocol,
    AwsQueryProtocol,
    Ec2QueryProtocol,
    RestJsonProtocol,
    RestXmlProtocol
};
use Smithy\Protocol\ProtocolInterface;
use Smithy\Protocol\RpcV2\{
    RpcV2CborProtocol,
    RpcV2JsonProtocol
};

require __DIR__ . '/bootstrap.php';

$options = \getopt('', [
    'help',
    'protocol:',
    'filter:',
    'direction:',
    'warmup:',
    'min-iterations:',
    'max-iterations:',
    'max-seconds:',
    'validate',
    'update-readme'
]);

if (isset($options['help'])) {
    \fwrite(STDOUT, <<<'HELP'
Usage: php benchmarks/serde/run.php [options]

Options:
  --protocol=VALUE       all, rpcv2-cbor, rpcv2-json, aws-json-1-0,
                         aws-json-1-1, aws-query, ec2-query, rest-json,
                         or rest-xml (default: all)
  --filter=TEXT          Run case IDs containing TEXT
  --direction=VALUE      all, serialize, or deserialize (default: all)
  --warmup=N             Warmup calls per case (default: 500)
  --min-iterations=N     Minimum measured calls per case (default: 1000)
  --max-iterations=N     Maximum measured calls per case (default: 5000)
  --max-seconds=N        Maximum measured seconds per case (default: 5)
  --validate             Validate selected cases without measuring
  --update-readme        Replace README results; requires an unfiltered
                         all-protocol, all-direction measured run
  --help                 Show this help

HELP);

    exit(0);
}

$root = \dirname(__DIR__, 2);
$jsonCodec = new JsonCodec(new JsonCodecSettings(
    defaultTimestampFormat: TimestampFormat::EpochSeconds,
    honorJsonName: false,
    honorTimestampFormatTrait: false,
    useStringForArbitraryPrecision: true
));
$serviceVersion = '1999-12-31';
$protocols = [
    'rpcv2-cbor' => [
        'label' => 'RPC v2 CBOR',
        'model' => $root . '/benchmarks/serde/model/rpcv2-cbor.json',
        'namespace' => 'Smithy\\Benchmarks\\RpcV2Cbor',
        'directory' => $root
            . '/build/benchmarks/serde/rpcv2-cbor/src',
        'protocolId' => RpcV2CborProtocol::ID,
        'header' => 'rpc-v2-cbor',
        'contentType' => 'application/cbor',
        'emptyBody' => "\xa0",
        'base64Body' => true,
        'validation' => 'roundTrip',
        'protocol' => new RpcV2CborProtocol(),
        'codec' => new CborCodec()
    ],
    'rpcv2-json' => [
        'label' => 'RPC v2 JSON',
        'model' => $root . '/benchmarks/serde/model/rpcv2-json.json',
        'namespace' => 'Smithy\\Benchmarks\\RpcV2Json',
        'directory' => $root
            . '/build/benchmarks/serde/rpcv2-json/src',
        'protocolId' => RpcV2JsonProtocol::ID,
        'header' => 'rpc-v2-json',
        'contentType' => 'application/json',
        'emptyBody' => '{}',
        'base64Body' => false,
        'validation' => 'roundTrip',
        'protocol' => new RpcV2JsonProtocol(),
        'codec' => $jsonCodec
    ],
    'aws-json-1-0' => awsConfig(
        $root,
        'AWS JSON 1.0',
        'aws-json-1-0',
        'AwsJson10',
        AwsJson1_0Protocol::ID,
        new AwsJson1_0Protocol()
    ),
    'aws-json-1-1' => awsConfig(
        $root,
        'AWS JSON 1.1',
        'aws-json-1-1',
        'AwsJson11',
        AwsJson1_1Protocol::ID,
        new AwsJson1_1Protocol()
    ),
    'aws-query' => awsConfig(
        $root,
        'AWS Query',
        'aws-query',
        'AwsQuery',
        AwsQueryProtocol::ID,
        new AwsQueryProtocol($serviceVersion)
    ),
    'ec2-query' => awsConfig(
        $root,
        'EC2 Query',
        'ec2-query',
        'Ec2Query',
        Ec2QueryProtocol::ID,
        new Ec2QueryProtocol($serviceVersion)
    ),
    'rest-json' => awsConfig(
        $root,
        'REST JSON',
        'rest-json',
        'RestJson',
        RestJsonProtocol::ID,
        new RestJsonProtocol()
    ),
    'rest-xml' => awsConfig(
        $root,
        'REST XML',
        'rest-xml',
        'RestXml',
        RestXmlProtocol::ID,
        new RestXmlProtocol()
    )
];

$protocolSelection = (string) ($options['protocol'] ?? 'all');
if ($protocolSelection !== 'all'
    && !isset($protocols[$protocolSelection])
) {
    \fwrite(
        STDERR,
        '--protocol must be all or one of: '
            . \implode(', ', \array_keys($protocols)) . ".\n"
    );

    exit(2);
}

$direction = (string) ($options['direction'] ?? 'all');
if (!\in_array($direction, ['all', 'serialize', 'deserialize'], true)) {
    \fwrite(
        STDERR,
        "--direction must be all, serialize, or deserialize.\n"
    );

    exit(2);
}

$filter = isset($options['filter'])
    ? (string) $options['filter']
    : null;
$validateOnly = isset($options['validate']);
$updateReadme = isset($options['update-readme']);
if ($updateReadme
    && ($protocolSelection !== 'all'
        || $filter !== null
        || $direction !== 'all'
        || $validateOnly)
) {
    \fwrite(
        STDERR,
        "--update-readme requires an unfiltered all-protocol, "
            . "all-direction measured run.\n"
    );

    exit(2);
}

$runner = new BenchmarkRunner(
    warmupIterations: (int) ($options['warmup'] ?? 500),
    minIterations: (int) ($options['min-iterations'] ?? 1_000),
    maxIterations: (int) ($options['max-iterations'] ?? 5_000),
    maxSeconds: (float) ($options['max-seconds'] ?? 5)
);
$results = [];
$caseCount = 0;

\fwrite(STDOUT, "Smithy PHP serde benchmark\n");
\fwrite(STDOUT, "PHP " . PHP_VERSION . ' on ' . \php_uname() . "\n");

foreach ($protocols as $name => $config) {
    if ($protocolSelection !== 'all' && $protocolSelection !== $name) {
        continue;
    }
    if (!\is_dir($config['directory'])) {
        \fwrite(
            STDERR,
            "Generated {$config['label']} benchmark shapes are missing. "
                . "Run make benchmark-generate.\n"
        );

        exit(1);
    }

    $repository = new BenchmarkCaseRepository(
        $config['model'],
        $config['protocolId']
    );
    $loader = new GeneratedCodeLoader(
        $config['namespace'],
        $config['directory']
    );
    $loader->register();
    \fwrite(STDOUT, "\n{$config['label']}\n");

    if ($direction === 'all' || $direction === 'serialize') {
        foreach ($repository->requests($filter) as $case) {
            $operation = resolveOperation($loader, $case);
            $inputClass = $operation::INPUT_CLASS;
            if ($inputClass === '') {
                throw new \RuntimeException(
                    "No generated input class for {$case['id']}."
                );
            }

            $input = ArrayShapeSerializer::fromArray(
                $inputClass,
                $case['params'] ?? []
            );
            if (!$input instanceof SerializableStructInterface) {
                throw new \RuntimeException(
                    "Generated input is invalid for {$case['id']}."
                );
            }

            if ($config['validation'] === 'wire') {
                validateWireSerialization(
                    $config['protocol'],
                    $operation,
                    $input,
                    $case
                );
            } else {
                validateSerialization(
                    $config['protocol'],
                    $config['codec'],
                    $config['header'],
                    $operation,
                    $input,
                    $case
                );
            }
            $caseCount++;
            if ($validateOnly) {
                printValidated('serialize', $case['id']);
                continue;
            }

            $statistics = $runner->run(
                static fn() => $config['protocol']->serializeRequest(
                    $operation,
                    $input
                )
            );
            $results[] = ResultWriter::benchmarkEntry(
                $case['id'],
                $statistics
            );
            printResult('serialize', $case['id'], $statistics);
        }
    }

    if ($direction === 'all' || $direction === 'deserialize') {
        foreach ($repository->responses($filter) as $case) {
            $operation = resolveOperation($loader, $case);
            $body = decodeResponseBody(
                $case['body'] ?? null,
                $config['base64Body'],
                $config['emptyBody']
            );
            $headers = $case['headers'] ?? [];
            if ($config['header'] !== null) {
                $headers['Smithy-Protocol'] = $config['header'];
            }
            if ($config['contentType'] !== null) {
                $headers['Content-Type'] = $config['contentType'];
            }
            $response = new HttpTransportResponse(
                new Response(
                    status: (int) ($case['code'] ?? 200),
                    headers: $headers,
                    body: $body
                )
            );

            $output = deserializeResponse(
                $config['protocol'],
                $operation,
                $response
            );
            validateDeserialization($operation, $output, $case);
            $caseCount++;
            if ($validateOnly) {
                printValidated('deserialize', $case['id']);
                continue;
            }

            $statistics = $runner->run(
                static fn() => deserializeResponse(
                    $config['protocol'],
                    $operation,
                    $response
                )
            );
            $results[] = ResultWriter::benchmarkEntry(
                $case['id'],
                $statistics
            );
            printResult('deserialize', $case['id'], $statistics);
        }
    }

    $loader->unregister();
}

if ($caseCount === 0) {
    \fwrite(STDERR, "No benchmark cases matched the selected filters.\n");

    exit(1);
}

if ($validateOnly) {
    \fwrite(STDOUT, "\nValidated {$caseCount} benchmark cases.\n");

    exit(0);
}

$resultSet = [
    'metadata' => BenchmarkEnvironment::metadata(
        $root,
        $runner,
        'serde-rpcv2'
    ),
    'serde_benchmarks' => $results
];
$writer = new ResultWriter();
$resultPath = $writer->writeJson(
    $resultSet,
    $root . '/build/benchmarks/results',
    'serde-rpcv2'
);
if ($updateReadme) {
    $writer->updateReadme(
        $resultSet,
        $root . '/benchmarks/README.md'
    );
}

\fwrite(STDOUT, "\nRaw results: {$resultPath}\n");
if ($updateReadme) {
    \fwrite(STDOUT, "Updated benchmarks/README.md.\n");
}

/**
 * Build the configuration for an AWS protocol benchmark. The fixtures carry
 * their own headers, so no protocol header or content type is forced.
 *
 * @return array<string, mixed>
 */
function awsConfig(
    string $root,
    string $label,
    string $name,
    string $namespace,
    string $protocolId,
    ProtocolInterface $protocol
): array
{
    return [
        'label' => $label,
        'model' => "{$root}/benchmarks/serde/model/{$name}.json",
        'namespace' => "Smithy\\Benchmarks\\{$namespace}",
        'directory' => "{$root}/build/benchmarks/serde/{$name}/src",
        'protocolId' => $protocolId,
        'header' => null,
        'contentType' => null,
        'emptyBody' => '',
        'base64Body' => false,
        'validation' => 'wire',
        'protocol' => $protocol,
        'codec' => null
    ];
}

/**
 * @param array<string, mixed> $case
 */
function resolveOperation(
    GeneratedCodeLoader $loader,
    array $case
): OperationInterface
{
    $class = $loader->operationClass((string) $case['operation']);
    if (!\is_a($class, OperationInterface::class, true)) {
        throw new \RuntimeException(
            "Generated operation class not found: {$class}"
        );
    }

    return $class::instance();
}

/**
 * @param array<string, mixed> $case
 */
function validateSerialization(
    ProtocolInterface $protocol,
    AbstractCodec $codec,
    string $protocolHeader,
    OperationInterface $operation,
    SerializableStructInterface $input,
    array $case
): void
{
    $request = $protocol->serializeRequest(
        $operation,
        $input
    );
    $httpRequest = HttpTransportRequest::expect($request)->request;
    if ($httpRequest->getMethod() !== 'POST'
        || $httpRequest->getHeaderLine('Smithy-Protocol')
            !== $protocolHeader
    ) {
        throw new \RuntimeException(
            "Invalid serialized request metadata for {$case['id']}."
        );
    }

    $schema = $operation->getInputSchema();
    $members = $codec->createMembersBuffer($schema);
    $httpRequest->getBody()->rewind();
    $codec->createDeserializer($httpRequest->getBody())
        ->deserialize($schema, $members);
    $roundTrip = $operation->createInput($members);
    if (!BenchmarkValueComparator::equivalent(
        ArrayShapeSerializer::toArray($input),
        ArrayShapeSerializer::toArray($roundTrip)
    )) {
        throw new \RuntimeException(
            "Serialized request did not round-trip for {$case['id']}."
        );
    }
}

/**
 * Check the HTTP request against the fixture. HTTP-binding and AWS RPC
 * protocols build the method, path, query, headers, and body differently, so
 * the body is compared with the fixture instead of decoded into the input.
 *
 * @param array<string, mixed> $case
 */
function validateWireSerialization(
    ProtocolInterface $protocol,
    OperationInterface $operation,
    SerializableStructInterface $input,
    array $case
): void
{
    $httpRequest = HttpTransportRequest::expect(
        $protocol->serializeRequest($operation, $input)
    )->request;
    $actualQuery = \array_filter(
        \explode('&', $httpRequest->getUri()->getQuery())
    );
    $expectedQuery = $case['queryParams'] ?? [];
    \sort($actualQuery);
    \sort($expectedQuery);
    if ($httpRequest->getMethod() !== $case['method']
        || $httpRequest->getUri()->getPath() !== $case['uri']
        || $actualQuery !== $expectedQuery
    ) {
        throw new \RuntimeException(
            "Invalid serialized request metadata for {$case['id']}."
        );
    }
    foreach ($case['headers'] ?? [] as $name => $value) {
        if ($httpRequest->getHeaderLine($name) !== $value) {
            throw new \RuntimeException(
                "Invalid serialized {$name} header for {$case['id']}."
            );
        }
    }

    $httpRequest->getBody()->rewind();
    if (!BenchmarkWireComparator::equivalent(
        $case['bodyMediaType'] ?? '',
        $case['body'] ?? '',
        (string) $httpRequest->getBody()
    )) {
        throw new \RuntimeException(
            "Serialized request body mismatch for {$case['id']}."
        );
    }
}

function deserializeResponse(
    ProtocolInterface $protocol,
    OperationInterface $operation,
    HttpTransportResponse $response
): SerializableStructInterface
{
    $response->response->getBody()->rewind();

    return $protocol->deserializeResponse($operation, $response);
}

/**
 * @param array<string, mixed> $case
 */
function validateDeserialization(
    OperationInterface $operation,
    SerializableStructInterface $output,
    array $case
): void
{
    $outputClass = $operation::OUTPUT_CLASS;
    if ($outputClass === '' || !$output instanceof $outputClass) {
        throw new \RuntimeException(
            "Unexpected output type for {$case['id']}."
        );
    }

    if (!isset($case['params'])) {
        return;
    }

    $expected = ArrayShapeSerializer::fromArray(
        $outputClass,
        $case['params']
    );
    if (!BenchmarkValueComparator::equivalent(
        ArrayShapeSerializer::toArray($expected),
        ArrayShapeSerializer::toArray($output)
    )) {
        throw new \RuntimeException(
            "Deserialized response mismatch for {$case['id']}."
        );
    }
}

function decodeResponseBody(
    mixed $body,
    bool $base64Encoded,
    string $emptyBody
): string
{
    if (!\is_string($body) || $body === '') {
        return $emptyBody;
    }
    if (!$base64Encoded) {
        return $body;
    }

    $decoded = \base64_decode($body, true);
    if ($decoded === false) {
        throw new \RuntimeException('Invalid base64 benchmark body.');
    }

    return $decoded;
}

/**
 * @param array<string, int|float|null> $statistics
 */
function printResult(
    string $direction,
    string $id,
    array $statistics
): void
{
    \printf(
        "%-11s %-54s %8s p50, %10s ops/CPU s\n",
        $direction,
        $id,
        ResultWriter::formatDuration($statistics['p50_ns']),
        \is_float($statistics['ops_per_cpu_sec'])
            ? \number_format($statistics['ops_per_cpu_sec'], 0)
            : 'unavailable'
    );
}

function printValidated(string $direction, string $id): void
{
    \printf("%-11s %-54s valid\n", $direction, $id);
}
