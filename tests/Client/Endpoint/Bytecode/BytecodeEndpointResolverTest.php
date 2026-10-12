<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Endpoint\Bytecode;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Client\ClientConfig;
use Smithy\Client\Endpoint\Endpoint;
use Smithy\Client\Endpoint\EndpointResolverInterface;
use Smithy\Client\Endpoint\Bytecode\{
    Bytecode,
    BytecodeEndpointResolver,
    Compiler\BddCompiler,
    StdFunctions
};
use Smithy\Client\Endpoint\EndpointResolverParams;
use Smithy\Core\Serde\{
    DynamicTraitValue,
    Schema,
    SerializableStructInterface
};
use Smithy\Core\Shape\Document\StructDocument;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\Http\Client\HttpClientInterface;
use Smithy\Tests\Client\Endpoint\Bytecode\Fixture\OpcodeHandlerFixture;
use Smithy\Tests\Client\Fake\FakeProtocol;

#[CoversClass(BytecodeEndpointResolver::class)]
final class BytecodeEndpointResolverTest extends TestCase
{
    private const CONTEXT_PARAM_TRAIT =
        'smithy.rules#contextParam';
    private const OPERATION_CONTEXT_PARAMS_TRAIT =
        'smithy.rules#operationContextParams';
    private const STATIC_CONTEXT_PARAMS_TRAIT =
        'smithy.rules#staticContextParams';

