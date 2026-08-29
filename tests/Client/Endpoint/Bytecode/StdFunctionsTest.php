<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Endpoint\Bytecode;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Endpoint\Bytecode\StdFunctions;

/**
 * Behavioral coverage for the Smithy rules-engine standard-library
 * function registry.
 *
 * We test each function's spec-defined edge cases directly against the
 * registered instance - the compiler prefers dedicated opcodes for the
 * fast-path ones but the registry still needs to be correct for cases
 * the compiler couldn't inline.
 */
#[CoversClass(StdFunctions::class)]
final class StdFunctionsTest extends TestCase
{
    public function testIsSet(): void
    {
        $fn = StdFunctions::get('isSet');
        $this->assertTrue($fn->apply1('anything'));
        // False is set by presence, not truthiness.
        $this->assertTrue($fn->apply1(false));
        $this->assertTrue($fn->apply1(0));
        $this->assertTrue($fn->apply1(''));
        $this->assertFalse($fn->apply1(null));
    }

    public function testNot(): void
    {
        $fn = StdFunctions::get('not');
        $this->assertTrue($fn->apply1(false));
        $this->assertFalse($fn->apply1(true));
        // `not` unwraps optional to bool. Any non-true is treated as false.
        $this->assertTrue($fn->apply1(null));
    }

    public function testStringEquals(): void
    {
        $fn = StdFunctions::get('stringEquals');
        $this->assertTrue($fn->apply2('abc', 'abc'));
        $this->assertFalse($fn->apply2('abc', 'ABC'));
        $this->assertFalse($fn->apply2(null, 'abc'));
        $this->assertFalse($fn->apply2(123, '123'));
    }

    public function testBooleanEquals(): void
    {
        $fn = StdFunctions::get('booleanEquals');
        $this->assertTrue($fn->apply2(true, true));
        $this->assertTrue($fn->apply2(false, false));
        $this->assertFalse($fn->apply2(true, false));
        $this->assertFalse($fn->apply2(1, true));
        $this->assertFalse($fn->apply2(null, false));
    }

    public function testGetAttrKey(): void
    {
        $fn = StdFunctions::get('getAttr');
        $this->assertSame('foo', $fn->apply2(['Thing1' => 'foo'], 'Thing1'));
        $this->assertNull($fn->apply2(['Thing1' => 'foo'], 'Missing'));
    }

    public function testGetAttrNestedPath(): void
    {
        $fn = StdFunctions::get('getAttr');

        $this->assertSame(
            'value',
            $fn->apply2(
                ['Parent' => ['Child' => 'value']],
                'Parent.Child'
            )
        );
    }

    public function testGetAttrNestedIndexedPath(): void
    {
        $fn = StdFunctions::get('getAttr');

        $this->assertSame(
            'second',
            $fn->apply2(
                [
                    'Parent' => [
                        'Items' => [
                            ['Name' => 'first'],
                            ['Name' => 'second']
                        ]
                    ]
                ],
                'Parent.Items[1].Name'
            )
        );
        $this->assertSame(
            'second',
            $fn->apply2(
                [
                    'Parent' => [
                        'Items' => [
                            ['Name' => 'first'],
                            ['Name' => 'second']
                        ]
                    ]
                ],
                'Parent.Items[-1].Name'
            )
        );
    }

    public function testGetAttrPositiveIndex(): void
    {
        $fn = StdFunctions::get('getAttr');
        $arr = ['Items' => ['a', 'b', 'c']];
        $this->assertSame('a', $fn->apply2($arr, 'Items[0]'));
        $this->assertSame('c', $fn->apply2($arr, 'Items[2]'));
        $this->assertNull($fn->apply2($arr, 'Items[9]'));
    }

    public function testGetAttrNegativeIndex(): void
    {
        $fn = StdFunctions::get('getAttr');
        $arr = ['Items' => ['a', 'b', 'c']];
        // Spec supports negative indexing: [-1] is last, [-2] is second-to-last.
        $this->assertSame('c', $fn->apply2($arr, 'Items[-1]'));
        $this->assertSame('b', $fn->apply2($arr, 'Items[-2]'));
    }

    public function testGetAttrRejectsInvalidInputs(): void
    {
        $fn = StdFunctions::get('getAttr');

        $this->assertNull($fn->apply2('not-an-array', 'Items'));
        $this->assertNull($fn->apply2([], 1));
        $this->assertNull(
            $fn->apply2(['Items' => 'not-an-array'], 'Items[0]')
        );
        $this->assertNull(
            $fn->apply2(['Items' => ['a']], 'Items[-2]')
        );
    }

    public function testSubstringForward(): void
    {
        $fn = StdFunctions::get('substring');
        $this->assertSame('abc', $fn->apply(['abcde', 0, 3, false]));
        $this->assertSame('bcd', $fn->apply(['abcde', 1, 4, false]));
    }

    public function testSubstringReverse(): void
    {
        $fn = StdFunctions::get('substring');
        // Spec example: `substring("abcde", 0, 3, true) == "cde"`.
        // Reverse mode counts back from the end of the string.
        $this->assertSame('cde', $fn->apply(['abcde', 0, 3, true]));
    }

