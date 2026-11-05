<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Http\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Http\Message\Uri;

#[CoversClass(Uri::class)]
final class UriTest extends TestCase
{
    #[DataProvider('userinfoUriProvider')]
    public function testRejectsUserinfoDuringConstruction(string $uri): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('authority confusion');

        new Uri($uri);
    }

    /** @return iterable<string, array{string}> */
    public static function userinfoUriProvider(): iterable
    {
        yield 'user' => ['https://user@example.com'];
        yield 'user and password' => [
            'https://user:password@example.com'
        ];
        yield 'empty user' => ['https://@example.com'];
    }

    #[DataProvider('userinfoProvider')]
    public function testRejectsUserinfoMutation(
        string $user,
        ?string $password
    ): void
    {
        $uri = new Uri('https://example.com');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('authority confusion');

        $uri->withUserInfo($user, $password);
    }

    /** @return iterable<string, array{string, string|null}> */
    public static function userinfoProvider(): iterable
    {
        yield 'user' => ['user', null];
        yield 'user and password' => ['user', 'password'];
        yield 'password without user' => ['', 'password'];
    }

    public function testAllowsEmptyUserinfoMutation(): void
    {
        $uri = new Uri('https://example.com');

        $this->assertSame($uri, $uri->withUserInfo(''));
        $this->assertSame($uri, $uri->withUserInfo('', ''));
    }

    #[DataProvider('controlByteProvider')]
    public function testRejectsRawControlBytes(string $controlByte): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('control bytes');

        new Uri('https://example.com/path' . $controlByte);
    }

    /** @return iterable<string, array{string}> */
    public static function controlByteProvider(): iterable
    {
        yield 'null' => ["\x00"];
        yield 'tab' => ["\x09"];
        yield 'line feed' => ["\x0A"];
        yield 'carriage return' => ["\x0D"];
        yield 'unit separator' => ["\x1F"];
        yield 'delete' => ["\x7F"];
    }

    public function testAllowsAtSignOutsideUserinfo(): void
    {
        $uri = new Uri(
            'https://example.com/path@value?recipient=@example'
        );

        $this->assertSame('/path@value', $uri->getPath());
        $this->assertSame('recipient=@example', $uri->getQuery());
    }

    public function testRejectsUserinfoDelimiterInHost(): void
    {
        $uri = new Uri('https://example.com');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must not contain userinfo');

        $uri->withHost('user@evil.example');
    }

    #[DataProvider('controlByteProvider')]
    public function testRejectsRawControlBytesInScheme(
        string $controlByte
    ): void
    {
        $uri = new Uri('https://example.com');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('control bytes');

        $uri->withScheme('https' . $controlByte);
    }

    #[DataProvider('controlByteProvider')]
    public function testRejectsRawControlBytesInHost(
        string $controlByte
    ): void
    {
        $uri = new Uri('https://example.com');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('control bytes');

        $uri->withHost('example.com' . $controlByte);
    }

    #[DataProvider('invalidSchemeProvider')]
    public function testRejectsInvalidSchemeMutation(string $scheme): void
    {
        $uri = new Uri();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid HTTP URI scheme');

        $uri->withScheme($scheme);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidSchemeProvider(): iterable
    {
        yield 'injected authority' => [
            'http://user@evil.example?'
        ];
        yield 'leading digit' => ['1https'];
        yield 'authority delimiter' => ['https://'];
        yield 'space' => ['http scheme'];
    }

    public function testPercentEncodesControlsInMutableComponents(): void
    {
        $uri = (new Uri('https://example.com'))
            ->withPath("/line\nbreak")
            ->withQuery("value=\r")
            ->withFragment("\0");

        $this->assertSame('/line%0Abreak', $uri->getPath());
        $this->assertSame('value=%0D', $uri->getQuery());
        $this->assertSame('%00', $uri->getFragment());
        $this->assertSame(
            'https://example.com/line%0Abreak?value=%0D#%00',
            (string) $uri
        );
    }
}
