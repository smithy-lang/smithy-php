<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Transport;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\{
    ClientConfig,
    ClientPipeline,
    RequestOverrideConfig
};
use Smithy\Client\Endpoint\{
    Endpoint,
    StaticEndpointResolver
};
use Smithy\Client\Interceptor\{
    AbstractInterceptor,
    InterceptorChain,
    OutputContext,
    RequestContext,
    ResponseContext
};
use Smithy\Client\Retry\{
    RetryState,
    RetryStrategyInterface
};
use Smithy\Client\Transport\{
    MessageExchangeInterface,
    TransportContext,
    TransportInterface,
    TransportOptionsInterface,
    TransportRequestInterface,
    TransportRequestOverrideInterface,
    TransportResponseInterface
};
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Http\Client\{
    HttpClientTransport,
    HttpRequestOptions
};
use Smithy\Http\Message\HttpMessageExchange;
use Smithy\Protocol\ProtocolInterface;
use Smithy\Tests\Client\Fake\{
    FakeInput,
    FakeOperation,
    FakeOutput
};
use Smithy\Tests\Utils\Http\MockHttpClient;

#[CoversClass(ClientPipeline::class)]
#[CoversClass(ClientConfig::class)]
#[CoversClass(RequestOverrideConfig::class)]
final class TransportPipelineTest extends TestCase
{
    public function testExecutesWithoutHttpMessages(): void
    {
        $transport = new TestTransport();
        $config = new ClientConfig(
            protocol: new TestProtocol(),
            transport: $transport,
            endpointResolver: new StaticEndpointResolver(
                new Endpoint('test://service')
            )
        );

        $output = (new ClientPipeline($config))->execute(
            FakeOperation::instance(),
            new FakeInput('hello')
        );

        self::assertInstanceOf(FakeOutput::class, $output);
        self::assertSame('output:input:hello', $output->value);
        self::assertSame($transport, $config->transport);
        self::assertSame('test://service', $transport->request?->endpoint);
        self::assertSame('input:hello', $transport->request?->payload);
    }

    public function testExecutesInterceptorRetryPathWithoutHttpMessages(): void
    {
        $transport = new TestTransport(failuresRemaining: 1);
        $interceptor = new TestInterceptor();
        $retryStrategy = new TestRetryStrategy();
        $config = new ClientConfig(
            protocol: new TestProtocol(),
            transport: $transport,
            endpointResolver: new StaticEndpointResolver(
                new Endpoint('test://service')
            ),
            interceptors: new InterceptorChain($interceptor),
            retryStrategy: $retryStrategy
        );

        $output = (new ClientPipeline($config))->execute(
            FakeOperation::instance(),
            new FakeInput('hello')
        );

        self::assertInstanceOf(FakeOutput::class, $output);
        self::assertSame(
            'output:input:hello:retry-loop:transmit:intercepted',
            $output->value
        );
        self::assertSame(2, $transport->sendCalls);
        self::assertSame(1, $transport->prepareCalls);
        self::assertSame(
            'input:hello:retry-loop:transmit',
            $transport->retryRequest?->payload
        );
        self::assertNull($transport->retryResponse);
        self::assertSame(
            'first attempt failed',
            $transport->retryError?->getMessage()
        );
        self::assertSame(1, $retryStrategy->refreshCalls);
        self::assertSame(1, $retryStrategy->successCalls);
        self::assertSame([
            'after-serialization:input:hello',
            'before-retry-loop:input:hello',
            'before-attempt:input:hello:retry-loop',
            'before-transmit:input:hello:retry-loop',
            'after-attempt:error',
            'before-attempt:input:hello:retry-loop',
            'before-transmit:input:hello:retry-loop',
            'after-transmit:output:input:hello:retry-loop:transmit',
            'before-deserialization:output:input:hello:retry-loop:transmit',
            'after-attempt:success'
        ], $interceptor->events);
    }

    public function testRejectsIncompatibleProtocolAndTransport(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Protocol and transport message exchanges are incompatible.'
        );

