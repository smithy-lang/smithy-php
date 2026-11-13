<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Plugin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Smithy\Client\ClientConfig;
use Smithy\Client\Endpoint\StaticEndpointResolver;
use Smithy\Client\Interceptor\{
    RequestCompressionInterceptor,
    RequestContext
};
use Smithy\Client\Plugin\RequestCompressionPlugin;
use Smithy\Core\Endpoint\Endpoint;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\Http\Message\{
    HttpTransportRequest,
    Request
};
use Smithy\Tests\Client\Fake\{
    FakeInput,
    FakeProtocol
};
use Smithy\Tests\Utils\Http\MockHttpClient;

#[CoversClass(RequestCompressionPlugin::class)]
final class RequestCompressionPluginTest extends TestCase
{
    private const DISABLE =
        RequestCompressionPlugin::CONFIG_DISABLE_REQUEST_COMPRESSION;
    private const MIN_SIZE =
        RequestCompressionPlugin::CONFIG_REQUEST_MIN_COMPRESSION_SIZE_BYTES;
    private const MAX_SIZE =
        RequestCompressionPlugin::MAX_MIN_COMPRESSION_SIZE_BYTES;

    public function testRegistersRequestCompressionInterceptor(): void
    {
        $config = (new RequestCompressionPlugin())->configure(
            $this->config()
        );

        self::assertTrue(
            $config->interceptorChain->contains(
                RequestCompressionInterceptor::class
            )
        );
    }

    public function testRespectsDisableSettingFromContext(): void
    {
        $updated = $this->requestAfterRetryLoop(
            new RequestCompressionPlugin(),
            [
                self::DISABLE => true
            ],
            \str_repeat('a', 11000)
        );

        self::assertFalse($updated->hasHeader('Content-Encoding'));
    }

    public function testAcceptsStringSettingsFromContext(): void
    {
        $updated = $this->requestAfterRetryLoop(
            new RequestCompressionPlugin(),
            [
                self::DISABLE => 'false',
                self::MIN_SIZE => '0'
            ],
            'payload'
        );

        self::assertSame(
            'gzip',
            $updated->getHeaderLine('Content-Encoding')
        );
    }

    public function testConstructorSettingsOverrideContext(): void
    {
        $updated = $this->requestAfterRetryLoop(
            new RequestCompressionPlugin(
                disabled: false,
                minCompressionSizeBytes: 0
            ),
            [
                self::DISABLE => true,
                self::MIN_SIZE => self::MAX_SIZE
            ],
            'payload'
        );

        self::assertSame(
            'gzip',
            $updated->getHeaderLine('Content-Encoding')
        );
    }

    public function testRejectsInvalidDisableSetting(): void
    {
        $config = $this->config([
            self::DISABLE => 'TRUE'
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'DisableRequestCompression must be true or false.'
        );

        (new RequestCompressionPlugin())->configure($config);
    }

    #[DataProvider('invalidMinimumCompressionSizeProvider')]
    public function testRejectsInvalidMinimumCompressionSize(
        mixed $size
    ): void
    {
        $config = $this->config([
            self::MIN_SIZE => $size
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'RequestMinCompressionSizeBytes must be an integer between '
            . '0 and 10485760.'
        );

        (new RequestCompressionPlugin())->configure($config);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function invalidMinimumCompressionSizeProvider(): iterable
    {
        yield 'empty string' => [''];
        yield 'non-numeric string' => ['ten'];
        yield 'negative integer' => [-1];
        yield 'integer above maximum' => [10485761];
        yield 'boolean' => [true];
    }

    /**
     * @param array<string, mixed> $context
     */
    private function requestAfterRetryLoop(
        RequestCompressionPlugin $plugin,
        array $context,
        string $body
    ): RequestInterface
    {
        $configured = $plugin->configure($this->config($context));
        $request = new Request(
            'POST',
            'https://example.test',
            [],
            $body
        );

        return HttpTransportRequest::expect(
            $configured->interceptorChain->modifyBeforeRetryLoop(
                self::requestContext(
                    $this->operation(),
                    new FakeInput(),
                    $configured,
                    $request
                )
            )
        )->request;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function config(array $context = []): ClientConfig
    {
        return new ClientConfig(
            protocol: new FakeProtocol(),
            transport: new \Smithy\Http\Client\HttpClientTransport(
                new MockHttpClient()
            ),
            endpointResolver: new StaticEndpointResolver(
                new Endpoint('https://example.test')
            ),
            context: $context
        );
    }

    private function operation(): OperationInterface
    {
        $operation = $this->createStub(OperationInterface::class);
        $operation->method('getSchema')->willReturn(new Schema(
            id: 'smithy.tests#CompressionOperation',
            type: ShapeType::Operation,
            traits: [Schema::T_REQUEST_COMPRESSION => ['gzip']]
        ));
        $operation->method('getInputStreamMember')->willReturn(null);

        return $operation;
    }

    private static function requestContext(
        OperationInterface $operation,
        FakeInput $input,
        ClientConfig $config,
        RequestInterface $request
    ): RequestContext {
        return new RequestContext(
            $operation,
            $input,
            $config,
            new HttpTransportRequest($request)
        );
    }
}