    public function testSubstringReturnsNullWhenTooShort(): void
    {
        $fn = StdFunctions::get('substring');
        $this->assertNull($fn->apply(['ab', 0, 3, false]));
    }

    public function testSubstringReturnsNullOnNonAscii(): void
    {
        $fn = StdFunctions::get('substring');
        // Spec: any octet > 127 returns None.
        $this->assertNull($fn->apply(["ab\xC3\xA9", 0, 1, false]));
    }

    public function testSubstringReturnsNullWhenStartGteStop(): void
    {
        $fn = StdFunctions::get('substring');
        $this->assertNull($fn->apply(['abcde', 2, 2, false]));
        $this->assertNull($fn->apply(['abcde', 3, 1, false]));
    }

    public function testSubstringRejectsInvalidTypes(): void
    {
        $fn = StdFunctions::get('substring');

        $this->assertNull($fn->apply([1, 0, 1, false]));
        $this->assertNull($fn->apply(['abc', '0', 1, false]));
        $this->assertNull($fn->apply(['abc', 0, '1', false]));
    }

    public function testUriEncodeUnreservedUntouched(): void
    {
        $fn = StdFunctions::get('uriEncode');
        // Unreserved chars per RFC 3986: alnum + `-`, `_`, `.`, `~`.
        $this->assertSame('AZaz09-_.~', $fn->apply1('AZaz09-_.~'));
    }

    public function testUriEncodeEscapesReservedAndSpace(): void
    {
        $fn = StdFunctions::get('uriEncode');
        // Space, slash, and question mark are reserved.
        $this->assertSame('a%20b', $fn->apply1('a b'));
        $this->assertSame('foo%2Fbar', $fn->apply1('foo/bar'));
    }

    public function testParseUrlBasic(): void
    {
        $fn = StdFunctions::get('parseURL');
        $result = $fn->apply1('https://example.com');
        $this->assertIsArray($result);
        $this->assertSame('https', $result['scheme']);
        $this->assertSame('example.com', $result['authority']);
        $this->assertSame('/', $result['path']);
        $this->assertSame('/', $result['normalizedPath']);
        $this->assertFalse($result['isIp']);
    }

    public function testParseUrlWithPortAndPath(): void
    {
        $fn = StdFunctions::get('parseURL');
        $result = $fn->apply1('http://example.com:80/foo/bar');
        $this->assertSame('http', $result['scheme']);
        $this->assertSame('example.com:80', $result['authority']);
        $this->assertSame('/foo/bar', $result['path']);
        $this->assertSame('/foo/bar/', $result['normalizedPath']);
    }

    public function testParseUrlRejectsQuery(): void
    {
        $fn = StdFunctions::get('parseURL');
        // Spec: query portion MUST cause rejection.
        $this->assertNull($fn->apply1('https://example.com?foo=bar'));
    }

    public function testParseUrlRejectsFragmentAndUnsupportedScheme(): void
    {
        $fn = StdFunctions::get('parseURL');

        $this->assertNull($fn->apply1('https://example.com#fragment'));
        $this->assertNull($fn->apply1('ftp://example.com'));
        $this->assertNull($fn->apply1(1));
    }

    public function testParseUrlIPv4(): void
    {
        $fn = StdFunctions::get('parseURL');
        $result = $fn->apply1('https://127.0.0.1');
        $this->assertSame('127.0.0.1', $result['authority']);
        $this->assertTrue($result['isIp']);
    }

    public function testParseUrlIPv4WithPort(): void
    {
        $fn = StdFunctions::get('parseURL');
        $result = $fn->apply1('https://127.0.0.1:8443');
        $this->assertSame('127.0.0.1:8443', $result['authority']);
        $this->assertTrue($result['isIp']);
    }

    public function testParseUrlIPv6Bracketed(): void
    {
        $fn = StdFunctions::get('parseURL');
        $result = $fn->apply1('https://[fe80::1]:8443');
        $this->assertSame('[fe80::1]:8443', $result['authority']);
        $this->assertTrue($result['isIp']);
    }

    public function testParseUrlReturnsNullOnMalformed(): void
    {
        $fn = StdFunctions::get('parseURL');
        $this->assertNull($fn->apply1('not a url at all'));
        $this->assertNull($fn->apply1('://missing-scheme.com'));
        $this->assertNull($fn->apply1('https://exa mple.com/path'));
        $this->assertNull($fn->apply1('https://foo-.example.com/path'));
        $this->assertNull($fn->apply1('https://user:pass@example.com/path'));
    }

    public function testValidHostLabelSingle(): void
    {
        $fn = StdFunctions::get('isValidHostLabel');
        $this->assertTrue($fn->apply2('example', false));
        $this->assertTrue($fn->apply2('a-b', false));
        // One character is the minimum valid label length.
        $this->assertTrue($fn->apply2('a', false));
        // 63 characters is the maximum valid label length.
        $this->assertTrue($fn->apply2(\str_repeat('a', 63), false));

        $this->assertFalse($fn->apply2('', false));
        // Labels cannot start with a hyphen.
        $this->assertFalse($fn->apply2('-bad', false));
        // Dots are not allowed when subdomains are disabled.
        $this->assertFalse($fn->apply2('foo.bar', false));
        // Labels cannot exceed 63 characters.
        $this->assertFalse($fn->apply2(\str_repeat('a', 64), false));
        // Labels cannot end with a hyphen.
        $this->assertFalse($fn->apply2('foo-', false));
    }

