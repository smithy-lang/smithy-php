<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Waiter;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Waiter\PathComparator;
final class PathComparatorTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed, string, bool}>
     */
    public static function stringEqualsCases(): iterable
    {
        yield 'string matches' => ['ready', 'ready', true];
        yield 'string differs' => ['pending', 'ready', false];
        yield 'integer is rejected' => [42, '42', false];
        yield 'float is rejected' => [4.5, '4.5', false];
        yield 'boolean is rejected' => [true, '1', false];
        yield 'null is rejected' => [null, '', false];
    }

    #[DataProvider('stringEqualsCases')]
    public function testStringEquals(
        mixed $actual,
        string $expected,
        bool $matches
    ): void
    {
        $this->assertSame(
            $matches,
            PathComparator::StringEquals->compare($actual, $expected)
        );
    }

    public function testBooleanEqualsRequiresBooleanActualValue(): void
    {
        $this->assertTrue(
            PathComparator::BooleanEquals->compare(true, 'true')
        );
        $this->assertTrue(
            PathComparator::BooleanEquals->compare(false, 'false')
        );
        $this->assertFalse(
            PathComparator::BooleanEquals->compare('true', 'true')
        );
        $this->assertFalse(
            PathComparator::BooleanEquals->compare(1, 'true')
        );
        $this->assertFalse(
            PathComparator::BooleanEquals->compare(false, 'invalid')
        );
    }

    public function testAllStringEqualsRequiresNonEmptyMatchingList(): void
    {
        $comparator = PathComparator::AllStringEquals;

        $this->assertTrue($comparator->compare(['ready', 'ready'], 'ready'));
        $this->assertFalse($comparator->compare([2, '2'], '2'));
        $this->assertFalse(
            $comparator->compare(['ready', 'pending'], 'ready')
        );
        $this->assertFalse($comparator->compare([], 'ready'));
        $this->assertFalse(
            $comparator->compare(['status' => 'ready'], 'ready')
        );
        $this->assertFalse($comparator->compare('ready', 'ready'));
    }

    public function testAnyStringEqualsRequiresAtLeastOneMatchingListItem(): void
    {
        $comparator = PathComparator::AnyStringEquals;

        $this->assertTrue(
            $comparator->compare(['pending', 'ready'], 'ready')
        );
        $this->assertFalse($comparator->compare([1, 2], '2'));
        $this->assertFalse(
            $comparator->compare(['pending', 'failed'], 'ready')
        );
        $this->assertFalse($comparator->compare([], 'ready'));
        $this->assertFalse($comparator->compare('ready', 'ready'));
    }
}
