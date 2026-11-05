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
use Smithy\Client\Endpoint\Bytecode\Bytecode;

#[CoversClass(Bytecode::class)]
final class BytecodeTest extends TestCase
{
    public function testPrecomputesRegisterDefaults(): void
    {
        $bytecode = new Bytecode(
            code: [],
            constants: [],
            functions: [],
            registers: [
                $this->register('Region', 'us-east-1'),
                $this->register('UseFips', false),
                $this->register('Bucket', null)
            ],
            inputRegisterMap: [],
            bddNodes: [],
            bddRoot: 1,
            conditionOffsets: [],
            resultOffsets: [],
            conditionTypes: [],
            conditionOperands: []
        );

        self::assertSame(
            ['us-east-1', false, null],
            $bytecode->defaultRegisters
        );
    }

    #[DataProvider('referenceProvider')]
    public function testClassifiesBddReferences(
        int $reference,
        bool $node,
        bool $terminal,
        bool $result
    ): void
    {
        self::assertSame($node, Bytecode::isNodeReference($reference));
        self::assertSame($terminal, Bytecode::isTerminal($reference));
        self::assertSame($result, Bytecode::isResultReference($reference));
    }

    /**
     * @return iterable<string, array{int, bool, bool, bool}>
     */
    public static function referenceProvider(): iterable
    {
        yield 'positive terminal' => [1, false, true, false];
        yield 'negative terminal' => [-1, false, true, false];
        yield 'first positive node' => [2, true, false, false];
        yield 'first negative node' => [-2, true, false, false];
        yield 'largest positive node' => [
            Bytecode::RESULT_OFFSET - 1,
            true,
            false,
            false
        ];
        yield 'largest negative node' => [
            -Bytecode::RESULT_OFFSET + 1,
            true,
            false,
            false
        ];
        yield 'result' => [
            Bytecode::RESULT_OFFSET,
            false,
            false,
            true
        ];
        yield 'negative result marker' => [
            -Bytecode::RESULT_OFFSET,
            false,
            false,
            false
        ];
        yield 'zero' => [0, false, false, false];
    }

    /**
     * @return array{name:string, builtIn:null, default:mixed, required:bool, type:string}
     */
    private function register(string $name, mixed $default): array
    {
        return [
            'name' => $name,
            'builtIn' => null,
            'default' => $default,
            'required' => false,
            'type' => 'string'
        ];
    }
}