    /**
     * Reset the process-wide endpoint cache.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $reflection = new \ReflectionClass(
            BytecodeEndpointResolver::class
        );
        $reflection->getProperty('sharedCache')->setValue(null, []);
    }

    public function testResolvesInputMemberContext(): void
    {
        $inputSchema = $this->inputSchema([
            self::CONTEXT_PARAM_TRAIT => new DynamicTraitValue(
                self::CONTEXT_PARAM_TRAIT,
                ['name' => 'Host']
            )
        ]);
        $operation = $this->operation($inputSchema);
        $input = StructDocument::fromMembers(
            $inputSchema,
            ['input.example']
        );
        $resolver = $this->resolver();

        $endpoint = $resolver->resolveEndpoint(
            $this->params($resolver, $operation, $input)
        );

        self::assertSame('https://input.example', $endpoint->url);
    }

    public function testResolvesOperationContextPath(): void
    {
        $inputSchema = $this->inputSchema();
        $operation = $this->operation(
            $inputSchema,
            [
                self::OPERATION_CONTEXT_PARAMS_TRAIT =>
                    new DynamicTraitValue(
                        self::OPERATION_CONTEXT_PARAMS_TRAIT,
                        ['Host' => ['path' => 'host']]
                    )
            ]
        );
        $input = StructDocument::fromMembers(
            $inputSchema,
            ['path.example']
        );
        $resolver = $this->resolver();

        $endpoint = $resolver->resolveEndpoint(
            $this->params($resolver, $operation, $input)
        );

        self::assertSame('https://path.example', $endpoint->url);
    }

    public function testInputMemberContextOverridesOperationContextPath(): void
    {
        $inputSchema = $this->inputSchema([
            self::CONTEXT_PARAM_TRAIT => ['name' => 'Host']
        ]);
        $operation = $this->operation(
            $inputSchema,
            [
                self::OPERATION_CONTEXT_PARAMS_TRAIT => [
                    'Host' => ['path' => 'host']
                ]
            ]
        );
        $input = StructDocument::fromMembers(
            $inputSchema,
            ['input.example']
        );
        $resolver = $this->resolver();

        $endpoint = $resolver->resolveEndpoint(
            $this->params($resolver, $operation, $input)
        );

        self::assertSame('https://input.example', $endpoint->url);
    }


    public function testOperationStaticContextOverridesInputContext(): void
    {
        $inputSchema = $this->inputSchema([
            self::CONTEXT_PARAM_TRAIT => ['name' => 'Host']
        ]);
        $operation = $this->operation(
            $inputSchema,
            [
                self::STATIC_CONTEXT_PARAMS_TRAIT => [
                    'Host' => ['value' => 'operation.example']
                ]
            ]
        );
        $input = StructDocument::fromMembers(
            $inputSchema,
            ['input.example']
        );
        $resolver = $this->resolver();

        $endpoint = $resolver->resolveEndpoint(
            $this->params($resolver, $operation, $input)
        );

        self::assertSame('https://operation.example', $endpoint->url);
    }

    public function testOperationStaticContextOverridesResolverContext(): void
    {
        $inputSchema = $this->inputSchema();
        $operation = $this->operation(
            $inputSchema,
            [
                self::STATIC_CONTEXT_PARAMS_TRAIT => [
                    'Host' => ['value' => 'operation.example']
                ]
            ]
        );
        $input = StructDocument::fromMembers(
            $inputSchema,
            ['input.example']
        );
        $resolver = $this->resolver([
            'Host' => 'resolver.example'
        ]);

        $endpoint = $resolver->resolveEndpoint(
            $this->params($resolver, $operation, $input)
        );

        self::assertSame('https://operation.example', $endpoint->url);
    }

    public function testConfiguredParametersOverrideResolverContext(): void
    {
        $inputSchema = $this->inputSchema();
        $operation = $this->operation($inputSchema);
        $input = StructDocument::fromMembers(
            $inputSchema,
            ['input.example']
        );
        $resolver = $this->resolver([
            'Host' => 'resolver.example'
        ]);

        $endpoint = $resolver->resolveEndpoint(
            $this->params(
                $resolver,
                $operation,
                $input,
                ['Host' => 'configured.example']
            )
        );

        self::assertSame('https://configured.example', $endpoint->url);
    }

    public function testConfiguredParametersOverrideOperationContext(): void
    {
        $inputSchema = $this->inputSchema();
        $operation = $this->operation(
            $inputSchema,
            [
                self::STATIC_CONTEXT_PARAMS_TRAIT => [
                    'Host' => ['value' => 'operation.example']
                ]
            ]
        );
        $input = StructDocument::fromMembers(
            $inputSchema,
            ['input.example']
        );
        $resolver = $this->resolver();

        $endpoint = $resolver->resolveEndpoint(
            $this->params(
                $resolver,
                $operation,
                $input,
                ['Host' => 'configured.example']
            )
        );

        self::assertSame('https://configured.example', $endpoint->url);
    }

    public function testOmitsNullInputContextValue(): void
    {
        $inputSchema = $this->inputSchema([
            self::CONTEXT_PARAM_TRAIT => ['name' => 'Host']
        ]);
        $operation = $this->operation($inputSchema);
        $input = StructDocument::fromMembers(
            $inputSchema,
            [null]
        );
        $resolver = $this->resolver();

        $endpoint = $resolver->resolveEndpoint(
            $this->params($resolver, $operation, $input)
        );

        self::assertSame('https://default.example', $endpoint->url);
    }

    public function testRejectsNullRequiredInputContextValue(): void
    {
        $inputSchema = $this->inputSchema(
            [self::CONTEXT_PARAM_TRAIT => ['name' => 'Host']],
            [Schema::T_REQUIRED => true]
        );
        $operation = $this->operation($inputSchema);
        $input = StructDocument::fromMembers(
            $inputSchema,
            [null]
        );
        $resolver = $this->resolver();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Required endpoint context parameter Host must not be empty.'
        );

        $resolver->resolveEndpoint(
            $this->params($resolver, $operation, $input)
        );
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function emptyRequiredInputContextValueProvider(): iterable
    {
        yield 'empty string' => [''];
        yield 'whitespace string' => ['   '];
        yield 'empty list' => [[]];
    }

    #[DataProvider('emptyRequiredInputContextValueProvider')]
    public function testRejectsEmptyRequiredInputContextValue(mixed $value): void
    {
        $inputSchema = $this->inputSchema(
            [self::CONTEXT_PARAM_TRAIT => ['name' => 'Host']],
            [Schema::T_REQUIRED => true]
        );
        $operation = $this->operation($inputSchema);
        $input = StructDocument::fromMembers($inputSchema, [$value]);
        $resolver = $this->resolver();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Required endpoint context parameter Host must not be empty.'
        );

        $resolver->resolveEndpoint(
            $this->params($resolver, $operation, $input)
        );
    }

    public function testIgnoresNonStringConfiguredParameterKeys(): void
    {
        $inputSchema = $this->inputSchema();
        $operation = $this->operation($inputSchema);
        $input = StructDocument::fromMembers(
            $inputSchema,
            ['input.example']
        );
        $resolver = $this->resolver();

        $endpoint = $resolver->resolveEndpoint(
            $this->params(
                $resolver,
                $operation,
                $input,
                [0 => 'ignored', 'Host' => 'configured.example']
            )
        );

        self::assertSame('https://configured.example', $endpoint->url);
    }

    public function testRejectsNonArrayConfiguredParameters(): void
    {
        $inputSchema = $this->inputSchema();
        $operation = $this->operation($inputSchema);
        $input = StructDocument::fromMembers(
            $inputSchema,
            ['input.example']
        );
        $resolver = $this->resolver();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Endpoint parameters must be an associative array.'
        );

        $resolver->resolveEndpoint(
            $this->params(
                $resolver,
                $operation,
                $input,
                'invalid'
            )
        );
    }

    public function testConsecutiveCallsUseL1CacheWhenL2IsDisabled(): void
    {
        $inputSchema = $this->inputSchema();
        $operation = $this->operation($inputSchema);
        $input = StructDocument::fromMembers(
            $inputSchema,
            ['input.example']
        );
        $resolver = $this->resolver(cacheSize: 0);
        $params = $this->params(
            $resolver,
            $operation,
            $input,
            ['Host' => 'cached.example']
        );

        $first = $resolver->resolveEndpoint($params);
        $second = $resolver->resolveEndpoint($params);

        self::assertSame($first, $second);
    }

    public function testEvictsOldestEndpointAtCacheLimit(): void
    {
        $inputSchema = $this->inputSchema();
        $operation = $this->operation($inputSchema);
        $input = StructDocument::fromMembers(
            $inputSchema,
            ['input.example']
        );
        $resolver = $this->resolver(cacheSize: 2);

        $first = $resolver->resolveEndpoint(
            $this->params(
                $resolver,
                $operation,
                $input,
                ['Host' => 'one.example']
            )
        );
        $resolver->resolveEndpoint(
            $this->params(
                $resolver,
                $operation,
                $input,
                ['Host' => 'two.example']
            )
        );
        $resolver->resolveEndpoint(
            $this->params(
                $resolver,
                $operation,
                $input,
                ['Host' => 'three.example']
            )
        );
        $resolvedAgain = $resolver->resolveEndpoint(
            $this->params(
                $resolver,
                $operation,
                $input,
                ['Host' => 'one.example']
            )
        );

        self::assertNotSame($first, $resolvedAgain);
        self::assertSame($first->url, $resolvedAgain->url);
    }

    public function testResolversShareCachedEndpoints(): void
    {
        $bytecode = $this->compileTrivialBytecode();
        $first = new BytecodeEndpointResolver(
            $bytecode,
            staticContext: ['Foo' => 'bar']
        );
        $second = new BytecodeEndpointResolver(
            $bytecode,
            staticContext: ['Foo' => 'bar']
        );
        $params = $this->sharedCacheParams(['Region' => 'us-east-1']);

        $firstEndpoint = $first->resolveEndpoint($params);
        $secondEndpoint = $second->resolveEndpoint($params);

        self::assertSame($firstEndpoint, $secondEndpoint);
        self::assertSame(1, $this->countSharedCacheEntries());
    }

    public function testDifferentStaticContextsAreCacheIsolated(): void
    {
        $bytecode = $this->compileTrivialBytecode();
        $first = new BytecodeEndpointResolver(
            $bytecode,
            staticContext: ['Foo' => 'one']
        );
        $second = new BytecodeEndpointResolver(
            $bytecode,
            staticContext: ['Foo' => 'two']
        );
        $params = $this->sharedCacheParams(['Region' => 'us-east-1']);

        $first->resolveEndpoint($params);
        $second->resolveEndpoint($params);

        self::assertSame(2, $this->countSharedCacheEntries());
    }

    public function testDifferentBytecodesAreCacheIsolated(): void
    {
        $first = new BytecodeEndpointResolver(
            $this->compileTrivialBytecode()
        );
        $second = new BytecodeEndpointResolver(
            $this->compileTrivialBytecode()
        );
        $params = $this->sharedCacheParams(['Region' => 'us-east-1']);

        $first->resolveEndpoint($params);
        $second->resolveEndpoint($params);

        self::assertSame(2, $this->countSharedCacheEntries());
    }

    public function testRejectsStaleSharedCacheEntry(): void
    {
        $bytecode = $this->compileTrivialBytecode();
        $resolver = new BytecodeEndpointResolver($bytecode);
        $reflector = new \ReflectionClass($resolver);
        $namespace = $reflector->getProperty('resolverNamespace')
            ->getValue($resolver);
        $key = $reflector->getMethod('buildCacheKey')->invoke(
            $resolver,
            ['Region' => 'us-east-1']
        );
        $sharedCache = new \ReflectionProperty(
            BytecodeEndpointResolver::class,
            'sharedCache'
        );
        $sharedCache->setValue(null, [
            $namespace . $key => [
                $this->compileTrivialBytecode(),
                [],
                null,
                null,
                [],
                new Endpoint('https://stale.example')
            ]
        ]);

        $endpoint = $resolver->resolveEndpoint(
            $this->sharedCacheParams(['Region' => 'us-east-1'])
        );

        self::assertSame('https://example.com', $endpoint->url);
        self::assertSame(1, $this->countSharedCacheEntries());
    }

    public function testDisabledSharedCacheSkipsWrites(): void
    {
        $resolver = new BytecodeEndpointResolver(
            $this->compileTrivialBytecode(),
            cacheSize: 0
        );

        $resolver->resolveEndpoint(
            $this->sharedCacheParams(['Region' => 'us-east-1'])
        );

        self::assertSame(0, $this->countSharedCacheEntries());
    }

    public function testDisabledSharedCacheSkipsReads(): void
    {
        $bytecode = $this->compileTrivialBytecode();
        $enabled = new BytecodeEndpointResolver($bytecode);
        $disabled = new BytecodeEndpointResolver($bytecode, cacheSize: 0);
        $params = $this->sharedCacheParams(['Region' => 'us-east-1']);

        $cached = $enabled->resolveEndpoint($params);
        $resolved = $disabled->resolveEndpoint($params);

        self::assertNotSame($cached, $resolved);
        self::assertSame('https://example.com', $resolved->url);
    }

    public function testExtractsMappedEndpointAuthSchemes(): void
    {
        $bytecode = $this->compileTrivialBytecode([
            'authSchemes' => [
                [
                    'name' => 'custom',
                    'signingName' => 'widgets',
                    'signingRegion' => 'us-east-1'
                ]
            ]
        ]);
        $resolver = new BytecodeEndpointResolver(
            $bytecode,
            endpointAuthSchemeIds: [
                'custom' => 'example.auth#custom'
            ]
        );

        $endpoint = $resolver->resolveEndpoint(
            $this->sharedCacheParams(['Region' => 'us-east-1'])
        );

        self::assertCount(1, $endpoint->authSchemes);
        self::assertSame(
            'example.auth#custom',
            $endpoint->authSchemes[0]->schemeId
        );
        self::assertSame(
            [
                'signingName' => 'widgets',
                'signingRegion' => 'us-east-1'
            ],
            $endpoint->authSchemes[0]->properties
        );
    }

    public function testAuthMappingsAreCacheIsolated(): void
    {
        $bytecode = $this->compileTrivialBytecode([
            'authSchemes' => [['name' => 'custom']]
        ]);
        $first = new BytecodeEndpointResolver(
            $bytecode,
            endpointAuthSchemeIds: ['custom' => 'example.auth#one']
        );
        $second = new BytecodeEndpointResolver(
            $bytecode,
            endpointAuthSchemeIds: ['custom' => 'example.auth#two']
        );
        $params = $this->sharedCacheParams(['Region' => 'us-east-1']);

        $firstEndpoint = $first->resolveEndpoint($params);
        $secondEndpoint = $second->resolveEndpoint($params);

        self::assertNotSame($firstEndpoint, $secondEndpoint);
        self::assertSame(
            'example.auth#one',
            $firstEndpoint->authSchemes[0]->schemeId
        );
        self::assertSame(
            'example.auth#two',
            $secondEndpoint->authSchemes[0]->schemeId
        );
    }

    public function testKeyBuildersAreCacheIsolated(): void
    {
        $bytecode = $this->compileTrivialBytecode();
        $first = new BytecodeEndpointResolver(
            $bytecode,
            keyBuilder: static fn(array $inputs): string => 'shared'
        );
        $second = new BytecodeEndpointResolver(
            $bytecode,
            keyBuilder: static fn(array $inputs): string => 'shared'
        );
        $params = $this->sharedCacheParams(['Region' => 'us-east-1']);

        $firstEndpoint = $first->resolveEndpoint($params);
        $secondEndpoint = $second->resolveEndpoint($params);

        self::assertNotSame($firstEndpoint, $secondEndpoint);
        self::assertSame(2, $this->countSharedCacheEntries());
    }

    public function testOpcodeHandlersAreCacheIsolated(): void
    {
        $bytecode = $this->compileTrivialBytecode();
        $first = new BytecodeEndpointResolver(
            $bytecode,
            handler: new OpcodeHandlerFixture()
        );
        $second = new BytecodeEndpointResolver(
            $bytecode,
            handler: new OpcodeHandlerFixture()
        );
        $params = $this->sharedCacheParams(['Region' => 'us-east-1']);

        $firstEndpoint = $first->resolveEndpoint($params);
        $secondEndpoint = $second->resolveEndpoint($params);

        self::assertNotSame($firstEndpoint, $secondEndpoint);
        self::assertSame(2, $this->countSharedCacheEntries());
    }

    public function testSmallerCacheLimitEvictsSharedEntries(): void
    {
        $large = new BytecodeEndpointResolver(
            $this->compileTrivialBytecode(),
            cacheSize: 4
        );
        foreach (['one', 'two', 'three', 'four'] as $region) {
            $large->resolveEndpoint(
                $this->sharedCacheParams(['Region' => $region])
            );
        }
        self::assertSame(4, $this->countSharedCacheEntries());

        $small = new BytecodeEndpointResolver(
            $this->compileTrivialBytecode(),
            cacheSize: 2
        );
        $small->resolveEndpoint(
            $this->sharedCacheParams(['Region' => 'five'])
        );

        self::assertSame(2, $this->countSharedCacheEntries());
    }

    /**
     * @return iterable<string, array{
     *     array<string, mixed>,
     *     array<string, mixed>
     * }>
     */
    public static function distinctInputMaps(): iterable
    {
        yield 'embedded separator' => [
            ['Alpha' => "a\x1Fb", 'Beta' => 'c'],
            ['Alpha' => 'a', 'Beta' => "b\x1Fc"]
        ];
        yield 'missing versus null' => [
            [],
            ['Alpha' => null]
        ];
        yield 'boolean versus control byte' => [
            ['Alpha' => false],
            ['Alpha' => "\x02"]
        ];
        yield 'integer versus string' => [
            ['Alpha' => 12],
            ['Alpha' => '12']
        ];
        yield 'serialized arrays' => [
            ['Alpha' => ['value' => 1]],
            ['Alpha' => ['value' => 2]]
        ];
    }

