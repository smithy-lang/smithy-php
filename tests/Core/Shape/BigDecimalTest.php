<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Shape\BigDecimal;
use Smithy\Core\Shape\Exception\InvalidShapeValueException;

class BigDecimalTest extends TestCase
{
    #[DataProvider('validValues')]
    public function testAcceptsValidValue(string $valid): void
    {
        $value = new BigDecimal($valid);
        $this->assertSame($valid, $value->value);
    }

    public static function validValues(): iterable
    {
        yield 'integer' => ['123'];
        yield 'negative integer' => ['-123'];
        yield 'zero' => ['0'];
        yield 'fractional' => ['1.5'];
        yield 'negative fractional' => ['-1.5'];
        yield 'exponent lowercase' => ['1.5e10'];
        yield 'exponent uppercase' => ['1.5E10'];
        yield 'exponent positive sign' => ['1.5e+10'];
        yield 'exponent negative sign' => ['1.5e-10'];
        yield 'long fractional' => [
            '3.141592653589793238462643383279502884197'
        ];
    }

    #[DataProvider('invalidValues')]
    public function testRejectsInvalidValue(string $invalid): void
    {
        $this->expectException(InvalidShapeValueException::class);
        $this->expectExceptionMessageMatches('/Invalid BigDecimal/');
        new BigDecimal($invalid);
    }

    public static function invalidValues(): iterable
    {
        yield 'empty string' => [''];
        yield 'leading dot' => ['.5'];
        yield 'trailing dot' => ['1.'];
        yield 'leading whitespace' => [' 1.5'];
        yield 'trailing whitespace' => ['1.5 '];
        yield 'plus sign' => ['+1.5'];
        yield 'hex' => ['0x10'];
        yield 'word' => ['abc'];
        yield 'mixed' => ['1.5abc'];
        yield 'exponent without digits' => ['1.5e'];
        yield 'leading zero' => ['01.0'];
        yield 'leading zero integer' => ['00'];
        yield 'negative leading zero' => ['-01'];
    }
}
