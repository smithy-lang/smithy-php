#!/usr/bin/env php
<?php

declare(strict_types=1);

use Smithy\Benchmarks\RpcV2Cbor\RpcV2CborClient;
use Smithy\Benchmarks\Serde\Support\BenchmarkCaseRepository;
use Smithy\Benchmarks\Serde\Support\BenchmarkEnvironment;
use Smithy\Benchmarks\Serde\Support\BenchmarkRunner;
use Smithy\Benchmarks\Serde\Support\BenchmarkValueComparator;
use Smithy\Benchmarks\Serde\Support\GeneratedCodeLoader;
use Smithy\Benchmarks\Serde\Support\FixedResponseHttpClient;
use Smithy\Benchmarks\Serde\Support\PipelineBenchmarkPlugin;
use Smithy\Benchmarks\Serde\Support\ResultWriter;
use Smithy\Client\Auth\{
    NoAuthScheme,
    StaticAuthSchemeResolver
};
use Smithy\Client\{
    ClientConfig,
    ClientInterface
};
use Smithy\Client\Endpoint\{
    Endpoint,
    StaticEndpointResolver
};
use Smithy\Client\Plugin\{
    PluginCollection,
    PluginInterface
};
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\Utils\ArrayShapeSerializer;
use Smithy\Http\Message\Response;
use Smithy\Protocol\RpcV2\RpcV2CborProtocol;

require \dirname(__DIR__) . '/serde/bootstrap.php';

$options = \getopt('', [
    'help',
    'filter:',
    'warmup:',
    'min-iterations:',
    'max-iterations:',
    'max-seconds:',
    'validate',
    'update-readme',
]);

if (isset($options['help'])) {
    \fwrite(STDOUT, <<<'HELP'
Usage: php benchmarks/client/run.php [options]

Options:
  --filter=TEXT          Run case IDs containing TEXT
  --warmup=N             Warmup calls per case (default: 500)
  --min-iterations=N     Minimum measured calls per case (default: 1000)
  --max-iterations=N     Maximum measured calls per case (default: 5000)
  --max-seconds=N        Maximum measured seconds per case (default: 5)
  --validate             Validate selected cases without measuring
  --update-readme        Replace README results; requires an unfiltered run
  --help                 Show this help

HELP);

    exit(0);
}

$root = \dirname(__DIR__, 2);
$generatedDirectory = $root . '/build/benchmarks/serde/rpcv2-cbor/src';
if (!\is_dir($generatedDirectory)) {
    \fwrite(
        STDERR,
        "Generated benchmark client is missing. "
            . "Run make benchmark-generate.\n"
    );

    exit(1);
}

$filter = isset($options['filter'])
    ? (string) $options['filter']
    : null;
