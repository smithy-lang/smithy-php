<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Http\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use Smithy\Http\Message\Request;
use Smithy\Http\Message\StringRequestStream;
use Smithy\Http\Message\Uri;

#[CoversClass(Request::class)]
final class RequestTest extends TestCase
{
    public function testImplementsPsrRequestContract(): void
    {
        $request = new Request('GET', 'https://example.com');

        $this->assertInstanceOf(RequestInterface::class, $request);
    }

    public function testPreservesZeroStringBody(): void
    {
        $request = new Request('POST', 'https://example.com', body: '0');

        $this->assertSame('0', (string) $request->getBody());
        $this->assertSame(1, $request->getBody()->getSize());
    }

    public function testCreatesEmptyStreamForEmptyStringBody(): void
    {
        $request = new Request('POST', 'https://example.com', body: '');

        $this->assertSame('', (string) $request->getBody());
        $this->assertSame(0, $request->getBody()->getSize());
    }

    public function testNormalizesHeadersDuringConstruction(): void
    {
        $request = new Request(
            'GET',
            'https://example.com',
            [
                'X-Test' => " \tvalue\tinside\t ",
                'X-List' => [' first ', 'second']
            ]
        );

        $this->assertTrue($request->hasHeader('x-TEST'));
        $this->assertSame(
            ["value\tinside"],
            $request->getHeader('X-Test')
        );
        $this->assertSame(
            ['first', 'second'],
            $request->getHeader('x-list')
        );
        $this->assertSame('first, second', $request->getHeaderLine('X-List'));
    }

    public function testRegularConstructorRejectsNormalizedHeaders(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Request(
            'GET',
            'https://example.com',
            [
                'x-test' => ['X-Test', ['value']]
            ]
        );
    }

    public function testCreatesFromNormalizedHeadersWithoutNormalization(): void
    {
        $headers = [
            'x-test' => ['X-Test', [" \tvalue\t "]]
        ];
        $request = Request::fromNormalized(
            'GET',
            'https://example.com',
            $headers
        );

        $this->assertSame(
            $headers['x-test'],
            $request->getRawHeaders()['x-test']
        );
        $this->assertSame(['example.com'], $request->getHeader('Host'));
    }

    public function testNormalMutationsPreserveOriginalRequest(): void
    {
        $request = new Request(
            'GET',
            'https://example.com/original',
            [
                'X-Test' => 'old',
                'X-Remove' => 'remove'
            ],
            'original'
        );
        $body = new StringRequestStream('updated');

        $updated = $request
            ->withProtocolVersion('2')
            ->withMethod('POST')
            ->withHeader('X-Test', " \tfirst\t ")
            ->withAddedHeader('X-Test', 'second')
            ->withoutHeader('X-Remove')
            ->withBody($body)
            ->withRequestTarget('/explicit')
            ->withUri(new Uri('https://example.org/updated'));

        $this->assertNotSame($request, $updated);
        $this->assertSame('1.1', $request->getProtocolVersion());
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame(['old'], $request->getHeader('X-Test'));
        $this->assertTrue($request->hasHeader('X-Remove'));
        $this->assertSame('original', (string) $request->getBody());
        $this->assertSame('/original', $request->getRequestTarget());
        $this->assertSame('example.com', $request->getHeaderLine('Host'));

        $this->assertSame('2', $updated->getProtocolVersion());
        $this->assertSame('POST', $updated->getMethod());
        $this->assertSame(['first', 'second'], $updated->getHeader('x-test'));
        $this->assertFalse($updated->hasHeader('x-remove'));
        $this->assertSame($body, $updated->getBody());
        $this->assertSame('/explicit', $updated->getRequestTarget());
        $this->assertSame('example.org', $updated->getHeaderLine('Host'));
    }

    public function testNormalizedHeadersCloneAndReplaceMatchingEntries(): void
    {
        $request = new Request(
            'GET',
            'https://example.com',
            [
                'X-First' => '1',
                'X-Replace' => 'old'
            ]
        );

        $updated = $request
            ->withNormalizedHeader(
                'x-single',
                'X-Single',
                [" \tvalue\t "]
            )
            ->withNormalizedHeaders([
                'x-second' => ['X-Second', ['2']],
                'x-replace' => ['X-REPLACE', ['new']]
            ]);

        $this->assertNotSame($request, $updated);
        $this->assertSame(['old'], $request->getHeader('x-replace'));
        $this->assertFalse($request->hasHeader('x-single'));
        $this->assertFalse($request->hasHeader('x-second'));

        $this->assertSame(['1'], $updated->getHeader('x-first'));
        $this->assertSame(['2'], $updated->getHeader('x-second'));
        $this->assertSame(['new'], $updated->getHeader('x-replace'));
        $this->assertSame(
            ['X-Single', [" \tvalue\t "]],
            $updated->getRawHeaders()['x-single']
        );
        $this->assertSame(
            'X-REPLACE',
            $updated->getRawHeaders()['x-replace'][0]
        );
    }

