<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Auth\{
    BearerToken,
    StaticTokenProvider
};

#[CoversClass(StaticTokenProvider::class)]
final class StaticTokenProviderTest extends TestCase
{
    public function testCreatesTokenFromString(): void
    {
        $identity = (new StaticTokenProvider(
            'token',
            123.0
        ))->resolveIdentity()->unwrap();

        self::assertInstanceOf(BearerToken::class, $identity);
        self::assertSame('token', $identity->getToken());
        self::assertSame(123.0, $identity->getExpirationTime());
    }

    public function testReturnsProvidedToken(): void
    {
        $token = new BearerToken('token');

        self::assertSame(
            $token,
            (new StaticTokenProvider($token))->resolveIdentity()->unwrap()
        );
    }

    public function testNestedTokenDebugOutputIsRedacted(): void
    {
        $provider = new StaticTokenProvider('secret-token');

        foreach ([$this->printValue($provider), $this->dumpValue($provider)] as $output) {
            self::assertStringContainsString('***REDACTED***', $output);
            self::assertStringNotContainsString('secret-token', $output);
        }
    }

    public function testRawTokenIsRedactedFromExceptionTrace(): void
    {
        $previous = \ini_set('zend.exception_ignore_args', '0');

        try {
            try {
                new StaticTokenProvider('trace-secret', []);
                self::fail('Expected a TypeError.');
            } catch (\TypeError $exception) {
                $trace = $exception->getTrace();

                self::assertInstanceOf(
                    \SensitiveParameterValue::class,
                    $trace[0]['args'][0]
                );
                self::assertStringNotContainsString(
                    'trace-secret',
                    \print_r($trace, true)
                );
            }
        } finally {
            if ($previous !== false) {
                \ini_set('zend.exception_ignore_args', $previous);
            }
        }
    }

    private function printValue(mixed $value): string
    {
        return \print_r($value, true);
    }

    private function dumpValue(mixed $value): string
    {
        \ob_start();
        \var_dump($value);

        return (string) \ob_get_clean();
    }
}