    public function testValidHostLabelSubdomains(): void
    {
        $fn = StdFunctions::get('isValidHostLabel');
        $this->assertTrue($fn->apply2('a.b.c', true));
        $this->assertTrue($fn->apply2('foo.example', true));

        // Subdomains cannot contain empty labels.
        $this->assertFalse($fn->apply2('a..b', true));
        // Subdomain labels cannot start with a hyphen.
        $this->assertFalse($fn->apply2('a.-b', true));
        // Subdomain labels cannot end with a hyphen.
        $this->assertFalse($fn->apply2('foo-.example', true));
    }

    public function testCoalesceFirstNonNull(): void
    {
        $fn = StdFunctions::get('coalesce');
        $this->assertSame('a', $fn->apply(['a', 'b']));
        $this->assertSame('b', $fn->apply([null, 'b']));
        $this->assertSame('c', $fn->apply([null, null, 'c']));
    }

    public function testCoalesceFalseIsNotEmpty(): void
    {
        // Spec: coalesce returns the first non-empty result. A `false`
        // is non-empty. It must short-circuit even though PHP `??` would
        // pass through it.
        $fn = StdFunctions::get('coalesce');
        $this->assertSame(false, $fn->apply([null, false, 'x']));
    }

    public function testCoalesceReturnsLastArgWhenAllNull(): void
    {
        $fn = StdFunctions::get('coalesce');
        // Spec: if all args are null, return the last one's result.
        $this->assertNull($fn->apply([null, null, null]));
    }

    public function testSplitBasic(): void
    {
        $fn = StdFunctions::get('split');
        $this->assertSame(['a', 'b', 'c'], $fn->apply(['a--b--c', '--', 0]));
    }

    public function testSplitLimit1NoSplit(): void
    {
        $fn = StdFunctions::get('split');
        // Spec: limit === 1 means "do not split".
        $this->assertSame(['a--b--c'], $fn->apply(['a--b--c', '--', 1]));
    }

    public function testSplitLimitCap(): void
    {
        $fn = StdFunctions::get('split');
        // Spec: limit > 1 splits into at most `limit` parts.
        // `explode('--', 'a--b--c--d', 3)` produces ['a', 'b', 'c--d'].
        $this->assertSame(
            ['a', 'b', 'c--d'],
            $fn->apply(['a--b--c--d', '--', 3])
        );
    }

    public function testSplitEmptyInput(): void
    {
        $fn = StdFunctions::get('split');
        // Spec: empty input returns [""].
        $this->assertSame([''], $fn->apply(['', '--', 0]));
    }

    public function testSplitRejectsInvalidInputs(): void
    {
        $fn = StdFunctions::get('split');

        $this->assertNull($fn->apply([1, '-', 0]));
        $this->assertNull($fn->apply(['a-b', 1, 0]));
        $this->assertNull($fn->apply(['a-b', '', 0]));
        $this->assertNull($fn->apply(['a-b', '-', '0']));
        $this->assertNull($fn->apply(['a-b', '-', -1]));
    }

    public function testFunctionsExposeRegistryMetadata(): void
    {
        foreach (StdFunctions::all() as $name => $function) {
            self::assertSame($name, $function->getName());
            self::assertSame(
                StdFunctions::class,
                $function->getRegistryClass()
            );
        }
    }

    public function testItePicksBranch(): void
    {
        $fn = StdFunctions::get('ite');
        $this->assertSame(
            'true-branch',
            $fn->apply([true, 'true-branch', 'false-branch'])
        );
        $this->assertSame(
            'false-branch',
            $fn->apply([false, 'true-branch', 'false-branch'])
        );
    }

    public function testIteNonBooleanIsFalse(): void
    {
        // The spec types the condition as `bool`. We treat any non-true
        // value as false. This keeps behavior deterministic when a
        // sibling expression accidentally produces a nullable bool.
        $fn = StdFunctions::get('ite');
        $this->assertSame('else', $fn->apply([null, 'then', 'else']));
        $this->assertSame('else', $fn->apply([1, 'then', 'else']));
    }

    public function testAllContainsEverySpecFunction(): void
    {
        $names = \array_keys(StdFunctions::all());
        // The Endpoints 2.0 spec defines exactly these 12 stdlib functions.
        $expected = [
            'booleanEquals',
            'stringEquals',
            'isSet',
            'not',
            'getAttr',
            'substring',
            'uriEncode',
            'parseURL',
            'isValidHostLabel',
            'coalesce',
            'split',
            'ite',
        ];
        \sort($names);
        \sort($expected);
        $this->assertSame($expected, $names);
    }

    public function testGetThrowsOnUnknown(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        StdFunctions::get('nonexistent');
    }
}
