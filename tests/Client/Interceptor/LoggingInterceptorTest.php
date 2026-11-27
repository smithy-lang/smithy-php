<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Interceptor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface
};
use Psr\Log\LogLevel;
use Smithy\Async\Async;
use Smithy\Async\EventLoopInterface;
use Smithy\Client\ClientConfig;
use Smithy\Client\ClientPipeline;
use Smithy\Client\Endpoint\StaticEndpointResolver;
use Smithy\Client\Interceptor\{
    InputContext,
    InterceptorChain,
    LoggingInterceptor,
    OutputContext,
    RequestContext
};
use Smithy\Client\Retry\{
    RetryState,
    RetryStrategyInterface
};
use Smithy\Core\Endpoint\Endpoint;
use Smithy\Http\Client\HttpClientTransport;
use Smithy\Http\Message\{
    HttpTransportRequest,
    HttpTransportResponse,
    Request,
    Response
};
use Smithy\Tests\Async\Utils\RecordingEventLoopFixture;
use Smithy\Tests\Client\Fake\{
    FakeInput,
    FakeOperation,
    FakeProtocol,
    FakeService
};
use Smithy\Tests\Client\Utils\{
    InputLogContextProviderFixture,
    RecordingLoggerFixture
};
use Smithy\Tests\Utils\Http\MockHttpClient;

#[CoversClass(LoggingInterceptor::class)]
final class LoggingInterceptorTest extends TestCase
{
    private EventLoopInterface $previousEventLoop;

    protected function setUp(): void
    {
        $this->previousEventLoop = Async::eventLoop();
        Async::configure(new RecordingEventLoopFixture());
    }

    protected function tearDown(): void
    {
        Async::configure($this->previousEventLoop);
    }

    public function testLogsCallAndAttemptOnSuccess(): void
    {
        $logger = new RecordingLoggerFixture();
        $http = (new MockHttpClient())->willReturn('server-body', 200);

        $this->pipeline(new LoggingInterceptor($logger), $http)->execute(
            FakeOperation::instance(),
            new FakeInput('hello')
        );

        self::assertSame(
            [
                LogLevel::DEBUG,
                LogLevel::DEBUG,
                LogLevel::DEBUG,
                LogLevel::INFO
            ],
            $logger->levels()
        );
        self::assertSame(
            [
                LoggingInterceptor::MESSAGE_CALL_STARTED,
                LoggingInterceptor::MESSAGE_ATTEMPT_STARTED,
                LoggingInterceptor::MESSAGE_ATTEMPT_SUCCEEDED,
                LoggingInterceptor::MESSAGE_CALL_SUCCEEDED
            ],
            $logger->messages()
        );

        $start = $logger->records[0]['context'];
        self::assertSame('Fake', $start['operation']);
        self::assertSame('smithy.tests#FakeService', $start['service']);

        $attempt = $logger->records[2]['context'];
        self::assertSame(1, $attempt['attempt']);
        self::assertSame(200, $attempt['statusCode']);
        self::assertSame('POST', $attempt['method']);
        self::assertSame('https://example.test/', $attempt['url']);
        self::assertIsFloat($attempt['durationMs']);
        self::assertArrayNotHasKey('requestHeaders', $attempt);

        $done = $logger->records[3]['context'];
        self::assertSame(1, $done['attempts']);
        self::assertIsFloat($done['durationMs']);
    }