    /**
     * @param array<string, mixed> $left
     * @param array<string, mixed> $right
     */
    #[DataProvider('distinctInputMaps')]
    public function testCacheKeysDoNotCollide(
        array $left,
        array $right
    ): void
    {
        $resolver = new BytecodeEndpointResolver(
            $this->compileTrivialBytecode(
                parameters: [
                    'Alpha' => ['type' => 'String'],
                    'Beta' => ['type' => 'String']
                ]
            )
        );
        $method = new \ReflectionMethod($resolver, 'buildCacheKey');

        self::assertNotSame(
            $method->invoke($resolver, $left),
            $method->invoke($resolver, $right)
        );
    }

    /**
     * @return iterable<string, array{array<mixed, mixed>}>
     */
    public static function invalidAuthSchemeMappings(): iterable
    {
        yield 'empty name' => [['' => 'example.auth#custom']];
        yield 'empty shape ID' => [['custom' => '']];
        yield 'non-string name' => [[0 => 'example.auth#custom']];
        yield 'non-string shape ID' => [['custom' => 1]];
    }

    /**
     * @param array<mixed, mixed> $mapping Invalid auth scheme mapping.
     */
    #[DataProvider('invalidAuthSchemeMappings')]
    public function testRejectsInvalidAuthSchemeMapping(
        array $mapping
    ): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Endpoint auth scheme mappings must contain non-empty names '
                . 'mapped to non-empty Smithy shape IDs.'
        );

        new BytecodeEndpointResolver(
            $this->bytecode(),
            endpointAuthSchemeIds: $mapping
        );
    }

    /**
     * @param array<string, mixed> $directTraitsById
     * @param array<int, mixed>    $memberTraits
     */
    private function inputSchema(
        array $directTraitsById = [],
        array $memberTraits = []
    ): Schema
    {
        return new Schema(
            id: 'example#Input',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.api#String',
                    type: ShapeType::String_,
                    memberName: 'host',
                    memberIndex: 0,
                    traits: $memberTraits,
                    directTraitsById: $directTraitsById
                )
            ]
        );
    }

    /**
     * @param array<string, mixed> $traitsById
     */
    private function operation(
        Schema $inputSchema,
        array $traitsById = []
    ): OperationInterface
    {
        $operation = $this->createStub(OperationInterface::class);
        $operation->method('getRuntimeIdentity')->willReturn(
            self::class . '::operation'
        );
        $operation->method('getSchema')->willReturn(
            new Schema(
                id: 'example#Operation',
                type: ShapeType::Operation,
                traitsById: $traitsById
            )
        );
        $operation->method('getInputSchema')->willReturn(
            $inputSchema
        );

        return $operation;
    }

    /**
     * @param array<string, mixed> $staticContext
     */
    private function resolver(
        array $staticContext = [],
        ?int $cacheSize = null
    ): BytecodeEndpointResolver
    {
        return new BytecodeEndpointResolver(
            $this->bytecode(),
            staticContext: $staticContext,
            cacheSize: $cacheSize
        );
    }

    private function bytecode(): Bytecode
    {
        $nodeBytes = \pack('N*', 0, 1, -1);
        $trait = [
            'parameters' => [
                'Host' => [
                    'type' => 'String',
                    'default' => 'default.example'
                ]
            ],
            'conditions' => [],
            'results' => [
                [
                    'type' => 'endpoint',
                    'endpoint' => [
                        'url' => 'https://{Host}'
                    ]
                ]
            ],
            'nodes' => \base64_encode($nodeBytes),
            'nodeCount' => 1,
            'root' => Bytecode::RESULT_OFFSET + 1
        ];

        return (new BddCompiler(StdFunctions::all()))->compile(
            $trait
        );
    }

    /**
     * @param array<string, mixed> $properties
     * @param array<string, mixed>|null $parameters
     */
    private function compileTrivialBytecode(
        array $properties = [],
        ?array $parameters = null
    ): Bytecode
    {
        $nodeBytes = \pack('N*', 0, 1, -1);
        $trait = [
            'parameters' => $parameters ?? [
                'Region' => [
                    'type' => 'String',
                    'default' => 'us-east-1'
                ]
            ],
            'conditions' => [],
            'results' => [
                [
                    'type' => 'endpoint',
                    'endpoint' => [
                        'url' => 'https://example.com',
                        'properties' => $properties
                    ]
                ]
            ],
            'nodes' => \base64_encode($nodeBytes),
            'nodeCount' => 1,
            'root' => Bytecode::RESULT_OFFSET + 1
        ];

        return (new BddCompiler(StdFunctions::all()))->compile($trait);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function sharedCacheParams(
        array $parameters
    ): EndpointResolverParams
    {
        $operation = $this->createStub(OperationInterface::class);
        $operation->method('getRuntimeIdentity')->willReturn(
            self::class . '::shared-cache-operation'
        );
        $operation->method('getSchema')->willReturn(
            new Schema('example#Operation', ShapeType::Operation)
        );
        $operation->method('getInputSchema')->willReturn(
            new Schema('example#Input', ShapeType::Structure)
        );

        return new EndpointResolverParams(
            operation: $operation,
            input: $this->createStub(SerializableStructInterface::class),
            config: new ClientConfig(
                protocol: new FakeProtocol(),
                httpClient: $this->createStub(HttpClientInterface::class),
                endpointResolver: $this->createStub(
                    EndpointResolverInterface::class
                ),
                context: [
                    EndpointResolverParams::CONTEXT_PARAMETERS => $parameters
                ]
            )
        );
    }

    private function countSharedCacheEntries(): int
    {
        $reflection = new \ReflectionClass(
            BytecodeEndpointResolver::class
        );
        $cache = $reflection->getProperty('sharedCache')->getValue();

        return \count($cache);
    }

    private function params(
        BytecodeEndpointResolver $resolver,
        OperationInterface $operation,
        SerializableStructInterface $input,
        mixed $endpointParameters = []
    ): EndpointResolverParams
    {
        return new EndpointResolverParams(
            operation: $operation,
            input: $input,
            config: new ClientConfig(
                protocol: new FakeProtocol(),
                httpClient: $this->createStub(
                    HttpClientInterface::class
                ),
                endpointResolver: $resolver,
                context: [
                    EndpointResolverParams::CONTEXT_PARAMETERS =>
                        $endpointParameters
                ]
            )
        );
    }
}
