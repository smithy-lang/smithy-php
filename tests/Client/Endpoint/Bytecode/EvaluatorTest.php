<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Endpoint\Bytecode;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Endpoint\Bytecode\{
    Bytecode,
    Evaluator,
    OpcodeHandlerInterface,
    Opcodes,
    RulesEvaluationException,
    RulesFunctionInterface,
    StdFunctions
};
use Smithy\Core\Endpoint\Endpoint;
use Smithy\Tests\Client\Endpoint\Bytecode\Fixture\{
    EndpointResolutionTraceFixture,
    OpcodeHandlerFixture
};

#[CoversClass(Evaluator::class)]
final class EvaluatorTest extends TestCase
{
    public function testLoadsConstantsAndRegisters(): void
    {
        [$result, $registers] = $this->execute([
            Opcodes::LOAD_CONST,
            0,
            Opcodes::SET_REGISTER,
            1,
            Opcodes::LOAD_REGISTER,
            1,
            Opcodes::RETURN_VALUE
        ], ['value'], [null, null]);

        self::assertSame('value', $result);
        self::assertSame('value', $registers[1]);
    }

    public function testLoadsWideConstantIndex(): void
    {
        $constants = \array_fill(0, 257, null);
        $constants[256] = 'wide';

        [$result] = $this->execute([
            Opcodes::LOAD_CONST_W,
            1,
            0,
            Opcodes::RETURN_VALUE
        ], $constants);

        self::assertSame('wide', $result);
    }

    #[DataProvider('collectionProvider')]
    public function testBuildsCollections(
        array $code,
        array $constants,
        mixed $expected
    ): void
    {
        [$result] = $this->execute([
            ...$code,
            Opcodes::RETURN_VALUE
        ], $constants);

        self::assertSame($expected, $result);
    }

    /**
     * @return iterable<string, array{list<int>, list<mixed>, mixed}>
     */
    public static function collectionProvider(): iterable
    {
        yield 'empty list' => [[Opcodes::LIST0], [], []];
        yield 'one-item list' => [
            [Opcodes::LOAD_CONST, 0, Opcodes::LIST1],
            ['a'],
            ['a']
        ];
        yield 'two-item list' => [
            [
                Opcodes::LOAD_CONST,
                0,
                Opcodes::LOAD_CONST,
                1,
                Opcodes::LIST2
            ],
            ['a', 'b'],
            ['a', 'b']
        ];
        yield 'n-item list' => [
            [
                Opcodes::LOAD_CONST,
                0,
                Opcodes::LOAD_CONST,
                1,
                Opcodes::LOAD_CONST,
                2,
                Opcodes::LISTN,
                3
            ],
            ['a', 'b', 'c'],
            ['a', 'b', 'c']
        ];
        yield 'empty map' => [[Opcodes::MAP0], [], []];
        yield 'one-entry map' => [
            [
                Opcodes::LOAD_CONST,
                0,
                Opcodes::LOAD_CONST,
                1,
                Opcodes::MAP1
            ],
            ['value', 'key'],
            ['key' => 'value']
        ];
        yield 'two-entry map' => [
            [
                Opcodes::LOAD_CONST, 0, Opcodes::LOAD_CONST, 1,
                Opcodes::LOAD_CONST, 2, Opcodes::LOAD_CONST, 3,
                Opcodes::MAP2
            ],
            ['a', 'A', 'b', 'B'],
            ['A' => 'a', 'B' => 'b']
        ];
        yield 'three-entry map' => [
            [
                Opcodes::LOAD_CONST, 0, Opcodes::LOAD_CONST, 1,
                Opcodes::LOAD_CONST, 2, Opcodes::LOAD_CONST, 3,
                Opcodes::LOAD_CONST, 4, Opcodes::LOAD_CONST, 5,
                Opcodes::MAP3
            ],
            ['a', 'A', 'b', 'B', 'c', 'C'],
            ['A' => 'a', 'B' => 'b', 'C' => 'c']
        ];
        yield 'four-entry map' => [
            [
                Opcodes::LOAD_CONST, 0, Opcodes::LOAD_CONST, 1,
                Opcodes::LOAD_CONST, 2, Opcodes::LOAD_CONST, 3,
                Opcodes::LOAD_CONST, 4, Opcodes::LOAD_CONST, 5,
                Opcodes::LOAD_CONST, 6, Opcodes::LOAD_CONST, 7,
                Opcodes::MAP4
            ],
            ['a', 'A', 'b', 'B', 'c', 'C', 'd', 'D'],
            ['A' => 'a', 'B' => 'b', 'C' => 'c', 'D' => 'd']
        ];
        yield 'n-entry structure' => [
            [
                Opcodes::LOAD_CONST, 0, Opcodes::LOAD_CONST, 1,
                Opcodes::LOAD_CONST, 2, Opcodes::LOAD_CONST, 3,
                Opcodes::STRUCTN, 2
            ],
            ['a', 'A', 'b', 'B'],
            ['A' => 'a', 'B' => 'b']
        ];
        yield 'n-entry map' => [
            [
                Opcodes::LOAD_CONST, 0, Opcodes::LOAD_CONST, 1,
                Opcodes::LOAD_CONST, 2, Opcodes::LOAD_CONST, 3,
                Opcodes::MAPN, 2
            ],
            ['a', 'A', 'b', 'B'],
            ['A' => 'a', 'B' => 'b']
        ];
    }