$validateOnly = isset($options['validate']);
$updateReadme = isset($options['update-readme']);
if ($updateReadme && ($filter !== null || $validateOnly)) {
    \fwrite(
        STDERR,
        "--update-readme requires an unfiltered measured run.\n"
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
    $root . '/benchmarks/serde/model/rpcv2-cbor.json'
);
$loader = new GeneratedCodeLoader(
    'Smithy\\Benchmarks\\RpcV2Cbor',
    $generatedDirectory
);
$loader->register();
$results = [];
$caseCount = 0;

\fwrite(STDOUT, "Smithy PHP generated client pipeline benchmark\n");
\fwrite(STDOUT, "PHP " . PHP_VERSION . ' on ' . \php_uname() . "\n\n");

$requestCases = casesBySize(
    $repository->requests('WideTypesRequest_')
);
$responseCases = casesBySize(
    $repository->responses('WideTypesResponse_')
);

foreach ([
    'default' => new PluginCollection(),
    'interceptor' => new PluginCollection(
        new PipelineBenchmarkPlugin()
    ),
] as $mode => $plugins) {
    foreach (['S', 'M', 'L'] as $size) {
        $id = "rpcv2Cbor_ClientPipeline_{$mode}_{$size}";
        if ($filter !== null && \stripos($id, $filter) === false) {
            continue;
        }

        $requestCase = $requestCases[$size];
        $responseCase = $responseCases[$size];
        $client = createClient($responseCase, $plugins);
        $input = ArrayShapeSerializer::fromArray(
            'Smithy\\Benchmarks\\RpcV2Cbor\\Input\\WideTypesInput',
            $requestCase['params']
        );
        if (!$input instanceof SerializableStructInterface) {
            throw new \RuntimeException(
                "Generated input is invalid for {$id}."
            );
        }

        $benchmark = static fn(): SerializableStructInterface =>
            $client->wideTypes($input);
        validatePipelineOutput($benchmark(), $responseCase, $id);
        $caseCount++;
        if ($validateOnly) {
            \printf("%-60s valid\n", $id);
            continue;
        }

        $statistics = $runner->run($benchmark);
        $results[] = ResultWriter::benchmarkEntry($id, $statistics);
        \printf(
            "%-60s %8s p50, %10s ops/CPU s\n",
            $id,
            ResultWriter::formatDuration($statistics['p50_ns']),
            \is_float($statistics['ops_per_cpu_sec'])
                ? \number_format($statistics['ops_per_cpu_sec'], 0)
                : 'unavailable'
        );
    }
}

if ($caseCount === 0) {
    \fwrite(STDERR, "No client benchmark cases matched the filter.\n");

    exit(1);
}

if ($validateOnly) {
    \fwrite(STDOUT, "\nValidated {$caseCount} client benchmark cases.\n");

    exit(0);
}

$resultSet = [
    'metadata' => BenchmarkEnvironment::metadata(
        $root,
        $runner,
        'client-rpcv2-cbor'
    ),
    'client_benchmarks' => $results,
];
$writer = new ResultWriter();
$resultPath = $writer->writeJson(
    $resultSet,
    $root . '/build/benchmarks/results',
    'client-rpcv2-cbor'
);
if ($updateReadme) {
    $writer->updateClientReadme(
        $resultSet,
        $root . '/benchmarks/README.md'
    );
}

\fwrite(STDOUT, "\nRaw results: {$resultPath}\n");
if ($updateReadme) {
    \fwrite(STDOUT, "Updated benchmarks/README.md.\n");
}

/**
 * @param list<array<string, mixed>> $cases
 *
 * @return array<string, array<string, mixed>>
 */
function casesBySize(array $cases): array
{
    $indexed = [];
    foreach ($cases as $case) {
        $id = (string) $case['id'];
        $separator = \strrpos($id, '_');
        if ($separator !== false) {
            $indexed[\substr($id, $separator + 1)] = $case;
        }
    }

    foreach (['S', 'M', 'L'] as $size) {
        if (!isset($indexed[$size])) {
            throw new \RuntimeException(
                "Missing WideTypes {$size} pipeline fixture."
            );
        }
    }

    return $indexed;
}

/**
 * @param array<string, mixed> $responseCase
 */
function createClient(
    array $responseCase,
    PluginCollection $plugins
): ClientInterface {
    $body = \base64_decode((string) $responseCase['body'], true);
    if ($body === false) {
        throw new \RuntimeException('Invalid pipeline response body.');
    }

    $headers = $responseCase['headers'] ?? [];
    $headers['Smithy-Protocol'] = 'rpc-v2-cbor';
    $httpClient = new FixedResponseHttpClient(new Response(
        status: (int) $responseCase['code'],
        headers: $headers,
        body: $body
    ));
    $config = (new ClientConfig(
        protocol: new RpcV2CborProtocol(),
        transport: new \Smithy\Http\Client\HttpClientTransport(
            $httpClient
        ),
        endpointResolver: new StaticEndpointResolver(
            new Endpoint('http://localhost')
        ),
        supportedAuthSchemes: [NoAuthScheme::instance()],
        authSchemeResolver: StaticAuthSchemeResolver::noAuth()
    ))->withPluginPredicate(
        static fn(PluginInterface $plugin): bool =>
            $plugin instanceof PipelineBenchmarkPlugin
    );

    return new RpcV2CborClient($config, $plugins);
}

/**
 * @param array<string, mixed> $responseCase
 */
function validatePipelineOutput(
    SerializableStructInterface $output,
    array $responseCase,
    string $id
): void {
    $expected = ArrayShapeSerializer::fromArray(
        'Smithy\\Benchmarks\\RpcV2Cbor\\Output\\WideTypesOutput',
        $responseCase['params']
    );
    if (!BenchmarkValueComparator::equivalent(
        ArrayShapeSerializer::toArray($expected),
        ArrayShapeSerializer::toArray($output)
    )) {
        throw new \RuntimeException(
            "Generated client output mismatch for {$id}."
        );
    }
}
