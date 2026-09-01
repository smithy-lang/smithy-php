<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Endpoint\Bytecode;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\ClientConfig;
use Smithy\Client\Endpoint\EndpointResolverInterface;
use Smithy\Client\Endpoint\Bytecode\{
    Bytecode,
    BytecodeEndpointResolver,
    StdFunctions
};
use Smithy\Client\Endpoint\Bytecode\Compiler\BddCompiler;
use Smithy\Client\Endpoint\{
    Endpoint,
    EndpointResolverParams
};
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\Http\Client\HttpClientInterface;
use Smithy\Protocol\ProtocolInterface;
use Smithy\Tests\Client\Endpoint\Bytecode\Fixture\OpcodeHandlerFixture;

/**
 * Cross-instance behaviour of the process-wide L2 cache introduced when
 * the resolver's L2 map became `static`.
 *
 * The tests verify three properties:
 *
 *   1. Two resolvers built from the same bytecode and static context
 *      hit each other's L2 entries.
 *   2. Two resolvers built from the same bytecode but different static
 *      context do not hit each other's L2 entries (namespace prefixes
 *      isolate them).
 *   3. Two resolvers built from different bytecodes do not hit each
 *      other's L2 entries.
 *
 * A tiny hand-crafted `endpointBdd`-shaped trait powers the tests
 * without touching any provider-specific ruleset.
 */
#[CoversClass(BytecodeEndpointResolver::class)]
final class BytecodeEndpointResolverSharedCacheTest extends TestCase
{
    /**
     * Reset the shared cache between cases so state from one test
     * doesn't leak into another. The reflection dance is one-time and
     * keeps the property `protected` (external code has no reason to
     * touch it).
     */
    protected function setUp(): void
    {
        $ref = new \ReflectionClass(BytecodeEndpointResolver::class);
        $prop = $ref->getProperty('sharedCache');
        $prop->setValue(null, []);
    }

    public function testSameBytecodeSameStaticContextShareCache(): void
    {
        $bytecode = $this->compileTrivialBytecode();

        $a = new BytecodeEndpointResolver($bytecode, staticContext: ['Foo' => 'bar']);
        $b = new BytecodeEndpointResolver($bytecode, staticContext: ['Foo' => 'bar']);

        // Prime `$a`'s cache.
        $endpointA = $a->resolveEndpoint($this->makeParams(['Region' => 'us-east-1']));

        // Force `$b` past its per-instance L1 slot so the L2 lookup
        // fires. A single call is enough because `$b->lastKey` is still
        // the empty sentinel.
        $endpointB = $b->resolveEndpoint($this->makeParams(['Region' => 'us-east-1']));

        // Same underlying `Endpoint` object - the L2 hit returned
        // `$a`'s memoized instance rather than running the evaluator
        // twice.
        $this->assertSame($endpointA, $endpointB);
        $this->assertSame(1, $this->countSharedCacheEntries());
    }

    public function testSameBytecodeDifferentStaticContextIsolated(): void
    {
        $bytecode = $this->compileTrivialBytecode();

        $a = new BytecodeEndpointResolver($bytecode, staticContext: ['Foo' => 'one']);
        $b = new BytecodeEndpointResolver($bytecode, staticContext: ['Foo' => 'two']);

        $a->resolveEndpoint($this->makeParams(['Region' => 'us-east-1']));
        $b->resolveEndpoint($this->makeParams(['Region' => 'us-east-1']));

        // Two entries land in the shared cache because the namespace
        // prefix differs.
        $this->assertSame(2, $this->countSharedCacheEntries());
    }

    public function testDifferentBytecodesIsolated(): void
    {
        $bytecodeA = $this->compileTrivialBytecode();
        $bytecodeB = $this->compileTrivialBytecode();

        $a = new BytecodeEndpointResolver($bytecodeA);
        $b = new BytecodeEndpointResolver($bytecodeB);

        $a->resolveEndpoint($this->makeParams(['Region' => 'us-east-1']));
        $b->resolveEndpoint($this->makeParams(['Region' => 'us-east-1']));

        $this->assertSame(2, $this->countSharedCacheEntries());
    }

