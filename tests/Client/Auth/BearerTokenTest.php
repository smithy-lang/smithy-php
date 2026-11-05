<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Auth\BearerToken;

#[CoversClass(BearerToken::class)]
final class BearerTokenTest extends TestCase
{
    public function testStoresTokenAndExpiration(): void
    {
        $token = new BearerToken('token', 123.0);

        self::assertSame('token', $token->getToken());
        self::assertSame(123.0, $token->getExpirationTime());
    }

    public function testRejectsEmptyToken(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must not be empty');

        new BearerToken('');
    }

    public function testRedactsDebugOutput(): void
    {
        $token = new BearerToken('secret-token', 123.0);

        self::assertSame(
            [
                'token' => '***REDACTED***',
                'expirationTime' => 123.0
            ],
            $token->__debugInfo()
        );

        foreach ([$this->printValue($token), $this->dumpValue($token)] as $output) {
            self::assertStringContainsString('***REDACTED***', $output);
            self::assertStringNotContainsString('secret-token', $output);
        }
    }

    public function testRedactsSerializedRepresentations(): void
    {
        $token = new BearerToken('secret-token', 123.0);
        $json = \json_encode($token, JSON_THROW_ON_ERROR);
        $serialized = \serialize($token);

        self::assertSame(
            [
                'token' => '***REDACTED***',
                'expirationTime' => 123
            ],
            \json_decode($json, true, flags: JSON_THROW_ON_ERROR)
        );
        self::assertStringContainsString('***REDACTED***', $serialized);
        self::assertStringNotContainsString('secret-token', $serialized);
    }

    public function testRejectsSerializedRestoration(): void
    {
        $serialized = \serialize(new BearerToken('secret-token'));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('cannot be restored');

        \unserialize($serialized);
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