        new ClientConfig(
            protocol: new TestProtocol(),
            transport: new HttpClientTransport(
                new MockHttpClient()
            ),
            endpointResolver: new StaticEndpointResolver(
                new Endpoint('https://example.test')
            )
        );
    }

    public function testRejectsHeaderOverridesForNonHttpTransport(): void
    {
        $overrides = RequestOverrideConfig::fromArray([
            RequestOverrideConfig::HTTP => [
                'headers' => ['x-test' => 'value']
            ]
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'HTTP request overrides require an HTTP transport.'
        );

        $overrides->resolveTransportOptions(new TestTransport());
    }

    public function testAcceptsOverridesForCompatibleCustomHttpTransport(): void
    {
        $overrides = RequestOverrideConfig::fromArray([
            RequestOverrideConfig::HTTP => [
                'headers' => ['x-test' => 'value'],
                'timeout' => 2.0
            ]
        ]);

        $options = $overrides->resolveTransportOptions(
            new TestHttpTransport(new HttpRequestOptions(timeout: 5.0))
        );

        self::assertInstanceOf(HttpRequestOptions::class, $options);
        self::assertSame(2.0, $options->timeout);
    }

    public function testRejectsHttpOptionsWithoutOptionCapability(): void
    {
        $overrides = RequestOverrideConfig::fromArray([
            RequestOverrideConfig::HTTP => ['timeout' => 2.0]
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'HTTP request option overrides require a transport that supports '
        );

        $overrides->resolveTransportOptions(new TestHttpTransport());
    }

    public function testAppliesCustomTransportOverrides(): void
    {
        $transport = new TestTransport();
        $config = new ClientConfig(
            protocol: new TestProtocol(),
            transport: $transport,
            endpointResolver: new StaticEndpointResolver(
                new Endpoint('test://service')
            )
        );
        $overrides = RequestOverrideConfig::fromArray([
            RequestOverrideConfig::TRANSPORT =>
                new TestRequestOverride('custom')
        ]);

        $output = (new ClientPipeline($config))->execute(
            FakeOperation::instance(),
            new FakeInput('hello'),
            $overrides
        );

        self::assertSame(
            'output:input:hello:custom',
            $output->value
        );
        self::assertInstanceOf(TestOptions::class, $transport->options);
        self::assertSame('custom', $transport->options->value);
    }
}

final readonly class TestRequest implements TransportRequestInterface
{
    public function __construct(
        public string $payload,
        public ?string $endpoint = null
    ) {}
}

final readonly class TestResponse implements TransportResponseInterface
{
    public function __construct(
        public string $payload
    ) {}
}

final readonly class TestOptions implements TransportOptionsInterface
{
    public function __construct(
        public string $value
    ) {}
}

final readonly class TestRequestOverride implements
    TransportRequestOverrideInterface
{
    public function __construct(
        private string $value
    ) {}

    public function resolveOptions(
        TransportInterface $transport
    ): ?TransportOptionsInterface
    {
        return new TestOptions($this->value);
    }

    public function applyToRequest(
        TransportRequestInterface $request
    ): TransportRequestInterface
    {
        \assert($request instanceof TestRequest);

        return new TestRequest(
            $request->payload . ':' . $this->value,
            $request->endpoint
        );
    }
}

final readonly class TestMessageExchange implements MessageExchangeInterface
{
    public function getRequestType(): string
    {
        return TestRequest::class;
    }

    public function getResponseType(): string
    {
        return TestResponse::class;
    }

    public function isCompatibleWith(
        MessageExchangeInterface $exchange
    ): bool
    {
        return $exchange->getRequestType() === $this->getRequestType()
            && $exchange->getResponseType() === $this->getResponseType();
    }
}

final class TestProtocol implements ProtocolInterface
{
    public function getId(): string
    {
        return 'smithy.tests#transportProtocol';
    }

    public function getMessageExchange(): MessageExchangeInterface
    {
        return new TestMessageExchange();
    }

    public function serializeRequest(
        OperationInterface $operation,
        SerializableStructInterface $input
    ): TransportRequestInterface
    {
        \assert($input instanceof FakeInput);

        return new TestRequest('input:' . $input->value);
    }

    public function setServiceEndpoint(
        TransportRequestInterface $request,
        Endpoint $endpoint
    ): TransportRequestInterface
    {
        \assert($request instanceof TestRequest);

        return new TestRequest($request->payload, $endpoint->url);
    }

    public function deserializeResponse(
        OperationInterface $operation,
        TransportResponseInterface $response
    ): SerializableStructInterface
    {
        \assert($response instanceof TestResponse);

        return new FakeOutput($response->payload);
    }
}

final class TestTransport implements TransportInterface
{
    public ?TestRequest $request = null;

    public int $sendCalls = 0;

    public int $prepareCalls = 0;

    public ?TestRequest $retryRequest = null;

    public ?TestResponse $retryResponse = null;

    public ?\Throwable $retryError = null;

    public ?TransportOptionsInterface $options = null;

    public function __construct(
        private int $failuresRemaining = 0
    ) {}

    public function getMessageExchange(): MessageExchangeInterface
    {
        return new TestMessageExchange();
    }

    public function getDefaultOptions(): ?TransportOptionsInterface
    {
        return null;
    }

    public function send(
        TransportRequestInterface $request,
        TransportContext $context
    ): TransportResponseInterface
    {
        \assert($request instanceof TestRequest);
        ++$this->sendCalls;
        $this->request = $request;
        $this->options = $context->options;
        if ($this->failuresRemaining > 0) {
            --$this->failuresRemaining;
            throw new \RuntimeException('first attempt failed');
        }

        return new TestResponse('output:' . $request->payload);
    }

    public function prepareRequestForRetry(
        TransportRequestInterface $request,
        ?TransportResponseInterface $response,
        TransportContext $context,
        \Throwable $error
    ): bool
    {
        \assert($request instanceof TestRequest);
        \assert($response === null || $response instanceof TestResponse);
        ++$this->prepareCalls;
        $this->retryRequest = $request;
        $this->retryResponse = $response;
        $this->retryError = $error;

        return true;
    }
}

final readonly class TestHttpTransport implements TransportInterface
{
    public function __construct(
        private ?TransportOptionsInterface $defaultOptions = null
    ) {}

    public function getMessageExchange(): MessageExchangeInterface
    {
        return HttpMessageExchange::instance();
    }

    public function getDefaultOptions(): ?TransportOptionsInterface
    {
        return $this->defaultOptions;
    }

    public function send(
        TransportRequestInterface $request,
        TransportContext $context
    ): TransportResponseInterface
    {
        throw new \LogicException('Not used by this test fixture.');
    }

    public function prepareRequestForRetry(
        TransportRequestInterface $request,
        ?TransportResponseInterface $response,
        TransportContext $context,
        \Throwable $error
    ): bool
    {
        return false;
    }
}

final class TestInterceptor extends AbstractInterceptor
{
    /** @var list<string> */
    public array $events = [];

    public function readAfterSerialization(RequestContext $ctx): void
    {
        \assert($ctx->request instanceof TestRequest);
        $this->events[] = 'after-serialization:' . $ctx->request->payload;
    }

    public function modifyBeforeRetryLoop(
        RequestContext $ctx
    ): TransportRequestInterface
    {
        \assert($ctx->request instanceof TestRequest);
        $this->events[] = 'before-retry-loop:' . $ctx->request->payload;

        return new TestRequest(
            $ctx->request->payload . ':retry-loop',
            $ctx->request->endpoint
        );
    }

    public function readBeforeAttempt(RequestContext $ctx): void
    {
        \assert($ctx->request instanceof TestRequest);
        $this->events[] = 'before-attempt:' . $ctx->request->payload;
    }

    public function modifyBeforeTransmit(
        RequestContext $ctx
    ): TransportRequestInterface
    {
        \assert($ctx->request instanceof TestRequest);
        $this->events[] = 'before-transmit:' . $ctx->request->payload;

        return new TestRequest(
            $ctx->request->payload . ':transmit',
            $ctx->request->endpoint
        );
    }

    public function readAfterTransmit(ResponseContext $ctx): void
    {
        \assert($ctx->response instanceof TestResponse);
        $this->events[] = 'after-transmit:' . $ctx->response->payload;
    }

    public function modifyBeforeDeserialization(
        ResponseContext $ctx
    ): TransportResponseInterface
    {
        \assert($ctx->response instanceof TestResponse);
        $this->events[] = 'before-deserialization:'
            . $ctx->response->payload;

        return new TestResponse($ctx->response->payload . ':intercepted');
    }

    public function readAfterAttempt(
        OutputContext $ctx,
        ?\Throwable $error
    ): void
    {
        $this->events[] = $error === null
            ? 'after-attempt:success'
            : 'after-attempt:error';
    }
}

final class TestRetryStrategy implements RetryStrategyInterface
{
    public int $refreshCalls = 0;

    public int $successCalls = 0;

    public function begin(string $scope, int $requestFlags = 0): RetryState
    {
        return new RetryState($scope, $requestFlags);
    }

    public function refresh(RetryState $state, \Throwable $error): float
    {
        ++$this->refreshCalls;
        ++$state->attempt;

        return 0.0;
    }

    public function recordSuccess(RetryState $state): void
    {
        ++$this->successCalls;
    }

    public function maxAttempts(): int
    {
        return 2;
    }
}
