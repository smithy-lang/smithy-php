<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Dynamic\Utils;

use Smithy\Client\Endpoint\Bytecode\RulesFunctionInterface;

final class AlwaysTrueFunctionFixture implements RulesFunctionInterface
{
    public static function get(string $name): RulesFunctionInterface
    {
        if ($name !== 'example.alwaysTrue') {
            throw new \InvalidArgumentException(
                "Unknown test endpoint function: {$name}"
            );
        }

        return new self();
    }

    public function getName(): string
    {
        return 'example.alwaysTrue';
    }

    public function getRegistryClass(): string
    {
        return self::class;
    }

    public function getArgumentCount(): int
    {
        return 0;
    }

    public function apply0(): mixed
    {
        return true;
    }

    public function apply1(mixed $a0): mixed
    {
        return null;
    }

    public function apply2(mixed $a0, mixed $a1): mixed
    {
        return null;
    }

    public function apply3(
        mixed $a0,
        mixed $a1,
        mixed $a2
    ): mixed
    {
        return null;
    }

    public function apply(array $args): mixed
    {
        return true;
    }
}
