<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Interceptor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\ClientConfig;
use Smithy\Client\Endpoint\StaticEndpointResolver;
use Smithy\Client\Interceptor\OutputContext;
use Smithy\Core\Endpoint\Endpoint;
use Smithy\Http\Client\HttpClientTransport;
use Smithy\Tests\Client\Fake\{
    FakeInput,
    FakeOperation,
    FakeOutput,
    FakeProtocol
};
use Smithy\Tests\Utils\Http\MockHttpClient;

#[CoversClass(OutputContext::class)]
final class OutputContextTest extends TestCase
{
    public function testGetOutputReturnsOutput(): void
    {
        $output = new FakeOutput();

        self::assertSame($output, $this->context($output)->getOutput());
    }

    public function testGetOutputThrowsWhenOutputIsNull(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Output is not set.');

        $this->context(null)->getOutput();
    }

    private function context(?FakeOutput $output): OutputContext
    {
        return new OutputContext(
            FakeOperation::instance(),
            new FakeInput(),
            new ClientConfig(
                protocol: new FakeProtocol(),
                transport: new HttpClientTransport(new MockHttpClient()),
                endpointResolver: new StaticEndpointResolver(
                    new Endpoint('https://example.test')
                )
            ),
            null,
            null,
            $output
        );
    }
}
