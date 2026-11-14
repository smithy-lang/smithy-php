<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Benchmarks\Serde;

use PHPUnit\Framework\TestCase;
use Smithy\Benchmarks\Serde\Support\BenchmarkCaseRepository;
use Smithy\Benchmarks\Serde\Support\BenchmarkValueComparator;
use Smithy\Benchmarks\Serde\Support\GeneratedCodeLoader;
use Smithy\Benchmarks\Serde\Support\FixedResponseHttpClient;
use Smithy\Benchmarks\Serde\Support\PipelineBenchmarkInterceptor;
use Smithy\Benchmarks\Serde\Support\PipelineBenchmarkPlugin;
use Smithy\Client\Auth\{
    NoAuthScheme,
    StaticAuthSchemeResolver
};
use Smithy\Client\ClientConfig;
use Smithy\Client\Endpoint\StaticEndpointResolver;
use Smithy\Client\Plugin\{
    PluginCollection,
    PluginInterface
};
use Smithy\Codegen\CodeGenerator;
use Smithy\Codegen\CodegenSettings;
use Smithy\Codegen\Model\SmithyModel;
use Smithy\Core\Endpoint\Endpoint;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Core\Shape\Utils\ArrayShapeSerializer;
use Smithy\Http\Message\{
    HttpTransportRequest,
    HttpTransportResponse,
    Response
};
use Smithy\Protocol\RpcV2\{
    RpcV2CborProtocol,
    RpcV2JsonProtocol
};

require_once __DIR__
    . '/../../../benchmarks/serde/support/BenchmarkCaseRepository.php';
require_once __DIR__
    . '/../../../benchmarks/serde/support/BenchmarkValueComparator.php';
require_once __DIR__
    . '/../../../benchmarks/serde/support/GeneratedCodeLoader.php';
require_once __DIR__
    . '/../../../benchmarks/serde/support/FixedResponseHttpClient.php';
require_once __DIR__
    . '/../../../benchmarks/serde/support/PipelineBenchmarkPlugin.php';

final class BenchmarkSmokeTest extends TestCase
{
    private string $outputDirectory;

    public function testGeneratesAndRunsRpcV2CborSerdeCases(): void
    {
        $modelPath = __DIR__
            . '/../../../benchmarks/serde/model/rpcv2-cbor.json';
        (new CodeGenerator(
            new CodegenSettings(
                namespace: 'Smithy\\BenchmarkSmoke',
                outputDir: $this->outputDirectory,
                organization: 'categorized',
                preserveExistingMemberOrder: false,
                generateClient: true,
                generateClientConfig: false
            ),
            SmithyModel::fromFile($modelPath)
        ))->generateAndWrite();

        $loader = new GeneratedCodeLoader(
            'Smithy\\BenchmarkSmoke',
            $this->outputDirectory
        );
        $loader->register();
        $repository = new BenchmarkCaseRepository($modelPath);
        $protocol = new RpcV2CborProtocol();

        $requestCase = $repository->requests(
            'PutItemRequest_Baseline'
        )[0];
        $requestOperation = $this->operation($loader, $requestCase);
        $input = ArrayShapeSerializer::fromArray(
            $requestOperation::INPUT_CLASS,
            $requestCase['params']
        );
        $request = $protocol->serializeRequest(
            $requestOperation,
            $input
        );
        $request = HttpTransportRequest::expect($request)->request;

        self::assertNotSame('', (string) $request->getBody());
        self::assertSame(
            'rpc-v2-cbor',
            $request->getHeaderLine('Smithy-Protocol')
        );

        $responseCase = $repository->responses(
            'GetItemOutput_Baseline'
        )[0];
        $responseOperation = $this->operation($loader, $responseCase);
        $output = $protocol->deserializeResponse(
            $responseOperation,
            new HttpTransportResponse(new Response(
                status: $responseCase['code'],
                headers: $responseCase['headers'],
                body: \base64_decode($responseCase['body'], true)
            ))
        );

        self::assertInstanceOf(
            $responseOperation::OUTPUT_CLASS,
            $output
        );

        $http = new FixedResponseHttpClient(new Response(
            status: $responseCase['code'],
            headers: $responseCase['headers'],
            body: \base64_decode($responseCase['body'], true)
        ));
        $config = new ClientConfig(
            protocol: $protocol,
            transport: new \Smithy\Http\Client\HttpClientTransport($http),
            endpointResolver: new StaticEndpointResolver(
                new Endpoint('http://localhost')
            ),
            supportedAuthSchemes: [NoAuthScheme::instance()],
            authSchemeResolver: StaticAuthSchemeResolver::noAuth()
        );
        $config = $config->withPluginPredicate(
            static fn(PluginInterface $plugin): bool =>
                $plugin instanceof PipelineBenchmarkPlugin
        );
        $clientClass = 'Smithy\\BenchmarkSmoke\\BenchmarkSmokeClient';
        $fastPathClient = new $clientClass($config);
        self::assertTrue(
            $fastPathClient->getConfig()->interceptorChain->isEmpty()
        );
        $client = new $clientClass(
            $config,
            new PluginCollection(new PipelineBenchmarkPlugin())
        );
        self::assertTrue(
            $client->getConfig()->interceptorChain->contains(
                PipelineBenchmarkInterceptor::class
            )
        );

        $pipelineRequestCase = $repository->requests(
            'WideTypesRequest_S'
        )[0];
        $pipelineResponseCase = $repository->responses(
            'WideTypesResponse_S'
        )[0];
        $pipelineHttp = new FixedResponseHttpClient(new Response(
            status: $pipelineResponseCase['code'],
            headers: $pipelineResponseCase['headers'],
            body: \base64_decode($pipelineResponseCase['body'], true)
        ));
        $pipelineClient = new $clientClass(
            $config->withTransport(
                new \Smithy\Http\Client\HttpClientTransport($pipelineHttp)
            )
        );
        $pipelineInput = ArrayShapeSerializer::fromArray(
            'Smithy\\BenchmarkSmoke\\Input\\WideTypesInput',
            $pipelineRequestCase['params']
        );
        $pipelineOutput = $pipelineClient->wideTypes($pipelineInput);
        self::assertInstanceOf(
            'Smithy\\BenchmarkSmoke\\Output\\WideTypesOutput',
            $pipelineOutput
        );

        $loader->unregister();
    }

