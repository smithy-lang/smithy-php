<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Auth;

use PHPUnit\Framework\Attributes\{
    CoversClass,
    DataProvider
};
use PHPUnit\Framework\TestCase;
use Smithy\Client\Auth\OrderingConstraint;

#[CoversClass(OrderingConstraint::class)]
final class OrderingConstraintTest extends TestCase
{
    /**
     * @param callable(string): OrderingConstraint $factory
     */
    #[DataProvider('constraintCases')]
    public function testCreatesConstraint(
        callable $factory,
        bool $standard,
        bool $before,
        bool $after
    ): void
    {
        $constraint = $factory('slot');

        self::assertSame('slot', $constraint->slot);
        self::assertSame($standard, $constraint->isStandard());
        self::assertSame($before, $constraint->isBefore());
        self::assertSame($after, $constraint->isAfter());
    }

    /**
     * @return array<string, array{
     *     callable(string): OrderingConstraint,
     *     bool,
     *     bool,
     *     bool
     * }>
     */
    public static function constraintCases(): array
    {
        return [
            'standard' => [
                OrderingConstraint::standard(...),
                true,
                false,
                false
            ],
            'before' => [
                OrderingConstraint::before(...),
                false,
                true,
                false
            ],
            'after' => [
                OrderingConstraint::after(...),
                false,
                false,
                true
            ]
        ];
    }

    public function testRejectsEmptySlot(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must not be empty');

        OrderingConstraint::standard('');
    }
}
