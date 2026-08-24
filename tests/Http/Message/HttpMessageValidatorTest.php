<?php

declare(strict_types=1);

namespace Smithy\Tests\Http\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Http\Message\HttpMessageValidator;

#[CoversClass(HttpMessageValidator::class)]
final class HttpMessageValidatorTest extends TestCase
{
    #[DataProvider('validTokenProvider')]
    public function testAcceptsValidMethod(string $method): void
    {
        HttpMessageValidator::validateMethod($method);

        $this->addToAssertionCount(1);
    }

    #[DataProvider('invalidTokenProvider')]
    public function testRejectsInvalidMethod(string $method): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'HTTP methods must be valid tokens.'
        );

        HttpMessageValidator::validateMethod($method);
    }

    #[DataProvider('validProtocolVersionProvider')]
    public function testAcceptsValidProtocolVersion(string $version): void
    {
        HttpMessageValidator::validateProtocolVersion($version);

        $this->addToAssertionCount(1);
    }

    #[DataProvider('invalidProtocolVersionProvider')]
    public function testRejectsInvalidProtocolVersion(string $version): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'HTTP protocol versions must contain only a version number.'
        );

        HttpMessageValidator::validateProtocolVersion($version);
    }

    #[DataProvider('validStatusCodeProvider')]
    public function testAcceptsValidStatusCode(int $statusCode): void
    {
        HttpMessageValidator::validateStatusCode($statusCode);

        $this->addToAssertionCount(1);
    }

    #[DataProvider('invalidStatusCodeProvider')]
    public function testRejectsInvalidStatusCode(int $statusCode): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'HTTP status codes must be between 100 and 599.'
        );

        HttpMessageValidator::validateStatusCode($statusCode);
    }

    #[DataProvider('validReasonPhraseProvider')]
    public function testAcceptsValidReasonPhrase(string $reasonPhrase): void
    {
        HttpMessageValidator::validateReasonPhrase($reasonPhrase);

        $this->addToAssertionCount(1);
    }

    #[DataProvider('invalidFieldValueProvider')]
    public function testRejectsInvalidReasonPhrase(string $reasonPhrase): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'HTTP reason phrases contain invalid control bytes.'
        );

        HttpMessageValidator::validateReasonPhrase($reasonPhrase);
    }

    #[DataProvider('validTokenProvider')]
    public function testAcceptsValidHeaderName(string $name): void
    {
        HttpMessageValidator::validateHeaderName($name);

        $this->addToAssertionCount(1);
    }

    #[DataProvider('invalidTokenProvider')]
    public function testRejectsInvalidHeaderName(string $name): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'HTTP header names must be valid tokens.'
        );

        HttpMessageValidator::validateHeaderName($name);
    }

    #[DataProvider('headerValueProvider')]
    public function testNormalizesHeaderValues(
        string|array $value,
        array $expected
    ): void
    {
        $this->assertSame(
            $expected,
            HttpMessageValidator::normalizeHeaderValues($value)
        );
    }

    public function testRejectsInvalidHeaderValueContainer(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'HTTP header values must be strings or arrays of strings.'
        );

        HttpMessageValidator::normalizeHeaderValues(1);
    }

    public function testRejectsNonStringHeaderValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'HTTP header values must be strings.'
        );

        HttpMessageValidator::normalizeHeaderValues(['value', 1]);
    }

    #[DataProvider('invalidFieldValueProvider')]
    public function testRejectsInvalidHeaderValue(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'HTTP header values contain invalid control bytes.'
        );

        HttpMessageValidator::normalizeHeaderValues($value);
    }

    /** @return iterable<string, array{string}> */
    public static function validTokenProvider(): iterable
    {
        yield 'letters' => ['GET'];
        yield 'hyphen' => ['M-SEARCH'];
        yield 'symbols' => ['!#$%&\'*+-.^_`|~09Az'];
    }

    /** @return iterable<string, array{string}> */
    public static function invalidTokenProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'space' => ['BAD TOKEN'];
        yield 'slash' => ['BAD/TOKEN'];
        yield 'control' => ["BAD\r\n"];
    }

    /** @return iterable<string, array{string}> */
    public static function validProtocolVersionProvider(): iterable
    {
        yield 'integer' => ['2'];
        yield 'decimal' => ['1.1'];
        yield 'multiple digits' => ['10.20'];
    }

    /** @return iterable<string, array{string}> */
    public static function invalidProtocolVersionProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'prefix' => ['HTTP/1.1'];
        yield 'missing minor' => ['1.'];
        yield 'missing major' => ['.1'];
        yield 'multiple dots' => ['1.1.1'];
        yield 'letters' => ['h2'];
    }

    /** @return iterable<string, array{int}> */
    public static function validStatusCodeProvider(): iterable
    {
        yield 'minimum' => [100];
        yield 'maximum' => [599];
    }

    /** @return iterable<string, array{int}> */
    public static function invalidStatusCodeProvider(): iterable
    {
        yield 'below minimum' => [99];
        yield 'above maximum' => [600];
    }

    /** @return iterable<string, array{string}> */
    public static function validReasonPhraseProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'text' => ['Not Found'];
        yield 'tab' => ["Not\tFound"];
    }

    /** @return iterable<string, array{string}> */
    public static function invalidFieldValueProvider(): iterable
    {
        yield 'null byte' => ["value\0"];
        yield 'line feed' => ["value\n"];
        yield 'carriage return' => ["value\r"];
        yield 'delete' => ["value\x7f"];
    }

    /** @return iterable<string, array{string|array, list<string>}> */
    public static function headerValueProvider(): iterable
    {
        yield 'string' => [' value ', ['value']];
        yield 'tab trimming' => ["\tvalue\t", ['value']];
        yield 'interior tab' => ["value\tinside", ["value\tinside"]];
        yield 'zero' => ['0', ['0']];
        yield 'list' => [
            [' first ', 'second'],
            ['first', 'second']
        ];
        yield 'empty list' => [[], []];
    }
}