    public function testLogsRetryAsWarningAndFinalFailureAsError(): void
    {
        $logger = new RecordingLoggerFixture();
        $http = (new MockHttpClient())
            ->queueException(new \RuntimeException('temporary'))
            ->queueException(new \RuntimeException('still failing'));

        $error = null;
        try {
            $this->pipeline(
                new LoggingInterceptor($logger),
                $http,
                $this->retryStrategy(2)
            )->execute(FakeOperation::instance(), new FakeInput('hello'));
        } catch (\RuntimeException $e) {
            $error = $e;
        }

        self::assertNotNull($error);
        self::assertSame(
            [
                LoggingInterceptor::MESSAGE_CALL_STARTED,
                LoggingInterceptor::MESSAGE_ATTEMPT_STARTED,
                LoggingInterceptor::MESSAGE_ATTEMPT_FAILED,
                LoggingInterceptor::MESSAGE_ATTEMPT_RETRYING,
                LoggingInterceptor::MESSAGE_ATTEMPT_FAILED,
                LoggingInterceptor::MESSAGE_CALL_FAILED
            ],
            $logger->messages()
        );
        self::assertSame(
            [
                LogLevel::DEBUG,
                LogLevel::DEBUG,
                LogLevel::DEBUG,
                LogLevel::WARNING,
                LogLevel::DEBUG,
                LogLevel::ERROR
            ],
            $logger->levels()
        );

        $retry = $logger->records[3]['context'];
        self::assertSame(2, $retry['attempt']);
        self::assertSame(\RuntimeException::class, $retry['errorType']);
        self::assertSame('temporary', $retry['errorMessage']);

        $failed = $logger->records[5]['context'];
        self::assertSame(2, $failed['attempts']);
        self::assertSame(\RuntimeException::class, $failed['errorType']);
        self::assertSame($error, $failed['exception']);
    }

    public function testRedactsSensitiveAndCustomHeaders(): void
    {
        $logger = new RecordingLoggerFixture();
        $interceptor = new LoggingInterceptor(
            $logger,
            redactedHeaders: ['X-Secret-Token'],
            logHeaders: true
        );

        $this->runAttempt(
            $interceptor,
            new Request('GET', 'https://example.test/a?sig=abc', [
                'Authorization' => 'Bearer secret',
                'Cookie' => 'session=1',
                'x-secret-token' => 'token',
                'Accept' => 'application/json'
            ]),
            new Response(200, [
                'Set-Cookie' => 'session=2',
                'Content-Type' => 'application/json'
            ])
        );

        $context = $logger->records[2]['context'];
        self::assertSame('https://example.test/a', $context['url']);
        self::assertSame(
            [
                'host' => ['example.test'],
                'authorization' => ['[REDACTED]'],
                'cookie' => ['[REDACTED]'],
                'x-secret-token' => ['[REDACTED]'],
                'accept' => ['application/json']
            ],
            \array_change_key_case($context['requestHeaders'])
        );
        self::assertSame(
            [
                'set-cookie' => ['[REDACTED]'],
                'content-type' => ['application/json']
            ],
            \array_change_key_case($context['responseHeaders'])
        );
        $encoded = \json_encode($logger->records, \JSON_THROW_ON_ERROR);
        foreach (['Bearer', 'session=', 'sig=abc', '"token"'] as $secret) {
            self::assertStringNotContainsString($secret, $encoded);
        }
    }

    public function testOmitsHeadersByDefault(): void
    {
        $logger = new RecordingLoggerFixture();

        $this->runAttempt(
            new LoggingInterceptor($logger),
            new Request('GET', 'https://example.test/', [
                'Authorization' => 'Bearer secret'
            ]),
            new Response(200)
        );

        self::assertArrayNotHasKey(
            'requestHeaders',
            $logger->records[2]['context']
        );
        self::assertArrayNotHasKey(
            'responseHeaders',
            $logger->records[2]['context']
        );
    }

    public function testMergesContextProviderOutput(): void
    {
        $logger = new RecordingLoggerFixture();
        $provider = new InputLogContextProviderFixture([
            'requestId' => 'req-1',
            'operation' => 'Overridden'
        ]);
        $interceptor = new LoggingInterceptor(
            $logger,
            contextProviders: [$provider]
        );

        $this->runAttempt(
            $interceptor,
            new Request('GET', 'https://example.test/'),
            new Response(200)
        );

        foreach ($logger->records as $record) {
            self::assertSame('req-1', $record['context']['requestId']);
            self::assertSame('hello', $record['context']['inputValue']);
            self::assertSame('Fake', $record['context']['operation']);
        }
        self::assertCount(3, $provider->contexts);
        self::assertInstanceOf(InputContext::class, $provider->contexts[0]);
        self::assertInstanceOf(RequestContext::class, $provider->contexts[1]);
        self::assertInstanceOf(OutputContext::class, $provider->contexts[2]);
    }

