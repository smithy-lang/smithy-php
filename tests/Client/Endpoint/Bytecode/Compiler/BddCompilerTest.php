<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Endpoint\Bytecode\Compiler;

use PHPUnit\Framework\TestCase;
use Smithy\Client\Endpoint\Bytecode\Compiler\BddCompiler;
use Smithy\Client\Endpoint\Bytecode\Opcodes;
use Smithy\Client\Endpoint\Bytecode\StdFunctions;

final class BddCompilerTest extends TestCase
{
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
}