    public function testResolvesTemplates(): void
    {
        [$resolved] = $this->execute([
            Opcodes::LOAD_CONST,
            0,
            Opcodes::LOAD_REGISTER,
            0,
            Opcodes::LOAD_CONST,
            1,
            Opcodes::RESOLVE_TEMPLATE,
            3,
            Opcodes::RETURN_VALUE
        ], ['https://', '/path'], ['example.test']);
        [$built] = $this->execute([
            Opcodes::BUILD_TEMPLATE,
            0,
            1,
            Opcodes::RETURN_VALUE
        ], [
            'property',
            [
                [0, 'https://'],
                [1, 0],
                [0, '/'],
                [2, [1, [0]]]
            ]
        ], ['example.test', ['property' => 'value']]);

        self::assertSame('https://example.test/path', $resolved);
        self::assertSame('https://example.test/value', $built);
    }

    #[DataProvider('functionProvider')]
    public function testCallsFunctions(
        array $code,
        array $constants,
        array $functions,
        mixed $expected
    ): void
    {
        [$result] = $this->execute([
            ...$code,
            Opcodes::RETURN_VALUE
        ], $constants, [], $functions);

        self::assertSame($expected, $result);
    }

    /**
     * @return iterable<string, array{
     *     list<int>,
     *     list<mixed>,
     *     list<RulesFunctionInterface>,
     *     mixed
     * }>
     */
    public static function functionProvider(): iterable
    {
        yield 'zero arguments' => [
            [Opcodes::FN0, 0],
            [],
            [StdFunctions::get('not')],
            null
        ];
        yield 'one argument' => [
            [Opcodes::LOAD_CONST, 0, Opcodes::FN1, 0],
            [false],
            [StdFunctions::get('not')],
            true
        ];
        yield 'two arguments' => [
            [
                Opcodes::LOAD_CONST,
                0,
                Opcodes::LOAD_CONST,
                1,
                Opcodes::FN2,
                0
            ],
            ['same', 'same'],
            [StdFunctions::get('stringEquals')],
            true
        ];
        yield 'three arguments' => [
            [
                Opcodes::LOAD_CONST, 0,
                Opcodes::LOAD_CONST, 1,
                Opcodes::LOAD_CONST, 2,
                Opcodes::FN3, 0
            ],
            [true, 'yes', 'no'],
            [StdFunctions::get('ite')],
            'yes'
        ];
        yield 'four arguments' => [
            [
                Opcodes::LOAD_CONST, 0,
                Opcodes::LOAD_CONST, 1,
                Opcodes::LOAD_CONST, 2,
                Opcodes::LOAD_CONST, 3,
                Opcodes::FN, 0, 4
            ],
            ['abcdef', 1, 4, false],
            [StdFunctions::get('substring')],
            'bcd'
        ];
        yield 'variadic arguments' => [
            [
                Opcodes::LOAD_CONST, 0,
                Opcodes::LOAD_CONST, 1,
                Opcodes::LOAD_CONST, 2,
                Opcodes::LOAD_CONST, 3,
                Opcodes::FN, 0, 4
            ],
            [null, null, 'value', 'ignored'],
            [StdFunctions::get('coalesce')],
            'value'
        ];
    }

    #[DataProvider('accessProvider')]
    public function testAccessesPropertiesAndIndexes(
        array $code,
        array $constants,
        array $registers,
        mixed $expected
    ): void
    {
        [$result] = $this->execute([
            ...$code,
            Opcodes::RETURN_VALUE
        ], $constants, $registers);

        self::assertSame($expected, $result);
    }

