#!/usr/bin/env php
<?php

declare(strict_types=1);

use Smithy\Benchmarks\Serde\Support\BenchmarkCaseRepository;
use Smithy\Benchmarks\Serde\Support\BenchmarkEnvironment;
use Smithy\Benchmarks\Serde\Support\BenchmarkRunner;
use Smithy\Benchmarks\Serde\Support\BenchmarkValueComparator;
use Smithy\Benchmarks\Serde\Support\GeneratedCodeLoader;
use Smithy\Benchmarks\Serde\Support\ResultWriter;
use Smithy\Codec\Cbor\CborCodec;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Core\Shape\Utils\ArrayShapeSerializer;
use Smithy\Http\Message\{
    HttpTransportRequest,
    HttpTransportResponse,
    Response
};
use Smithy\Protocol\RpcV2\RpcV2CborProtocol;

require __DIR__ . '/bootstrap.php';

$options = \getopt('', [
    'help',
    'filter:',
    'direction:',
    'warmup:',
    'min-iterations:',
    'max-iterations:',
    'max-seconds:',
    'validate',
    'update-readme',
]);

if (isset($options['help'])) {
    \fwrite(STDOUT, <<<'HELP'
Usage: php benchmarks/serde/run.php [options]

Options:
  --filter=TEXT          Run case IDs containing TEXT
  --direction=VALUE      all, serialize, or deserialize (default: all)
  --warmup=N             Warmup calls per case (default: 500)
  --min-iterations=N     Minimum measured calls per case (default: 1000)
  --max-iterations=N     Maximum measured calls per case (default: 5000)
  --max-seconds=N        Maximum measured seconds per case (default: 5)
  --validate             Validate selected cases without measuring
  --update-readme        Replace README results; requires an unfiltered
                         all-direction measured run
  --help                 Show this help

HELP);

    exit(0);
}

$root = \dirname(__DIR__, 2);
$generatedDirectory = $root . '/build/benchmarks/serde/rpcv2-cbor/src';
if (!\is_dir($generatedDirectory)) {
    \fwrite(
        STDERR,
        "Generated benchmark shapes are missing. "
            . "Run make benchmark-generate.\n"
    );

    exit(1);
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
    && ($filter !== null || $direction !== 'all' || $validateOnly)
) {
    \fwrite(
        STDERR,
        "--update-readme requires an unfiltered all-direction measured run.\n"
    );

    exit(2);
}
$runner = new BenchmarkRunner(
    warmupIterations: (int) ($options['warmup'] ?? 500),
    minIterations: (int) ($options['min-iterations'] ?? 1_000),
    maxIterations: (int) ($options['max-iterations'] ?? 5_000),
    maxSeconds: (float) ($options['max-seconds'] ?? 5)
);
$repository = new BenchmarkCaseRepository(
    __DIR__ . '/model/rpcv2-cbor.json'
);
$loader = new GeneratedCodeLoader(
    'Smithy\\Benchmarks\\RpcV2Cbor',
    $generatedDirectory
);
$loader->register();
$protocol = new RpcV2CborProtocol();
$results = [];
$caseCount = 0;

\fwrite(STDOUT, "Smithy PHP RPC v2 CBOR serde benchmark\n");
\fwrite(STDOUT, "PHP " . PHP_VERSION . ' on ' . \php_uname() . "\n\n");

if ($direction === 'all' || $direction === 'serialize') {
    foreach ($repository->requests($filter) as $case) {
        $operation = resolveOperation($loader, $case);
        $inputClass = $operation->getInputClass();
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

        validateSerialization($protocol, $operation, $input, $case);
        $caseCount++;
        if ($validateOnly) {
            printValidated('serialize', $case['id']);
            continue;
        }

        $statistics = $runner->run(
            static fn() => $protocol->serializeRequest(
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
        $body = decodeResponseBody($case['body'] ?? null);
        $headers = $case['headers'] ?? [];
        $headers['Smithy-Protocol'] = 'rpc-v2-cbor';
        $response = new HttpTransportResponse(
            new Response(
                status: (int) ($case['code'] ?? 200),
                headers: $headers,
                body: $body
            )
        );

        $output = deserializeResponse($protocol, $operation, $response);
        validateDeserialization($operation, $output, $case);
        $caseCount++;
        if ($validateOnly) {
            printValidated('deserialize', $case['id']);
            continue;
        }

        $statistics = $runner->run(
            static fn() => deserializeResponse(
                $protocol,
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
        'serde-rpcv2-cbor'
    ),
    'serde_benchmarks' => $results,
];
$writer = new ResultWriter();
$resultPath = $writer->writeJson(
    $resultSet,
    $root . '/build/benchmarks/results'
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
 * @param array<string, mixed> $case
 */
function resolveOperation(
    GeneratedCodeLoader $loader,
    array $case
): OperationInterface {
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
    RpcV2CborProtocol $protocol,
    OperationInterface $operation,
    SerializableStructInterface $input,
    array $case
): void {
    $request = $protocol->serializeRequest(
        $operation,
        $input
    );
    $httpRequest = HttpTransportRequest::expect($request)->request;
    if ($httpRequest->getMethod() !== 'POST'
        || $httpRequest->getHeaderLine('Smithy-Protocol') !== 'rpc-v2-cbor'
    ) {
        throw new \RuntimeException(
            "Invalid serialized request metadata for {$case['id']}."
        );
    }

    $codec = new CborCodec();
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

function deserializeResponse(
    RpcV2CborProtocol $protocol,
    OperationInterface $operation,
    HttpTransportResponse $response
): SerializableStructInterface {
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
): void {
    $outputClass = $operation->getOutputClass();
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

function decodeResponseBody(mixed $body): string
{
    if (!\is_string($body) || $body === '') {
        return "\xa0";
    }

    $decoded = \base64_decode($body, true);
    if ($decoded === false) {
        throw new \RuntimeException('Invalid base64 CBOR benchmark body.');
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
): void {
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