    public function testNormalizedHeadersDoNotValidateInput(): void
    {
        $request = new Request('GET', 'https://example.com');

        $updated = $request->withNormalizedHeaders([
            'wrong-key' => ['Bad Header', ["value\r\n"]]
        ]);

        $this->assertSame(
            ['Bad Header', ["value\r\n"]],
            $updated->getRawHeaders()['wrong-key']
        );
    }

    public function testComputesRequestTargetAndHostFromUri(): void
    {
        $request = new Request(
            'GET',
            'https://example.com:8443/path?one=1'
        );

        $this->assertSame('/path?one=1', $request->getRequestTarget());
        $this->assertSame('example.com:8443', $request->getHeaderLine('Host'));
    }

    public function testWithUriUpdatesHostAndPreservesOriginalRequest(): void
    {
        $request = new Request(
            'GET',
            'https://example.com/first',
            ['X-First' => 'value']
        );

        $updated = $request->withUri(
            new Uri('https://example.org/second?two=2')
        );

        $this->assertSame('/first', $request->getRequestTarget());
        $this->assertSame('example.com', $request->getHeaderLine('Host'));
        $this->assertSame('/second?two=2', $updated->getRequestTarget());
        $this->assertSame('example.org', $updated->getHeaderLine('Host'));
        $this->assertSame(
            ['Host', 'X-First'],
            \array_keys($updated->getHeaders())
        );
    }

    public function testWithUriPreservesExplicitHostWhenRequested(): void
    {
        $request = new Request(
            'GET',
            'https://example.com',
            ['Host' => 'custom.example']
        );

        $updated = $request->withUri(
            new Uri('https://example.org'),
            preserveHost: true
        );

        $this->assertSame('custom.example', $updated->getHeaderLine('Host'));
    }

    public function testExplicitRequestTargetSurvivesUriMutation(): void
    {
        $request = new Request('GET', 'https://example.com/first');

        $updated = $request
            ->withRequestTarget('*')
            ->withUri(new Uri('https://example.org/second'));

        $this->assertSame('*', $updated->getRequestTarget());
    }

    public function testRejectsInvalidMethodDuringConstruction(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Request('BAD METHOD', 'https://example.com');
    }

    public function testRejectsInvalidMethodDuringMutation(): void
    {
        $request = new Request('GET', 'https://example.com');

        $this->expectException(\InvalidArgumentException::class);

        $request->withMethod("GET\r\n");
    }

    public function testRejectsInvalidHeaderNameDuringConstruction(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Request(
            'GET',
            'https://example.com',
            ['Bad Header' => 'value']
        );
    }

    public function testRejectsInvalidHeaderValueDuringMutation(): void
    {
        $request = new Request('GET', 'https://example.com');

        $this->expectException(\InvalidArgumentException::class);

        $request->withHeader(
            'X-Test',
            "value\r\nX-Injected: true"
        );
    }

    public function testRejectsNonStringAddedHeaderValue(): void
    {
        $request = new Request('GET', 'https://example.com');

        $this->expectException(\InvalidArgumentException::class);

        $request->withAddedHeader('X-Test', 1);
    }

    public function testRejectsInvalidProtocolVersionDuringConstruction(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Request(
            'GET',
            'https://example.com',
            protocolVersion: 'HTTP/1.1'
        );
    }

    public function testRejectsInvalidProtocolVersionDuringMutation(): void
    {
        $request = new Request('GET', 'https://example.com');

        $this->expectException(\InvalidArgumentException::class);

        $request->withProtocolVersion('HTTP/1.1');
    }

    public function testRejectsInvalidHostFromCustomUri(): void
    {
        $uri = $this->createStub(UriInterface::class);
        $uri->method('getHost')->willReturn("example.com\r\nX-Test: value");

        $this->expectException(\InvalidArgumentException::class);

        new Request('GET', $uri);
    }
}