    /**
     * @return iterable<string, array{
     *     list<int>,
     *     list<mixed>,
     *     array<int, mixed>,
     *     mixed
     * }>
     */
    public static function accessProvider(): iterable
    {
        yield 'property' => [
            [Opcodes::LOAD_REGISTER, 0, Opcodes::GET_PROPERTY, 0, 0],
            ['name'],
            [['name' => 'value']],
            'value'
        ];
        yield 'property register' => [
            [Opcodes::GET_PROPERTY_REG, 0, 0, 0],
            ['name'],
            [['name' => 'value']],
            'value'
        ];
        yield 'index' => [
            [Opcodes::LOAD_REGISTER, 0, Opcodes::GET_INDEX, 1],
            [],
            [['zero', 'one']],
            'one'
        ];
        yield 'index register' => [
            [Opcodes::GET_INDEX_REG, 0, 1],
            [],
            [['zero', 'one']],
            'one'
        ];
        yield 'negative index' => [
            [Opcodes::LOAD_REGISTER, 0, Opcodes::GET_NEGATIVE_INDEX, 1],
            [],
            [['zero', 'one']],
            'one'
        ];
        yield 'negative index register' => [
            [Opcodes::GET_NEGATIVE_INDEX_REG, 0, 2],
            [],
            [['zero', 'one', 'two']],
            'one'
        ];
        yield 'missing property' => [
            [Opcodes::GET_PROPERTY_REG, 0, 0, 0],
            ['missing'],
            ['not-an-array'],
            null
        ];
    }

    #[DataProvider('comparisonProvider')]
    public function testEvaluatesComparisons(
        array $code,
        array $constants,
        array $registers,
        bool $expected
    ): void
    {
        [$result] = $this->execute([
            ...$code,
            Opcodes::RETURN_VALUE
        ], $constants, $registers);

        self::assertSame($expected, $result);
    }

    /**
     * @return iterable<string, array{
     *     list<int>,
     *     list<mixed>,
     *     array<int, mixed>,
     *     bool
     * }>
     */
    public static function comparisonProvider(): iterable
    {
        yield 'not' => [
            [Opcodes::LOAD_CONST, 0, Opcodes::NOT],
            [false],
            [],
            true
        ];
        yield 'not treats null as false' => [
            [Opcodes::LOAD_CONST, 0, Opcodes::NOT],
            [null],
            [],
            true
        ];
        yield 'is set' => [
            [Opcodes::LOAD_CONST, 0, Opcodes::ISSET],
            ['value'],
            [],
            true
        ];
        yield 'register is set' => [
            [Opcodes::TEST_REGISTER_ISSET, 0],
            [],
            ['value'],
            true
        ];
        yield 'register is not set' => [
            [Opcodes::TEST_REGISTER_NOT_SET, 0],
            [],
            [null],
            true
        ];
        yield 'is true' => [
            [Opcodes::LOAD_CONST, 0, Opcodes::IS_TRUE],
            [true],
            [],
            true
        ];
        yield 'register is true' => [
            [Opcodes::TEST_REGISTER_IS_TRUE, 0],
            [],
            [true],
            true
        ];
        yield 'register is false' => [
            [Opcodes::TEST_REGISTER_IS_FALSE, 0],
            [],
            [false],
            true
        ];
        yield 'strict equality' => [
            [
                Opcodes::LOAD_CONST,
                0,
                Opcodes::LOAD_CONST,
                1,
                Opcodes::EQUALS
            ],
            [1, 1],
            [],
            true
        ];
        yield 'string equality rejects non-string' => [
            [
                Opcodes::LOAD_CONST,
                0,
                Opcodes::LOAD_CONST,
                1,
                Opcodes::STRING_EQUALS
            ],
            [1, 1],
            [],
            false
        ];
        yield 'boolean equality' => [
            [
                Opcodes::LOAD_CONST,
                0,
                Opcodes::LOAD_CONST,
                1,
                Opcodes::BOOLEAN_EQUALS
            ],
            [false, false],
            [],
            true
        ];
    }

    #[DataProvider('stringOperationProvider')]
    public function testEvaluatesStringOperations(
        array $code,
        array $constants,
        array $registers,
        mixed $expected
    ): void
    {
        [$result] = $this->execute([
            ...$code,
            Opcodes::RETURN_VALUE
        ], $constants, $registers);

        self::assertSame($expected, $result);
    }

