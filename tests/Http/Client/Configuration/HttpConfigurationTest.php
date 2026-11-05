<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Http\Client\Configuration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Http\Client\Configuration\{
    ConnectConfiguration,
    ConnectionManagerConfiguration,
    HTTP2Configuration,
    HTTPVersionPolicy,
    HttpClientConfiguration,
    TLSConfiguration
};

#[CoversClass(ConnectConfiguration::class)]
#[CoversClass(ConnectionManagerConfiguration::class)]
#[CoversClass(HTTP2Configuration::class)]
#[CoversClass(HTTPVersionPolicy::class)]
#[CoversClass(HttpClientConfiguration::class)]
#[CoversClass(TLSConfiguration::class)]
final class HttpConfigurationTest extends TestCase
{
    public function testUsesProductionDefaults(): void
    {
        $manager = new ConnectionManagerConfiguration();
        $connect = new ConnectConfiguration();
        $http2 = new HTTP2Configuration();

        $this->assertSame(100, $manager->maxConcurrency);
        $this->assertSame(100, $manager->maxIdlePerHost);
        $this->assertSame(100, $manager->maxIdleTotal);
        $this->assertSame(60.0, $manager->idleTimeout);
        $this->assertSame(10.0, $manager->connectionAcquireTimeout);
        $this->assertSame(10.0, $connect->connectTimeout);
        $this->assertSame(100, $http2->maxStreams);
    }

    public function testAllowsZeroIdleLimits(): void
    {
        $configuration = new ConnectionManagerConfiguration(
            maxIdlePerHost: 0,
            maxIdleTotal: 0
        );

        $this->assertSame(0, $configuration->maxIdlePerHost);
        $this->assertSame(0, $configuration->maxIdleTotal);
    }

    #[DataProvider('invalidConnectionManagerConfigurations')]
    public function testRejectsInvalidConnectionManagerConfiguration(
        callable $create,
        string $message
    ): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $create();
    }

    /**
     * Provide invalid connection manager configurations.
     *
     * @return array<string, array{callable(): object, string}>
     */
    public static function invalidConnectionManagerConfigurations(): array
    {
        return [
            'zero concurrency' => [
                static fn() => new ConnectionManagerConfiguration(
                    maxConcurrency: 0
                ),
                'Maximum concurrency must be at least 1.'
            ],
            'negative per-host idle limit' => [
                static fn() => new ConnectionManagerConfiguration(
                    maxIdlePerHost: -1
                ),
                'Idle connection limits must be non-negative.'
            ],
            'negative total idle limit' => [
                static fn() => new ConnectionManagerConfiguration(
                    maxIdleTotal: -1
                ),
                'Idle connection limits must be non-negative.'
            ],
            'total below per-host idle limit' => [
                static fn() => new ConnectionManagerConfiguration(
                    maxIdlePerHost: 2,
                    maxIdleTotal: 1
                ),
                'Maximum total idle connections must be greater than '
                    . 'or equal to the per-host limit.'
            ],
            'zero idle timeout' => [
                static fn() => new ConnectionManagerConfiguration(
                    idleTimeout: 0.0
                ),
                'Idle timeout must be finite and positive.'
            ],
            'infinite idle timeout' => [
                static fn() => new ConnectionManagerConfiguration(
                    idleTimeout: \INF
                ),
                'Idle timeout must be finite and positive.'
            ],
            'negative acquisition timeout' => [
                static fn() => new ConnectionManagerConfiguration(
                    connectionAcquireTimeout: -1.0
                ),
                'Connection acquisition timeout must be finite and '
                    . 'non-negative.'
            ],
            'infinite acquisition timeout' => [
                static fn() => new ConnectionManagerConfiguration(
                    connectionAcquireTimeout: \INF
                ),
                'Connection acquisition timeout must be finite and '
                    . 'non-negative.'
            ]
        ];
    }

    #[DataProvider('invalidConnectTimeouts')]
    public function testRejectsInvalidConnectTimeout(float $timeout): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Connect timeout must be finite and positive.'
        );

        new ConnectConfiguration(connectTimeout: $timeout);
    }

    /**
     * Provide invalid connect timeouts.
     *
     * @return array<string, array{float}>
     */
    public static function invalidConnectTimeouts(): array
    {
        return [
            'zero' => [0.0],
            'negative' => [-1.0],
            'infinite' => [\INF]
        ];
    }

    public function testRejectsInvalidHttp2StreamLimit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Maximum HTTP/2 streams must be at least 1.'
        );

        new HTTP2Configuration(maxStreams: 0);
    }

    public function testAcceptsCipherSuiteNames(): void
    {
        $configuration = new TLSConfiguration(
            cipherSuites: [
                'ECDHE-RSA-AES128-GCM-SHA256'
            ],
            tls13CipherSuites: [
                'TLS_AES_256_GCM_SHA384'
            ]
        );

        $this->assertSame(
            ['ECDHE-RSA-AES128-GCM-SHA256'],
            $configuration->cipherSuites
        );
        $this->assertSame(
            ['TLS_AES_256_GCM_SHA384'],
            $configuration->tls13CipherSuites
        );
    }

    #[DataProvider('invalidCipherSuiteNames')]
    public function testRejectsInvalidCipherSuiteName(mixed $name): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Cipher suites must contain only valid cipher '
                . 'suite names.'
        );

        new TLSConfiguration(cipherSuites: [$name]);
    }

    /**
     * Provide invalid cipher suite names.
     *
     * @return array<string, array{mixed}>
     */
    public static function invalidCipherSuiteNames(): array
    {
        return [
            'empty' => [''],
            'separator' => ['ECDHE:RSA'],
            'expression' => ['!AES128-SHA'],
            'non-string' => [123]
        ];
    }
}