    public function testTracksConcurrentCallsSeparately(): void
    {
        Async::configure($this->previousEventLoop);
        $logger = new RecordingLoggerFixture();
        $failedOnce = false;
        $http = (new MockHttpClient())->fallbackHandler(
            static function (RequestInterface $request) use (
                &$failedOnce
            ): ResponseInterface {
                // Suspend so the two calls interleave.
                Async::delay(0.001);
                if ((string) $request->getBody() === 'input:a'
                    && !$failedOnce
                ) {
                    $failedOnce = true;

                    throw new \RuntimeException('temporary');
                }

                return new Response(200, [], 'server-body');
            }
        );
        $pipeline = $this->pipeline(
            new LoggingInterceptor(
                $logger,
                contextProviders: [new InputLogContextProviderFixture()]
            ),
            $http,
            $this->retryStrategy(2)
        );

        Async::all(
            static fn(): mixed => $pipeline->execute(
                FakeOperation::instance(),
                new FakeInput('a')
            ),
            static fn(): mixed => $pipeline->execute(
                FakeOperation::instance(),
                new FakeInput('b')
            )
        );

        $attemptsByInput = [];
        foreach ($logger->records as $record) {
            if ($record['message'] === LoggingInterceptor::MESSAGE_CALL_SUCCEEDED) {
                $context = $record['context'];
                $attemptsByInput[$context['inputValue']] = $context['attempts'];
            }
        }
        \ksort($attemptsByInput);
        self::assertSame(['a' => 2, 'b' => 1], $attemptsByInput);
    }

    private function pipeline(
        LoggingInterceptor $interceptor,
        MockHttpClient $http,
        ?RetryStrategyInterface $retryStrategy = null
    ): ClientPipeline
    {
        return new ClientPipeline(new ClientConfig(
            protocol: new FakeProtocol(),
            transport: new HttpClientTransport($http),
            endpointResolver: new StaticEndpointResolver(
                new Endpoint('https://example.test')
            ),
            interceptors: new InterceptorChain($interceptor),
            retryStrategy: $retryStrategy,
            service: FakeService::instance()
        ));
    }

    private function config(): ClientConfig
    {
        return new ClientConfig(
            protocol: new FakeProtocol(),
            transport: new HttpClientTransport(new MockHttpClient()),
            endpointResolver: new StaticEndpointResolver(
                new Endpoint('https://example.test')
            )
        );
    }

    /**
     * Run the call-start, attempt-start, and attempt-end hooks for one
     * attempt that sent `$request` and received `$response`.
     */
    private function runAttempt(
        LoggingInterceptor $interceptor,
        Request $request,
        Response $response
    ): void
    {
        $input = new FakeInput('hello');
        $config = $this->config();
        $transportRequest = new HttpTransportRequest($request);

        $interceptor->readBeforeExecution(new InputContext(
            FakeOperation::instance(),
            $input,
            $config
        ));
        $interceptor->readBeforeAttempt(new RequestContext(
            FakeOperation::instance(),
            $input,
            $config,
            $transportRequest
        ));
        $interceptor->readAfterAttempt(
            new OutputContext(
                FakeOperation::instance(),
                $input,
                $config,
                $transportRequest,
                new HttpTransportResponse($response),
                null
            ),
            null
        );
    }

    private function retryStrategy(int $maxAttempts): RetryStrategyInterface
    {
        $strategy = $this->createMock(RetryStrategyInterface::class);
        $strategy->method('begin')->willReturnCallback(
            static fn(
                string $scope,
                int $requestFlags = 0
            ): RetryState => new RetryState($scope, $requestFlags)
        );
        $strategy->method('refresh')->willReturn(0.0);
        $strategy->method('maxAttempts')->willReturn($maxAttempts);

        return $strategy;
    }
}