    /**
     * @return iterable<string, array{
     *     list<int>,
     *     list<mixed>,
     *     array<int, mixed>,
     *     mixed
     * }>
     */
    public static function stringOperationProvider(): iterable
    {
        yield 'substring' => [
            [Opcodes::LOAD_CONST, 0, Opcodes::SUBSTRING, 1, 4, 0],
            ['abcdef'],
            [],
            'bcd'
        ];
        yield 'reverse substring' => [
            [Opcodes::LOAD_CONST, 0, Opcodes::SUBSTRING, 0, 3, 1],
            ['abcdef'],
            [],
            'def'
        ];
        yield 'substring rejects reversed bounds' => [
            [Opcodes::LOAD_CONST, 0, Opcodes::SUBSTRING, 3, 1, 0],
            ['abcdef'],
            [],
            null
        ];
        yield 'invalid host label' => [
            [
                Opcodes::LOAD_CONST,
                0,
                Opcodes::LOAD_CONST,
                1,
                Opcodes::IS_VALID_HOST_LABEL
            ],
            ['bad label', false],
            [],
            false
        ];
        yield 'valid multi-label host' => [
            [
                Opcodes::LOAD_CONST,
                0,
                Opcodes::LOAD_CONST,
                1,
                Opcodes::IS_VALID_HOST_LABEL
            ],
            [\str_repeat('a', 63) . '.' . \str_repeat('b', 63), true],
            [],
            true
        ];
        yield 'host label rejects trailing hyphen' => [
            [
                Opcodes::LOAD_CONST,
                0,
                Opcodes::LOAD_CONST,
                1,
                Opcodes::IS_VALID_HOST_LABEL
            ],
            ['foo-', false],
            [],
            false
        ];
        yield 'multi-label host rejects trailing hyphen' => [
            [
                Opcodes::LOAD_CONST,
                0,
                Opcodes::LOAD_CONST,
                1,
                Opcodes::IS_VALID_HOST_LABEL
            ],
            ['foo-.example', true],
            [],
            false
        ];
        yield 'parse URL' => [
            [Opcodes::LOAD_CONST, 0, Opcodes::PARSE_URL],
            ['https://example.test/path'],
            [],
            [
                'scheme' => 'https',
                'authority' => 'example.test',
                'path' => '/path',
                'normalizedPath' => '/path/',
                'isIp' => false
            ]
        ];
        yield 'parse URL leaves an absent path empty' => [
            [Opcodes::LOAD_CONST, 0, Opcodes::PARSE_URL],
            ['https://example.test'],
            [],
            [
                'scheme' => 'https',
                'authority' => 'example.test',
                'path' => '',
                'normalizedPath' => '/',
                'isIp' => false
            ]
        ];
        yield 'parse URL preserves trailing slashes' => [
            [Opcodes::LOAD_CONST, 0, Opcodes::PARSE_URL],
            ['https://example.test/path//'],
            [],
            [
                'scheme' => 'https',
                'authority' => 'example.test',
                'path' => '/path//',
                'normalizedPath' => '/path//',
                'isIp' => false
            ]
        ];
        yield 'parse URL rejects query' => [
            [Opcodes::LOAD_CONST, 0, Opcodes::PARSE_URL],
            ['https://example.test?query=value'],
            [],
            null
        ];
        yield 'parse URL rejects fragment' => [
            [Opcodes::LOAD_CONST, 0, Opcodes::PARSE_URL],
            ['https://example.test#fragment'],
            [],
            null
        ];
        yield 'parse URL rejects whitespace in host' => [
            [Opcodes::LOAD_CONST, 0, Opcodes::PARSE_URL],
            ['https://exa mple.test/path'],
            [],
            null
        ];
        yield 'parse URL rejects invalid host label' => [
            [Opcodes::LOAD_CONST, 0, Opcodes::PARSE_URL],
            ['https://foo-.example/path'],
            [],
            null
        ];
        yield 'parse URL rejects userinfo' => [
            [Opcodes::LOAD_CONST, 0, Opcodes::PARSE_URL],
            ['https://user:pass@example.test/path'],
            [],
            null
        ];
        yield 'URI encode' => [
            [Opcodes::LOAD_CONST, 0, Opcodes::URI_ENCODE],
            ['a b'],
            [],
            'a%20b'
        ];
        yield 'split' => [
            [
                Opcodes::LOAD_CONST, 0,
                Opcodes::LOAD_CONST, 1,
                Opcodes::LOAD_CONST, 2,
                Opcodes::SPLIT
            ],
            ['a-b-c', '-', 2],
            [],
            ['a', 'b-c']
        ];
        yield 'split rejects invalid argument types' => [
            [
                Opcodes::LOAD_CONST, 0,
                Opcodes::LOAD_CONST, 1,
                Opcodes::LOAD_CONST, 2,
                Opcodes::SPLIT
            ],
            [null, '-', 0],
            [],
            null
        ];
        yield 'split rejects negative limit' => [
            [
                Opcodes::LOAD_CONST, 0,
                Opcodes::LOAD_CONST, 1,
                Opcodes::LOAD_CONST, 2,
                Opcodes::SPLIT
            ],
            ['a-b', '-', -1],
            [],
            null
        ];
        yield 'substring equals' => [
            [Opcodes::SUBSTRING_EQ, 0, 1, 4, 2, 0, 0],
            ['bcd'],
            ['abcdef'],
            true
        ];
        yield 'split get' => [
            [Opcodes::SPLIT_GET, 0, 0, 0, 255],
            ['-'],
            ['a-b-c'],
            'c'
        ];
        yield 'boolean select' => [
            [Opcodes::SELECT_BOOL_REG, 0, 0, 0, 0, 1],
            ['yes', 'no'],
            [true],
            'yes'
        ];
        yield 'boolean select uses false branch for non-true' => [
            [Opcodes::SELECT_BOOL_REG, 0, 0, 0, 0, 1],
            ['yes', 'no'],
            ['truthy'],
            'no'
        ];
        yield 'register string equality' => [
            [Opcodes::STRING_EQUALS_REG_CONST, 0, 0, 0],
            ['same'],
            ['same'],
            true
        ];
        yield 'build URI' => [
            [
                Opcodes::LOAD_CONST, 1,
                Opcodes::LOAD_CONST, 2,
                Opcodes::BUILD_URI, 0, 0
            ],
            ['https', 'example.test', '/path'],
            [],
            'https://example.test/path'
        ];
    }

