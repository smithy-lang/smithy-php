<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Endpoint\Bytecode\Fixture;

use Smithy\Client\Endpoint\Bytecode\RulesFunctionInterface;

final class VariadicFunctionFixture implements RulesFunctionInterface
{
    public function getName(): string
    {
        return 'vendor.variadic';
    }

    public function getRegistryClass(): string
    {
        return self::class;
    }

    public function getArgumentCount(): int
    {
        return -1;
    }

    public function apply0(): mixed
    {
        return null;
    }

    public function apply1(mixed $a0): mixed
    {
        return null;
    }

    public function apply2(mixed $a0, mixed $a1): mixed
    {
        return null;
    }

    public function apply3(mixed $a0, mixed $a1, mixed $a2): mixed
    {
        return null;
    }

    /**
     * @param list<mixed> $args
     */
    public function apply(array $args): mixed
    {
        return \implode('-', $args);
    }
}
