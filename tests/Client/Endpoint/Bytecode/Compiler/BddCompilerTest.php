<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Endpoint\Bytecode\Compiler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Endpoint\Bytecode\{
    Bytecode,
    Compiler\BddCompiler,
    Evaluator,
    Opcodes,
    RulesEvaluationException,
    StdFunctions
};
use Smithy\Tests\Client\Endpoint\Bytecode\Fixture\VariadicFunctionFixture;

#[CoversClass(BddCompiler::class)]
final class BddCompilerTest extends TestCase
{
    public function testDecodesSignedBddNodes(): void
    {
        $nodes = [
            -1,
            1,
            -1,
            0,
            Bytecode::RESULT_OFFSET,
            -2
        ];

        self::assertSame(
            $nodes,
            BddCompiler::decodeNodes($this->encodeNodes($nodes), 2)
        );
    }

    public function testRejectsInvalidNodeEncoding(): void
    {
        try {
            BddCompiler::decodeNodes('not base64!', 1);
            self::fail('Expected invalid base64 to be rejected.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame(
                'Invalid base64 BDD node blob',
                $exception->getMessage()
            );
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'BDD node blob size 12 bytes expected, got 4'
        );

        BddCompiler::decodeNodes(\base64_encode(\pack('N', 1)), 1);
    }

    public function testCompilesParametersAndFastConditions(): void
    {
        $trait = $this->trait(
            parameters: [
                'Region' => [
                    'type' => 'String',
                    'builtIn' => 'smithy.example#Region',
                    'default' => 'us-east-1',
                    'required' => true
                ],
                'UseFips' => [
                    'type' => 'Boolean',
                    'default' => false
                ]
            ],
            conditions: [
                [
                    'fn' => 'isSet',
                    'argv' => [['ref' => 'Region']]
                ],
                [
                    'fn' => 'booleanEquals',
                    'argv' => [['ref' => 'UseFips'], true]
                ],
                [
                    'fn' => 'stringEquals',
                    'argv' => [['ref' => 'Region'], 'us-east-1']
                ]
            ]
        );

        $bytecode = (new BddCompiler(StdFunctions::all()))
            ->compile($trait);

        self::assertSame(
            [
                [
                    'name' => 'Region',
                    'builtIn' => 'smithy.example#Region',
                    'default' => 'us-east-1',
                    'required' => true,
                    'type' => 'string'
                ],
                [
                    'name' => 'UseFips',
                    'builtIn' => null,
                    'default' => false,
                    'required' => false,
                    'type' => 'boolean'
                ]
            ],
            $bytecode->registers
        );
        self::assertSame(
            ['Region' => 0, 'UseFips' => 1],
            $bytecode->inputRegisterMap
        );
        self::assertSame(
            [
                Opcodes::COND_ISSET,
                Opcodes::COND_IS_TRUE,
                Opcodes::COND_STRING_EQ_REG_CONST
            ],
            $bytecode->conditionTypes
        );
        self::assertSame([0, 1, 0], $bytecode->conditionOperands);
        self::assertSame([0, 0, 0], $bytecode->conditionOffsets);
    }