    public function testControlsProgramFlow(): void
    {
        [$nonNull] = $this->execute([
            Opcodes::LOAD_CONST, 0,
            Opcodes::JNN_OR_POP, 0, 2,
            Opcodes::LOAD_CONST, 1,
            Opcodes::RETURN_VALUE
        ], ['kept', 'fallback']);
        [$null] = $this->execute([
            Opcodes::LOAD_CONST, 0,
            Opcodes::JNN_OR_POP, 0, 2,
            Opcodes::LOAD_CONST, 1,
            Opcodes::RETURN_VALUE
        ], [null, 'fallback']);
        [$conditional] = $this->execute([
            Opcodes::LOAD_CONST, 0,
            Opcodes::JMP_IF_FALSE, 0, 5,
            Opcodes::LOAD_CONST, 1,
            Opcodes::JUMP, 0, 2,
            Opcodes::LOAD_CONST, 2,
            Opcodes::RETURN_VALUE
        ], [false, 'yes', 'no']);

        self::assertSame('kept', $nonNull);
        self::assertSame('fallback', $null);
        self::assertSame('no', $conditional);
    }

    public function testSetsRegisterAndReturnsValue(): void
    {
        [$result, $registers] = $this->execute([
            Opcodes::LOAD_CONST,
            0,
            Opcodes::SET_REG_RETURN,
            0
        ], ['assigned'], [null]);

        self::assertSame('assigned', $result);
        self::assertSame('assigned', $registers[0]);
    }

    public function testBuildsEndpointWithHeadersAndAuthSchemes(): void
    {
        $properties = [
            'authSchemes' => [
                [
                    'name' => 'sigv4',
                    'signingName' => 'service',
                    'signingRegion' => 'us-east-1'
                ],
                ['missing-name' => true],
                'invalid'
            ]
        ];
        [$endpoint] = $this->execute([
            Opcodes::LOAD_CONST, 0,
            Opcodes::LOAD_CONST, 1,
            Opcodes::LOAD_CONST, 2,
            Opcodes::RETURN_ENDPOINT, 3
        ], [
            ['x-test' => ['one', 'two'], 'x-count' => 3],
            $properties,
            [
                'scheme' => 'https',
                'authority' => 'example.test',
                'path' => '/path'
            ]
        ], [], [], null, ['sigv4' => 'smithy.api#sigv4']);

        self::assertInstanceOf(Endpoint::class, $endpoint);
        self::assertSame('https://example.test/path', $endpoint->url);
        self::assertSame(
            ['x-test' => 'one, two', 'x-count' => '3'],
            $endpoint->headers
        );
        self::assertCount(1, $endpoint->authSchemes);
        self::assertSame(
            'smithy.api#sigv4',
            $endpoint->authSchemes[0]->schemeId
        );
        self::assertSame(
            [
                'signingName' => 'service',
                'signingRegion' => 'us-east-1'
            ],
            $endpoint->authSchemes[0]->properties
        );
        self::assertSame($properties, $endpoint->properties);
        self::assertTrue($endpoint->hasProperty('authSchemes'));
        self::assertSame(
            $properties['authSchemes'],
            $endpoint->getProperty('authSchemes')
        );
        self::assertFalse($endpoint->hasProperty('missing'));
        self::assertNull($endpoint->getProperty('missing'));
    }

    #[DataProvider('conditionProvider')]
    public function testWalksFastBddConditions(
        int $conditionType,
        int $operand,
        array $constants,
        mixed $value,
        string $expectedHost,
        mixed $default = 'default'
    ): void
    {
        $bytecode = $this->conditionalBytecode(
            $conditionType,
            $operand,
            $constants,
            default: $default,
            registerType: \is_bool($value) ? 'boolean' : 'string'
        );
        $endpoint = (new Evaluator($bytecode))->evaluate(['Value' => $value]);

        self::assertSame("https://{$expectedHost}", $endpoint->url);
    }

