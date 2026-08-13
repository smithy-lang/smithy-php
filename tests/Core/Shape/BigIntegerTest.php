<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Shape\BigInteger;

class BigIntegerTest extends TestCase
{
    public function testAcceptsPositiveDigits(): void
    {
        $value = new BigInteger('12345678901234567890');
        $this->assertSame('12345678901234567890', $value->value);
    }

    public function testAcceptsNegativeDigits(): void
    {
        $value = new BigInteger('-12345678901234567890');
        $this->assertSame('-12345678901234567890', $value->value);
    }

    public function testAcceptsZero(): void
    {
        $value = new BigInteger('0');
        $this->assertSame('0', $value->value);
    }

    #[DataProvider('invalidValues')]
    public function testRejectsInvalidValue(string $invalid): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid BigInteger/');
        new BigInteger($invalid);
    }

    public static function invalidValues(): iterable
    {
        yield 'empty string' => [''];
        yield 'fractional' => ['1.5'];
        yield 'exponent' => ['1e10'];
        yield 'leading whitespace' => [' 1'];
        yield 'trailing whitespace' => ['1 '];
        yield 'plus sign' => ['+1'];
        yield 'hex' => ['0x10'];
        yield 'word' => ['abc'];
        yield 'mixed' => ['1abc'];
    }
}
