<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Endpoint\Bytecode\Fixture;

use Smithy\Client\Endpoint\Bytecode\OpcodeHandlerInterface;

final class OpcodeHandlerFixture implements OpcodeHandlerInterface
{
    public int $calls = 0;

    /**
     * Push the constant selected by the custom opcode operand.
     *
     * @param list<int> $code
     * @param list<mixed> $stack
     * @param array<int, mixed> $regs
     * @param list<mixed> $constants
     */
    public function handle(
        int $op,
        array $code,
        int &$pc,
        array &$stack,
        int &$sp,
        array &$regs,
        array $constants
    ): void
    {
        $this->calls++;
        $stack[$sp++] = $constants[$code[$pc++]];
    }
}