    /**
     * @return iterable<string, array{int, int, list<mixed>, mixed, string, 5?:mixed}>
     */
    public static function conditionProvider(): iterable
    {
        yield 'is set' => [Opcodes::COND_ISSET, 0, [], 'value', 'high'];
        yield 'is true' => [Opcodes::COND_IS_TRUE, 0, [], true, 'high'];
        yield 'is false' => [Opcodes::COND_IS_FALSE, 0, [], false, 'high'];
        yield 'is not set' => [
            Opcodes::COND_NOT_SET,
            0,
            [],
            null,
            'high',
            null
        ];
        yield 'string equals' => [
            Opcodes::COND_STRING_EQ_REG_CONST,
            0,
            ['match'],
            'other',
            'low'
        ];
    }

    public function testWalksFullAndComplementedBddConditions(): void
    {
        $bytecode = $this->conditionalBytecode(
            Opcodes::COND_FULL,
            0,
            [true, 'https://high', 'https://low'],
            [
                Opcodes::LOAD_CONST,
                0,
                Opcodes::RETURN_VALUE
            ],
            -2
        );

        $endpoint = (new Evaluator($bytecode))->evaluate([]);

        self::assertSame('https://low', $endpoint->url);
    }

    public function testTracesBddDecisionPathAndEffectiveParameters(): void
    {
        $bytecode = $this->conditionalBytecode(
            Opcodes::COND_STRING_EQ_REG_CONST,
            0,
            ['default']
        );
        $trace = new EndpointResolutionTraceFixture();

        $endpoint = (new Evaluator($bytecode))->evaluateTraced(
            ['Value' => null],
            $trace
        );

        self::assertSame(['Value' => 'default'], $trace->parameters);
        self::assertSame([[2, 0, true, true]], $trace->nodes);
        self::assertSame([[1, $endpoint]], $trace->results);
        self::assertSame('https://high', $endpoint->url);
    }

    public function testTracesComplementedBddBranch(): void
    {
        $bytecode = $this->conditionalBytecode(
            Opcodes::COND_FULL,
            0,
            [true, 'https://high', 'https://low'],
            [
                Opcodes::LOAD_CONST,
                0,
                Opcodes::RETURN_VALUE
            ],
            -2
        );
        $trace = new EndpointResolutionTraceFixture();

        $endpoint = (new Evaluator($bytecode))->evaluateTraced([], $trace);

        self::assertSame([[-2, 0, true, false]], $trace->nodes);
        self::assertSame([[2, $endpoint]], $trace->results);
        self::assertSame('https://low', $endpoint->url);
    }

    public function testTracesNoMatchResult(): void
    {
        $trace = new EndpointResolutionTraceFixture();

        try {
            (new Evaluator($this->bytecode([], bddRoot: 1)))
                ->evaluateTraced([], $trace);
            self::fail('Expected endpoint evaluation to fail.');
        } catch (RulesEvaluationException $exception) {
            self::assertSame(
                'No endpoint matched the supplied parameters',
                $exception->getMessage()
            );
        }

        self::assertSame([[-1, null]], $trace->results);
    }

    public function testTracesMatchedRulesError(): void
    {
        $bytecode = $this->bytecode(
            [
                Opcodes::LOAD_CONST,
                0,
                Opcodes::RETURN_VALUE,
                Opcodes::LOAD_CONST,
                1,
                Opcodes::RETURN_ERROR
            ],
            constants: [null, 'rules failed'],
            bddRoot: Bytecode::RESULT_OFFSET + 1,
            resultOffsets: [0, 3]
        );
        $trace = new EndpointResolutionTraceFixture();

        try {
            (new Evaluator($bytecode))->evaluateTraced([], $trace);
            self::fail('Expected endpoint evaluation to fail.');
        } catch (RulesEvaluationException $exception) {
            self::assertSame('rules failed', $exception->getMessage());
        }

        self::assertSame([[1, null]], $trace->results);
    }

    public function testUsesDefaultsAndIgnoresUnknownParameters(): void
    {
        $bytecode = $this->conditionalBytecode(
            Opcodes::COND_STRING_EQ_REG_CONST,
            0,
            ['default']
        );

        $endpoint = (new Evaluator($bytecode))->evaluate([
            'Value' => null,
            'Unknown' => 'ignored'
        ]);

        self::assertSame('https://high', $endpoint->url);
    }