    public function testCompilesEndpointResult(): void
    {
        $trait = $this->trait(
            parameters: [
                'Host' => ['type' => 'String'],
                'Metadata' => ['type' => 'Any']
            ],
            results: [
                [
                    'type' => 'endpoint',
                    'endpoint' => [
                        'url' => 'https://{{literal}}.{Host}'
                            . '/{Metadata#suffix}',
                        'headers' => [
                            'x-test' => ['one', '{Host}']
                        ],
                        'properties' => [
                            'authSchemes' => [
                                [
                                    'name' => 'sigv4',
                                    'signingName' => '{Metadata#auth#name}',
                                    'signingRegion' => '{Host}'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            root: Bytecode::RESULT_OFFSET
        );
        $bytecode = (new BddCompiler(StdFunctions::all()))
            ->compile($trait);

        $endpoint = (new Evaluator($bytecode))->evaluate([
            'Host' => 'example.test',
            'Metadata' => [
                'suffix' => 'path',
                'auth' => ['name' => 'service']
            ]
        ]);

        self::assertSame(
            'https://{literal}.example.test/path',
            $endpoint->url
        );
        self::assertSame(
            ['x-test' => 'one, example.test'],
            $endpoint->headers
        );
        self::assertCount(1, $endpoint->authSchemes);
        self::assertSame('sigv4', $endpoint->authSchemes[0]->schemeId);
        self::assertSame(
            [
                'signingName' => 'service',
                'signingRegion' => 'example.test'
            ],
            $endpoint->authSchemes[0]->properties
        );
        self::assertContains(Opcodes::RESOLVE_TEMPLATE, $bytecode->code);
    }

    public function testCompilesPlainTemplatesWithBuildTemplate(): void
    {
        $trait = $this->trait(
            parameters: [
                'Host' => ['type' => 'String']
            ],
            results: [
                [
                    'type' => 'endpoint',
                    'endpoint' => [
                        'url' => 'https://{Host}.example'
                    ]
                ]
            ],
            root: Bytecode::RESULT_OFFSET
        );
        $bytecode = (new BddCompiler(StdFunctions::all()))
            ->compile($trait);

        $endpoint = (new Evaluator($bytecode))->evaluate([
            'Host' => 'service'
        ]);

        self::assertSame('https://service.example', $endpoint->url);
        self::assertContains(Opcodes::BUILD_TEMPLATE, $bytecode->code);
        self::assertNotContains(Opcodes::RESOLVE_TEMPLATE, $bytecode->code);
    }

    public function testCompilesErrorResult(): void
    {
        $trait = $this->trait(
            parameters: [
                'Region' => ['type' => 'String']
            ],
            results: [
                [
                    'type' => 'error',
                    'error' => 'Missing endpoint for {Region}'
                ]
            ],
            root: Bytecode::RESULT_OFFSET
        );
        $bytecode = (new BddCompiler(StdFunctions::all()))
            ->compile($trait);

        $this->expectException(RulesEvaluationException::class);
        $this->expectExceptionMessage(
            'Missing endpoint for us-east-1'
        );

        (new Evaluator($bytecode))->evaluate([
            'Region' => 'us-east-1'
        ]);
    }

    public function testCompilesAssignedGetAttrCondition(): void
    {
        $nodes = [
            -1,
            1,
            -1,
            0,
            Bytecode::RESULT_OFFSET,
            -1
        ];
        $trait = $this->trait(
            parameters: [
                'Metadata' => ['type' => 'Any']
            ],
            conditions: [
                [
                    'fn' => 'getAttr',
                    'argv' => [
                        ['ref' => 'Metadata'],
                        'items[-1].suffix'
                    ],
                    'assign' => 'Suffix'
                ]
            ],
            results: [
                [
                    'type' => 'endpoint',
                    'endpoint' => [
                        'url' => 'https://{Suffix}.example'
                    ]
                ]
            ],
            nodes: $this->encodeNodes($nodes),
            nodeCount: 2,
            root: 2
        );
        $bytecode = (new BddCompiler(StdFunctions::all()))
            ->compile($trait);

        $endpoint = (new Evaluator($bytecode))->evaluate([
            'Metadata' => [
                'items' => [
                    ['suffix' => 'first'],
                    ['suffix' => 'last']
                ]
            ]
        ]);

        self::assertSame('https://last.example', $endpoint->url);
        self::assertSame(Opcodes::COND_FULL, $bytecode->conditionTypes[0]);
        self::assertSame('Suffix', $bytecode->registers[1]['name']);
        self::assertContains(
            Opcodes::GET_NEGATIVE_INDEX,
            $bytecode->code
        );
        self::assertContains(Opcodes::SET_REG_RETURN, $bytecode->code);
    }

    public function testCompilesExtendedOpcode(): void
    {
        $trait = $this->trait(
            conditions: [
                [
                    'fn' => 'vendor.function',
                    'argv' => ['value'],
                    'assign' => 'Result'
                ]
            ]
        );
        $bytecode = (new BddCompiler(
            StdFunctions::all(),
            ['vendor.function' => 200]
        ))->compile($trait);

        self::assertContains(200, $bytecode->code);
        self::assertContains(Opcodes::SET_REG_RETURN, $bytecode->code);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function invalidExtendedOpcodeProvider(): iterable
    {
        yield 'built-in range' => [55];
        yield 'byte overflow' => [256];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidExtendedOpcodeProvider')]
    public function testRejectsInvalidExtendedOpcode(int $opcode): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Extended opcode for vendor.function must be between 56 and 255'
        );

        new BddCompiler(
            StdFunctions::all(),
            ['vendor.function' => $opcode]
        );
    }

    public function testCompilesVariadicFunctionToVariadicOpcode(): void
    {
        $trait = $this->trait(
            conditions: [
                [
                    'fn' => 'vendor.variadic',
                    'argv' => ['a', 'b'],
                    'assign' => 'Result'
                ]
            ]
        );

        $bytecode = (new BddCompiler([
            'vendor.variadic' => new VariadicFunctionFixture()
        ]))->compile($trait);

        self::assertContains(Opcodes::FN, $bytecode->code);
        self::assertNotContains(Opcodes::FN2, $bytecode->code);
    }

    public function testRejectsFixedArityMismatch(): void
    {
        $trait = $this->trait(
            conditions: [
                [
                    'fn' => 'substring',
                    'argv' => [
                        ['ref' => 'Value'],
                        0,
                        1,
                        false,
                        'extra'
                    ]
                ]
            ],
            parameters: ['Value' => ['type' => 'String']]
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Endpoint rules function substring expects 4 arguments, got 5'
        );

        (new BddCompiler(StdFunctions::all()))->compile($trait);
    }

    public function testRejectsFastPathArityMismatch(): void
    {
        $trait = $this->trait(
            conditions: [
                [
                    'fn' => 'isSet',
                    'argv' => [
                        ['ref' => 'Value'],
                        'extra'
                    ]
                ]
            ],
            parameters: ['Value' => ['type' => 'String']]
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Endpoint rules function isSet expects 1 arguments, got 2'
        );

        (new BddCompiler(StdFunctions::all()))->compile($trait);
    }

    public function testRejectsCoalesceBelowMinimumArity(): void
    {
        $trait = $this->trait(
            conditions: [
                [
                    'fn' => 'coalesce',
                    'argv' => [['ref' => 'Value']]
                ]
            ],
            parameters: ['Value' => ['type' => 'String']]
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Endpoint rules function coalesce expects at least 2 arguments, got 1'
        );

        (new BddCompiler(StdFunctions::all()))->compile($trait);
    }

    public function testResetsCompilerStateBetweenCompilations(): void
    {
        $compiler = new BddCompiler(StdFunctions::all());
        $compiler->compile($this->trait(
            parameters: ['First' => ['type' => 'String']],
            results: [
                [
                    'type' => 'endpoint',
                    'endpoint' => ['url' => 'https://{First}']
                ]
            ],
            root: Bytecode::RESULT_OFFSET
        ));

        $second = $compiler->compile($this->trait(
            parameters: ['Second' => ['type' => 'String']],
            results: [
                [
                    'type' => 'endpoint',
                    'endpoint' => ['url' => 'https://{Second}']
                ]
            ],
            root: Bytecode::RESULT_OFFSET
        ));

        self::assertSame(['Second' => 0], $second->inputRegisterMap);
        self::assertSame('Second', $second->registers[0]['name']);
        self::assertNotContains('First', $second->constants);
    }

    public function testRejectsUnknownFunction(): void
    {
        $trait = $this->trait(
            conditions: [
                [
                    'fn' => 'unknown',
                    'argv' => []
                ]
            ]
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Unknown endpoint rules function: unknown'
        );

        (new BddCompiler(StdFunctions::all()))->compile($trait);
    }

    public function testRejectsUnknownAndMalformedReferences(): void
    {
        try {
            (new BddCompiler(StdFunctions::all()))->compile(
                $this->trait(
                    results: [
                        [
                            'type' => 'endpoint',
                            'endpoint' => ['url' => '{Unknown}']
                        ]
                    ]
                )
            );
            self::fail('Expected unknown template reference to fail.');
        } catch (\RuntimeException $exception) {
            self::assertSame(
                'Unknown template reference: Unknown',
                $exception->getMessage()
            );
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Malformed index in path: items[0'
        );

        (new BddCompiler(StdFunctions::all()))->compile(
            $this->trait(
                parameters: ['Value' => ['type' => 'String']],
                conditions: [
                    [
                        'fn' => 'getAttr',
                        'argv' => [
                            ['ref' => 'Value'],
                            'items[0'
                        ]
                    ]
                ]
            )
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function malformedGetAttrIndexProvider(): iterable
    {
        yield 'empty index' => ['items[]'];
        yield 'non-numeric index' => ['items[abc]'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('malformedGetAttrIndexProvider')]
    public function testRejectsMalformedGetAttrIndex(string $path): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Malformed index in path: {$path}");

        (new BddCompiler(StdFunctions::all()))->compile(
            $this->trait(
                parameters: ['Value' => ['type' => 'String']],
                conditions: [
                    [
                        'fn' => 'getAttr',
                        'argv' => [
                            ['ref' => 'Value'],
                            $path
                        ]
                    ]
                ]
            )
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function malformedGetAttrPathProvider(): iterable
    {
        yield 'empty path' => [''];
        yield 'dot-only path' => ['.'];
        yield 'leading dot' => ['.items'];
        yield 'trailing dot' => ['items.'];
        yield 'empty middle segment' => ['items..suffix'];
        yield 'empty segment before index' => ['items.[0]'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('malformedGetAttrPathProvider')]
    public function testRejectsMalformedGetAttrPath(string $path): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Malformed property path: {$path}");

        (new BddCompiler(StdFunctions::all()))->compile(
            $this->trait(
                parameters: ['Value' => ['type' => 'String']],
                conditions: [
                    [
                        'fn' => 'getAttr',
                        'argv' => [
                            ['ref' => 'Value'],
                            $path
                        ]
                    ]
                ]
            )
        );
    }

    public function testAsciiStringOptimizationRequiresExplicitOptIn(): void
    {
        $defaultBytecode = (new BddCompiler(
            StdFunctions::all()
        ))->compile($this->substringTrait());
        $optimizedBytecode = (new BddCompiler(
            StdFunctions::all(),
            [],
            true
        ))->compile($this->substringTrait());

        $this->assertSame(0, $this->substringFlags($defaultBytecode->code));
        $this->assertSame(0x02, $this->substringFlags(
            $optimizedBytecode->code
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function substringTrait(): array
    {
        return [
            'parameters' => [
                'Value' => ['type' => 'String']
            ],
            'conditions' => [
                [
                    'fn' => 'substring',
                    'argv' => [
                        ['ref' => 'Value'],
                        0,
                        1,
                        false
                    ]
                ]
            ],
            'results' => [],
            'nodes' => '',
            'nodeCount' => 0,
            'root' => 1
        ];
    }

    /**
     * @param list<int> $code
     */
    private function substringFlags(array $code): int
    {
        $offset = \array_search(Opcodes::SUBSTRING, $code, true);
        $this->assertIsInt($offset);

        return $code[$offset + 3];
    }

    /**
     * @param array<string, mixed> $parameters
     * @param list<array<string, mixed>> $conditions
     * @param list<array<string, mixed>> $results
     *
     * @return array<string, mixed>
     */
    private function trait(
        array $parameters = [],
        array $conditions = [],
        array $results = [],
        string $nodes = '',
        int $nodeCount = 0,
        int $root = 1
    ): array
    {
        return [
            'parameters' => $parameters,
            'conditions' => $conditions,
            'results' => $results,
            'nodes' => $nodes,
            'nodeCount' => $nodeCount,
            'root' => $root
        ];
    }

    /**
     * @param list<int> $nodes
     */
    private function encodeNodes(array $nodes): string
    {
        $bytes = '';
        foreach ($nodes as $node) {
            $bytes .= \pack('N', $node & 0xFFFFFFFF);
        }

        return \base64_encode($bytes);
    }
}