    public function testGeneratesAndRunsRpcV2JsonSerdeCases(): void
    {
        $modelPath = __DIR__
            . '/../../../benchmarks/serde/model/rpcv2-json.json';
        (new CodeGenerator(
            new CodegenSettings(
                namespace: 'Smithy\\BenchmarkJsonSmoke',
                outputDir: $this->outputDirectory,
                organization: 'categorized',
                preserveExistingMemberOrder: false,
                generateClient: true,
                generateClientConfig: false
            ),
            SmithyModel::fromFile($modelPath)
        ))->generateAndWrite();

        $loader = new GeneratedCodeLoader(
            'Smithy\\BenchmarkJsonSmoke',
            $this->outputDirectory
        );
        $loader->register();
        $repository = new BenchmarkCaseRepository(
            $modelPath,
            RpcV2JsonProtocol::ID,
            'rpcv2Json'
        );
        $protocol = new RpcV2JsonProtocol();

        $requestCase = $repository->requests(
            'WideTypesRequest_S'
        )[0];
        $requestOperation = $this->operation($loader, $requestCase);
        $input = ArrayShapeSerializer::fromArray(
            $requestOperation::INPUT_CLASS,
            $requestCase['params']
        );
        $request = $protocol->serializeRequest(
            $requestOperation,
            $input
        );
        $request = HttpTransportRequest::expect($request)->request;
        $body = \json_decode(
            (string) $request->getBody(),
            true,
            512,
            \JSON_THROW_ON_ERROR
        );

        self::assertSame(
            'rpc-v2-json',
            $request->getHeaderLine('Smithy-Protocol')
        );
        self::assertSame(
            '123456789012345678901',
            $body['bigIntegerA']
        );

        $responseCase = $repository->responses(
            'WideTypesResponse_S'
        )[0];
        $responseOperation = $this->operation($loader, $responseCase);
        $output = $protocol->deserializeResponse(
            $responseOperation,
            new HttpTransportResponse(new Response(
                status: $responseCase['code'],
                headers: $responseCase['headers'],
                body: $responseCase['body']
            ))
        );
        $expected = ArrayShapeSerializer::fromArray(
            $responseOperation::OUTPUT_CLASS,
            $responseCase['params']
        );

        self::assertTrue(
            BenchmarkValueComparator::equivalent(
                ArrayShapeSerializer::toArray($expected),
                ArrayShapeSerializer::toArray($output)
            )
        );

        $loader->unregister();
    }

    protected function setUp(): void
    {
        $this->outputDirectory = \sys_get_temp_dir()
            . '/smithy-php-benchmark-smoke-'
            . \bin2hex(\random_bytes(8));
    }

    protected function tearDown(): void
    {
        if (!\is_dir($this->outputDirectory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $this->outputDirectory,
                \FilesystemIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $entry) {
            $entry->isDir()
                ? \rmdir($entry->getPathname())
                : \unlink($entry->getPathname());
        }
        \rmdir($this->outputDirectory);
    }

    /**
     * @param array<string, mixed> $case
     */
    private function operation(
        GeneratedCodeLoader $loader,
        array $case
    ): OperationInterface {
        $class = $loader->operationClass($case['operation']);

        return $class::instance();
    }
}
