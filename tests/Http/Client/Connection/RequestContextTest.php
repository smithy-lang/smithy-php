<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Http\Client\Connection;

use PHPUnit\Framework\TestCase;
use Smithy\Http\Client\Connection\RequestContext;
use Smithy\Protocol\Utils\ComponentUri;

final class RequestContextTest extends TestCase
{
    public function testDefaultsSchemeAndPortFromMissingScheme(): void
    {
        $context = RequestContext::fromUri(
            new ComponentUri(host: 'example.com')
        );

        self::assertSame('https', $context->scheme);
        self::assertSame(443, $context->port);
        self::assertSame(
            'https://example.com:443',
            $context->key
        );
    }

    public function testDefaultsHttpPort(): void
    {
        $context = RequestContext::fromUri(
            new ComponentUri(scheme: 'http', host: 'example.com')
        );

        self::assertSame('http', $context->scheme);
        self::assertSame(80, $context->port);
    }

    public function testPreservesExplicitPortForUnknownScheme(): void
    {
        $context = RequestContext::fromUri(
            new ComponentUri(
                scheme: 'ftp',
                host: 'example.com',
                port: 2121
            )
        );

        self::assertSame('ftp', $context->scheme);
        self::assertSame(2121, $context->port);
    }

    public function testRejectsUnknownSchemeWithoutPort(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'URI scheme ftp requires an explicit port.'
        );

        RequestContext::fromUri(
            new ComponentUri(scheme: 'ftp', host: 'example.com')
        );
    }
}