    public function testStaleSharedCacheEntryWithReusedNamespaceIsRejected(): void
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
            $this->makeParams(['Region' => 'us-east-1'])
        );

        $this->assertSame('https://example.com', $endpoint->url);
        $this->assertSame(1, $this->countSharedCacheEntries());
    }

    public function testL2DisabledSkipsSharedCacheWrite(): void
    {
        $bytecode = $this->compileTrivialBytecode();
        $resolver = new BytecodeEndpointResolver($bytecode, cacheSize: 0);

        $resolver->resolveEndpoint($this->makeParams(['Region' => 'us-east-1']));

        // No L2 write when the cap is zero. The L1 slot still holds a
        // reference. That's fine.
        $this->assertSame(0, $this->countSharedCacheEntries());
    }

    public function testL2DisabledSkipsSharedCacheRead(): void
    {
        $bytecode = $this->compileTrivialBytecode();
        $enabled = new BytecodeEndpointResolver($bytecode);
        $disabled = new BytecodeEndpointResolver($bytecode, cacheSize: 0);

        $cached = $enabled->resolveEndpoint(
            $this->makeParams(['Region' => 'us-east-1'])
        );
        $resolved = $disabled->resolveEndpoint(
            $this->makeParams(['Region' => 'us-east-1'])
        );

        $this->assertNotSame($cached, $resolved);
        $this->assertSame('https://example.com', $resolved->url);
    }

    public function testExtractsMappedEndpointAuthSchemes(): void
    {
        $bytecode = $this->compileTrivialBytecode([
            'authSchemes' => [
                [
                    'name' => 'custom',
                    'signingName' => 'widgets',
                    'signingRegion' => 'us-east-1',
                ],
            ],
        ]);
        $resolver = new BytecodeEndpointResolver(
            $bytecode,
            endpointAuthSchemeIds: [
                'custom' => 'example.auth#custom',
            ]
        );

        $endpoint = $resolver->resolveEndpoint(
            $this->makeParams(['Region' => 'us-east-1'])
        );

        $this->assertCount(1, $endpoint->authSchemes);
        $this->assertSame(
            'example.auth#custom',
            $endpoint->authSchemes[0]->schemeId
        );
        $this->assertSame(
            [
                'signingName' => 'widgets',
                'signingRegion' => 'us-east-1',
            ],
            $endpoint->authSchemes[0]->properties
        );
    }

    public function testDifferentEndpointAuthMappingsAreCacheIsolated(): void
    {
        $bytecode = $this->compileTrivialBytecode([
            'authSchemes' => [['name' => 'custom']],
        ]);
        $a = new BytecodeEndpointResolver(
            $bytecode,
            endpointAuthSchemeIds: ['custom' => 'example.auth#one']
        );
        $b = new BytecodeEndpointResolver(
            $bytecode,
            endpointAuthSchemeIds: ['custom' => 'example.auth#two']
        );

        $endpointA = $a->resolveEndpoint(
            $this->makeParams(['Region' => 'us-east-1'])
        );
        $endpointB = $b->resolveEndpoint(
            $this->makeParams(['Region' => 'us-east-1'])
        );

        $this->assertNotSame($endpointA, $endpointB);
        $this->assertSame(
            'example.auth#one',
            $endpointA->authSchemes[0]->schemeId
        );
        $this->assertSame(
            'example.auth#two',
            $endpointB->authSchemes[0]->schemeId
        );
        $this->assertSame(2, $this->countSharedCacheEntries());
    }

    public function testDifferentKeyBuildersAreCacheIsolated(): void
    {
        $bytecode = $this->compileTrivialBytecode();
        $builderA = static fn(array $inputs): string => 'shared';
        $builderB = static fn(array $inputs): string => 'shared';
        $a = new BytecodeEndpointResolver(
            $bytecode,
            keyBuilder: $builderA
        );
        $b = new BytecodeEndpointResolver(
            $bytecode,
            keyBuilder: $builderB
        );

        $endpointA = $a->resolveEndpoint(
            $this->makeParams(['Region' => 'us-east-1'])
        );
        $endpointB = $b->resolveEndpoint(
            $this->makeParams(['Region' => 'us-east-1'])
        );

        $this->assertNotSame($endpointA, $endpointB);
        $this->assertSame(2, $this->countSharedCacheEntries());
    }

    public function testDifferentHandlersAreCacheIsolated(): void
    {
        $bytecode = $this->compileTrivialBytecode();
        $handlerA = new OpcodeHandlerFixture();
        $handlerB = new OpcodeHandlerFixture();
        $a = new BytecodeEndpointResolver(
            $bytecode,
            handler: $handlerA
        );
        $b = new BytecodeEndpointResolver(
            $bytecode,
            handler: $handlerB
        );

        $endpointA = $a->resolveEndpoint(
            $this->makeParams(['Region' => 'us-east-1'])
        );
        $endpointB = $b->resolveEndpoint(
            $this->makeParams(['Region' => 'us-east-1'])
        );

        $this->assertNotSame($endpointA, $endpointB);
        $this->assertSame(2, $this->countSharedCacheEntries());
    }

    public function testSmallCacheCapEvictsDownToConfiguredSize(): void
    {
        $large = new BytecodeEndpointResolver(
            $this->compileTrivialBytecode(),
            cacheSize: 4
        );
        foreach (['one', 'two', 'three', 'four'] as $region) {
            $large->resolveEndpoint($this->makeParams(['Region' => $region]));
        }
        $this->assertSame(4, $this->countSharedCacheEntries());

        $small = new BytecodeEndpointResolver(
            $this->compileTrivialBytecode(),
            cacheSize: 2
        );
        $small->resolveEndpoint($this->makeParams(['Region' => 'five']));

        $this->assertSame(2, $this->countSharedCacheEntries());
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
    #[\PHPUnit\Framework\Attributes\DataProvider('distinctInputMaps')]
    public function testCacheKeyEncodingDoesNotCollide(
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

        $this->assertNotSame(
            $method->invoke($resolver, $left),
            $method->invoke($resolver, $right)
        );
    }

    /**
     * @param array<string, mixed> $params
     */
    private function makeParams(array $params): EndpointResolverParams
    {
        $operation = $this->createStub(OperationInterface::class);
        $operation->method('getRuntimeIdentity')->willReturn(
            self::class . '::operation'
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
                protocol: $this->createStub(ProtocolInterface::class),
                httpClient: $this->createStub(HttpClientInterface::class),
                endpointResolver: $this->createStub(
                    EndpointResolverInterface::class
                ),
                context: [
                    EndpointResolverParams::CONTEXT_PARAMETERS => $params
                ]
            )
        );
    }

    /**
     * @param array<string, mixed> $properties
     */
    private function compileTrivialBytecode(
        array $properties = [],
        ?array $parameters = null
    ): Bytecode
    {
        // Minimal endpointBdd trait: no BDD walk needed. The root
        // reference points directly at the first defined result, so
        // the evaluator jumps straight to the result program without
        // touching the BDD node array.
        //
        // Result-ref numbering follows the SEP: `100_000_000` is the
        // implicit `NoMatchRule`, so the first serialized result is
        // `100_000_001`.
        //
        // We still include one BDD node in the payload so `nodeCount`
        // matches (BddCompiler validates the blob length against
        // `nodeCount * 12`). It's unreachable and its contents don't
        // matter.
        $nodeBytes = \pack('N*', 0, 1, -1);
        $trait = [
            'parameters' => $parameters ?? [
                'Region' => [
                    'type' => 'String',
                    'default' => 'us-east-1',
                ],
            ],
            'conditions' => [],
            'results' => [
                [
                    'type' => 'endpoint',
                    'endpoint' => [
                        'url' => 'https://example.com',
                        'properties' => $properties,
                    ],
                ],
            ],
            'nodes' => \base64_encode($nodeBytes),
            'nodeCount' => 1,
            'root' => 100_000_001,
        ];

        return (new BddCompiler(StdFunctions::all()))->compile($trait);
    }

    private function countSharedCacheEntries(): int
    {
        $ref = new \ReflectionClass(BytecodeEndpointResolver::class);
        $prop = $ref->getProperty('sharedCache');
        return \count($prop->getValue());
    }
}