    public function testDelegatesUnknownOpcodeToHandler(): void
    {
        $handler = new OpcodeHandlerFixture();
        [$result] = $this->execute(
            [200, 0, Opcodes::RETURN_VALUE],
            ['handled'],
            [],
            [],
            $handler
        );

        self::assertSame('handled', $result);
        self::assertSame(1, $handler->calls);
    }

    public function testThrowsForUnknownOpcode(): void
    {
        $this->expectException(RulesEvaluationException::class);
        $this->expectExceptionMessage('Unknown opcode 200 at pc 0');

        $this->execute([200]);
    }

    #[DataProvider('functionOpcodeProvider')]
    public function testThrowsForUnknownFunctionIndex(int $opcode): void
    {
        $this->expectException(RulesEvaluationException::class);
        $this->expectExceptionMessage('Unknown function 9 at pc 0');

        $this->execute([$opcode, 9, 0]);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function functionOpcodeProvider(): iterable
    {
        yield 'FN0' => [Opcodes::FN0];
        yield 'FN1' => [Opcodes::FN1];
        yield 'FN2' => [Opcodes::FN2];
        yield 'FN3' => [Opcodes::FN3];
        yield 'FN' => [Opcodes::FN];
    }

    public function testThrowsWhenProgramRunsOffEnd(): void
    {
        $this->expectException(RulesEvaluationException::class);
        $this->expectExceptionMessage(
            'Bytecode program ran off the end without returning'
        );

        $this->execute([Opcodes::LIST0]);
    }

    public function testThrowsReturnedError(): void
    {
        $this->expectException(RulesEvaluationException::class);
        $this->expectExceptionMessage('rules failed');

        $this->execute([
            Opcodes::LOAD_CONST,
            0,
            Opcodes::RETURN_ERROR
        ], ['rules failed']);
    }

    public function testRejectsTerminalAndInvalidResults(): void
    {
        $terminal = $this->bytecode([], bddRoot: 1);
        $invalid = $this->bytecode(
            [],
            bddRoot: Bytecode::RESULT_OFFSET
        );

        foreach ([$terminal, $invalid] as $bytecode) {
            try {
                (new Evaluator($bytecode))->evaluate([]);
                self::fail('Expected endpoint evaluation to fail.');
            } catch (RulesEvaluationException $exception) {
                self::assertSame(
                    'No endpoint matched the supplied parameters',
                    $exception->getMessage()
                );
            }
        }
    }

    public function testRejectsResultProgramThatReturnsValue(): void
    {
        $bytecode = $this->bytecode(
            [
                Opcodes::LOAD_CONST,
                0,
                Opcodes::RETURN_VALUE,
                Opcodes::LIST0,
                Opcodes::RETURN_VALUE
            ],
            constants: [null],
            bddRoot: Bytecode::RESULT_OFFSET + 1,
            resultOffsets: [0, 3]
        );

        $this->expectException(RulesEvaluationException::class);
        $this->expectExceptionMessage(
            'Endpoint result program did not return an Endpoint'
        );

        (new Evaluator($bytecode))->evaluate([]);
    }

    public function testRejectsMissingRequiredEndpointParameter(): void
    {
        $bytecode = $this->endpointBytecode([
            [
                'name' => 'Region',
                'builtIn' => null,
                'default' => null,
                'required' => true,
                'type' => 'string'
            ]
        ], ['Region' => 0]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Required endpoint parameter Region must not be null.'
        );

        (new Evaluator($bytecode))->evaluate([]);
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function invalidEndpointParameterProvider(): iterable
    {
        yield 'string' => ['string', false];
        yield 'boolean' => ['boolean', 'true'];
        yield 'string array' => ['stringarray', ['ok', 1]];
        yield 'string array map' => ['stringarray', ['first' => 'ok']];
    }

    #[DataProvider('invalidEndpointParameterProvider')]
    public function testRejectsInvalidEndpointParameterType(
        string $type,
        mixed $value
    ): void
    {
        $bytecode = $this->endpointBytecode([
            [
                'name' => 'Value',
                'builtIn' => null,
                'default' => null,
                'required' => false,
                'type' => $type
            ]
        ], ['Value' => 0]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Endpoint parameter Value must be of type {$type}."
        );

        (new Evaluator($bytecode))->evaluate(['Value' => $value]);
    }

    public function testRejectsUnknownConditionType(): void
    {
        $bytecode = $this->conditionalBytecode(
            conditionType: 99,
            operand: 0,
            constants: []
        );

        $this->expectException(RulesEvaluationException::class);
        $this->expectExceptionMessage(
            'Unknown condition type 99 at condition 0'
        );

        (new Evaluator($bytecode))->evaluate([]);
    }

    /**
     * @param list<int> $code
     * @param list<mixed> $constants
     * @param array<int, mixed> $registers
     * @param list<RulesFunctionInterface> $functions
     * @param array<string, string> $authSchemeIds
     *
     * @return array{mixed, array<int, mixed>}
     */
    private function execute(
        array $code,
        array $constants = [],
        array $registers = [],
        array $functions = [],
        ?OpcodeHandlerInterface $handler = null,
        array $authSchemeIds = []
    ): array
    {
        $evaluator = new Evaluator(
            $this->bytecode(
                $code,
                $constants,
                $functions
            ),
            $handler,
            $authSchemeIds
        );
        $method = new \ReflectionMethod($evaluator, 'runBytecode');
        $result = $method->invokeArgs($evaluator, [0, &$registers]);

        return [$result, $registers];
    }

    /**
     * @param list<int> $code
     * @param list<mixed> $constants
     * @param list<RulesFunctionInterface> $functions
     * @param list<array{name:string, builtIn:?string, default:mixed, required:bool, type:string}>
     *     $registers
     * @param array<string, int> $inputRegisterMap
     * @param list<int> $bddNodes
     * @param list<int> $conditionOffsets
     * @param list<int> $resultOffsets
     * @param list<int> $conditionTypes
     * @param list<int> $conditionOperands
     */
    private function bytecode(
        array $code,
        array $constants = [],
        array $functions = [],
        array $registers = [],
        array $inputRegisterMap = [],
        array $bddNodes = [],
        int $bddRoot = 1,
        array $conditionOffsets = [],
        array $resultOffsets = [],
        array $conditionTypes = [],
        array $conditionOperands = []
    ): Bytecode
    {
        return new Bytecode(
            code: $code,
            constants: $constants,
            functions: $functions,
            registers: $registers,
            inputRegisterMap: $inputRegisterMap,
            bddNodes: $bddNodes,
            bddRoot: $bddRoot,
            conditionOffsets: $conditionOffsets,
            resultOffsets: $resultOffsets,
            conditionTypes: $conditionTypes,
            conditionOperands: $conditionOperands
        );
    }

    /**
     * @param list<array{name:string, builtIn:?string, default:mixed, required:bool, type:string}>
     *     $registers
     * @param array<string, int> $inputRegisterMap
     */
    private function endpointBytecode(
        array $registers,
        array $inputRegisterMap
    ): Bytecode
    {
        return $this->bytecode(
            [
                Opcodes::LOAD_CONST,
                0,
                Opcodes::RETURN_VALUE,
                Opcodes::LOAD_CONST,
                1,
                Opcodes::RETURN_ENDPOINT,
                0
            ],
            constants: [null, 'https://example.test'],
            registers: $registers,
            inputRegisterMap: $inputRegisterMap,
            bddRoot: Bytecode::RESULT_OFFSET + 1,
            resultOffsets: [0, 3]
        );
    }

    /**
     * @param list<mixed> $constants
     * @param list<int>|null $conditionCode
     */
    private function conditionalBytecode(
        int $conditionType,
        int $operand,
        array $constants,
        ?array $conditionCode = null,
        int $root = 2,
        mixed $default = 'default',
        string $registerType = 'string'
    ): Bytecode
    {
        $code = $conditionCode ?? [];
        $conditionOffset = 0;
        $noMatchOffset = \count($code);
        $noMatchConstant = \count($constants);
        $constants[] = null;
        $code[] = Opcodes::LOAD_CONST;
        $code[] = $noMatchConstant;
        $code[] = Opcodes::RETURN_VALUE;
        $highOffset = \count($code);
        $highConstant = \count($constants);
        $constants[] = 'https://high';
        $code[] = Opcodes::LOAD_CONST;
        $code[] = $highConstant;
        $code[] = Opcodes::RETURN_ENDPOINT;
        $code[] = 0;
        $lowOffset = \count($code);
        $lowConstant = \count($constants);
        $constants[] = 'https://low';
        $code[] = Opcodes::LOAD_CONST;
        $code[] = $lowConstant;
        $code[] = Opcodes::RETURN_ENDPOINT;
        $code[] = 0;

        return $this->bytecode(
            code: $code,
            constants: $constants,
            registers: [
                [
                    'name' => 'Value',
                    'builtIn' => null,
                    'default' => $default,
                    'required' => false,
                    'type' => $registerType
                ]
            ],
            inputRegisterMap: ['Value' => 0],
            bddNodes: [
                -1,
                1,
                -1,
                0,
                Bytecode::RESULT_OFFSET + 1,
                Bytecode::RESULT_OFFSET + 2
            ],
            bddRoot: $root,
            conditionOffsets: [$conditionOffset],
            resultOffsets: [$noMatchOffset, $highOffset, $lowOffset],
            conditionTypes: [$conditionType],
            conditionOperands: [$operand]
        );
    }
}
