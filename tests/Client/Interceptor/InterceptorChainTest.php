<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Interceptor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface
};
use Smithy\Client\ClientConfig;
use Smithy\Client\Endpoint\StaticEndpointResolver;
use Smithy\Client\Interceptor\{
    AbstractInterceptor,
    CallContext,
    HttpChecksumInterceptor,
    IdempotencyTokenInterceptor,
    InputContext,
    InterceptorChain,
    InterceptorInterface,
    OutputContext,
    RequestCompressionInterceptor,
    RequestContext,
    ResponseContext
};
use Smithy\Core\Endpoint\Endpoint;
use Smithy\Core\Transport\{
    TransportRequestInterface,
    TransportResponseInterface
};
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Http\Message\{
    HttpTransportRequest,
    HttpTransportResponse
};
use Smithy\Tests\Client\Fake\{
    FakeInput,
    FakeOperation,
    FakeOutput,
    FakeProtocol,
    RecordingInterceptor
};
use Smithy\Tests\Utils\Http\MockHttpClient;

#[CoversClass(InterceptorChain::class)]
final class InterceptorChainTest extends TestCase
{
    public function testModifyBeforeCallThreadsConfiguration(): void
    {
        $alternate = new FakeProtocol(requestPrefix: 'alternate:');
        $first = new class extends AbstractInterceptor {
            public function modifyBeforeCall(
                CallContext $ctx
            ): ClientConfig
            {
                return $ctx->config->withContext('first', true);
            }
        };
        $second = new class($alternate) extends AbstractInterceptor {
            public function __construct(
                private readonly FakeProtocol $protocol
            ) {}

            public function modifyBeforeCall(
                CallContext $ctx
            ): ClientConfig
            {
                \PHPUnit\Framework\Assert::assertTrue(
                    $ctx->config->context['first']
                );

                return $ctx->config->withProtocol($this->protocol);
            }
        };
        $config = $this->inputContext()->config;

        $result = (new InterceptorChain($first, $second))
            ->modifyBeforeCall(new CallContext(
                FakeOperation::instance(),
                new FakeInput(),
                $config
            ));

        self::assertTrue($result->context['first']);
        self::assertSame($alternate, $result->getProtocol());
    }

    public function testCallWrappersNestAndSkipNonWrappingInterceptors(): void
    {
        $calls = new \ArrayObject();
        $outer = $this->callWrapper('outer', $calls);
        $ordinary = $this->getMockBuilder(AbstractInterceptor::class)
            ->onlyMethods(['interceptCall'])
            ->getMock();
        $ordinary->expects(self::never())->method('interceptCall');
        $inner = $this->callWrapper('inner', $calls);
        $output = new FakeOutput('output');
        $terminal = function (InputContext $ctx) use ($calls, $output) {
            $calls[] = 'call';

            return $output;
        };
        $chain = new InterceptorChain($outer, $ordinary, $inner);

        self::assertTrue($chain->interceptCalls());
        self::assertSame(
            $output,
            $chain->interceptCall($this->inputContext(), $terminal)
        );
        self::assertSame([
            'outer-before',
            'inner-before',
            'call',
            'inner-after',
            'outer-after'
        ], $calls->getArrayCopy());
    }

    public function testNextCallCanBeInvokedMoreThanOnce(): void
    {
        $calls = new \ArrayObject();
        $duplicator = new class extends AbstractInterceptor {
            public function interceptCalls(): bool
            {
                return true;
            }

            public function interceptCall(
                InputContext $ctx,
                callable $next
            ): SerializableStructInterface
            {
                $next($ctx);

                return $next($ctx);
            }
        };
        $inner = $this->callWrapper('inner', $calls);
        $terminal = function (InputContext $ctx) use ($calls) {
            $calls[] = 'call';

            return new FakeOutput();
        };

        (new InterceptorChain($duplicator, $inner))
            ->interceptCall($this->inputContext(), $terminal);

        self::assertSame([
            'inner-before',
            'call',
            'inner-after',
            'inner-before',
            'call',
            'inner-after'
        ], $calls->getArrayCopy());
    }

    public function testAbstractInterceptorDoesNotWrapCallsByDefault(): void
    {
        $interceptor = new IdempotencyTokenInterceptor();
        $chain = new InterceptorChain($interceptor);
        $output = new FakeOutput();

        self::assertFalse($chain->interceptCalls());
        self::assertSame(
            $output,
            $interceptor->interceptCall(
                $this->inputContext(),
                static fn(InputContext $ctx): FakeOutput => $output
            )
        );
    }

    public function testReportsWhetherChainIsEmpty(): void
    {
        self::assertTrue((new InterceptorChain())->isEmpty());
        self::assertFalse(
            (new InterceptorChain(new RecordingInterceptor()))->isEmpty()
        );
    }

    public function testForOperationDropsInterceptorsThatDoNotApply(): void
    {
        $skipped = new class extends AbstractInterceptor {
            public function appliesTo(OperationInterface $operation): bool
            {
                return false;
            }
        };
        $chain = new InterceptorChain(new RecordingInterceptor(), $skipped);

        $filtered = $chain->forOperation(FakeOperation::instance());

        self::assertNotSame($chain, $filtered);
        self::assertTrue($filtered->contains(RecordingInterceptor::class));
        self::assertFalse($filtered->contains($skipped::class));
        self::assertSame(
            $filtered,
            $chain->forOperation(FakeOperation::instance())
        );
    }

    public function testForOperationKeepsChainWhenAllInterceptorsApply(): void
    {
        $chain = new InterceptorChain(new RecordingInterceptor());

        self::assertSame(
            $chain,
            $chain->forOperation(FakeOperation::instance())
        );
    }

    public function testForOperationDropsBuiltInsWithoutMatchingTraits(): void
    {
        $chain = new InterceptorChain(
            new IdempotencyTokenInterceptor(),
            new RequestCompressionInterceptor(),
            new HttpChecksumInterceptor()
        );

        self::assertTrue(
            $chain->forOperation(FakeOperation::instance())->isEmpty()
        );
    }

    public function testFindsAssignableInterceptorClasses(): void
    {
        $chain = new InterceptorChain(new RecordingInterceptor());

        self::assertTrue($chain->contains(RecordingInterceptor::class));
        self::assertTrue($chain->contains(AbstractInterceptor::class));
        self::assertTrue($chain->contains(InterceptorInterface::class));
        self::assertFalse(
            $chain->contains(IdempotencyTokenInterceptor::class)
        );
    }

    public function testAddsInterceptorWithoutChangingOriginalChain(): void
    {
        $first = new RecordingInterceptor('first');
        $second = new RecordingInterceptor('second');
        $original = new InterceptorChain($first);
        $added = $original->add($second);
        $ctx = $this->inputContext();

        $original->readBeforeExecution($ctx);

        self::assertSame(['first:readBeforeExecution'], $first->calls);
        self::assertSame([], $second->calls);

        $first->calls = [];
        $added->readBeforeExecution($ctx);

        self::assertSame(['first:readBeforeExecution'], $first->calls);
        self::assertSame(['second:readBeforeExecution'], $second->calls);
    }

    public function testPrependsInterceptorWithoutChangingOriginalChain(): void
    {
        $existing = new RecordingInterceptor('existing');
        $prepended = new RecordingInterceptor('prepended');
        $original = new InterceptorChain($existing);
        $updated = $original->prepend($prepended);
        $error = new \RuntimeException('error');
        $ctx = $this->outputContext();

        try {
            $updated->modifyBeforeAttemptCompletion($ctx, $error);
            self::fail('Expected the completion error to be forwarded.');
        } catch (\RuntimeException $e) {
            self::assertSame($error, $e);
        }

        self::assertSame(
            ['prepended:modifyBeforeAttemptCompletion'],
            $prepended->calls
        );
        self::assertSame(
            ['existing:modifyBeforeAttemptCompletion'],
            $existing->calls
        );

        $existing->calls = [];
        try {
            $original->modifyBeforeAttemptCompletion($ctx, $error);
            self::fail('Expected the completion error to be forwarded.');
        } catch (\RuntimeException $e) {
            self::assertSame($error, $e);
        }

        self::assertSame(
            ['existing:modifyBeforeAttemptCompletion'],
            $existing->calls
        );
    }

    public function testDispatchesReadHooks(): void
    {
        $recorder = new RecordingInterceptor();
        $chain = new InterceptorChain($recorder);
        $inputCtx = $this->inputContext();
        $requestCtx = $this->requestContext();
        $responseCtx = $this->responseContext();
        $outputCtx = $this->outputContext();

        $chain->readBeforeExecution($inputCtx);
        $chain->readBeforeSerialization($inputCtx);
        $chain->readAfterSerialization($requestCtx);
        $chain->readBeforeAttempt($requestCtx);
        $chain->readBeforeSigning($requestCtx);
        $chain->readAfterSigning($requestCtx);
        $chain->readBeforeTransmit($requestCtx);
        $chain->readAfterTransmit($responseCtx);
        $chain->readBeforeDeserialization($responseCtx);
        $chain->readAfterDeserialization($outputCtx, null);
        $chain->readAfterAttempt($outputCtx, null);
        $chain->readAfterExecution($outputCtx, null);

        self::assertSame([
            'readBeforeExecution',
            'readBeforeSerialization',
            'readAfterSerialization',
            'readBeforeAttempt',
            'readBeforeSigning',
            'readAfterSigning',
            'readBeforeTransmit',
            'readAfterTransmit',
            'readBeforeDeserialization',
            'readAfterDeserialization',
            'readAfterAttempt',
            'readAfterExecution'
        ], $recorder->calls);
    }

    public function testThreadsModifiedValuesThroughStages(): void
    {
        $first = $this->createMock(InterceptorInterface::class);
        $second = $this->createMock(InterceptorInterface::class);
        $chain = new InterceptorChain($first, $second);

        $inputOne = new FakeInput('one');
        $inputTwo = new FakeInput('two');
        $first->method('modifyBeforeSerialization')->willReturn($inputOne);
        $second->method('modifyBeforeSerialization')->willReturnCallback(
            function (InputContext $ctx) use ($inputOne, $inputTwo) {
                self::assertSame($inputOne, $ctx->input);

                return $inputTwo;
            }
        );

        $requestZero = new HttpTransportRequest(
            $this->createStub(RequestInterface::class)
        );
        $requestOne = new HttpTransportRequest(
            $this->createStub(RequestInterface::class)
        );
        $requestTwo = new HttpTransportRequest(
            $this->createStub(RequestInterface::class)
        );
        $this->configureRequestStage(
            $first,
            $second,
            'modifyBeforeRetryLoop',
            $requestOne,
            $requestTwo
        );
        $this->configureRequestStage(
            $first,
            $second,
            'modifyBeforeSigning',
            $requestOne,
            $requestTwo
        );
        $this->configureRequestStage(
            $first,
            $second,
            'modifyBeforeTransmit',
            $requestOne,
            $requestTwo
        );

        $responseZero = new HttpTransportResponse(
            $this->createStub(ResponseInterface::class)
        );
        $responseOne = new HttpTransportResponse(
            $this->createStub(ResponseInterface::class)
        );
        $responseTwo = new HttpTransportResponse(
            $this->createStub(ResponseInterface::class)
        );
        $first->method('modifyBeforeDeserialization')
            ->willReturn($responseOne);
        $second->method('modifyBeforeDeserialization')->willReturnCallback(
            function (ResponseContext $ctx) use ($responseOne, $responseTwo) {
                self::assertSame($responseOne, $ctx->response);

                return $responseTwo;
            }
        );

        $outputZero = new FakeOutput('zero');
        $outputOne = new FakeOutput('one');
        $outputTwo = new FakeOutput('two');
        $this->configureOutputStage(
            $first,
            $second,
            'modifyBeforeAttemptCompletion',
            $outputOne,
            $outputTwo
        );
        $this->configureOutputStage(
            $first,
            $second,
            'modifyBeforeCompletion',
            $outputOne,
            $outputTwo
        );

        $inputZero = new FakeInput('zero');
        self::assertSame(
            $inputTwo,
            $chain->modifyBeforeSerialization($this->inputContext($inputZero))
        );
        self::assertSame('zero', $inputZero->value);

        $requestCtx = $this->requestContext($requestZero);
        self::assertSame(
            $requestTwo,
            $chain->modifyBeforeRetryLoop($requestCtx)
        );
        self::assertSame(
            $requestTwo,
            $chain->modifyBeforeSigning($requestCtx)
        );
        self::assertSame(
            $requestTwo,
            $chain->modifyBeforeTransmit($requestCtx)
        );

        self::assertSame(
            $responseTwo,
            $chain->modifyBeforeDeserialization(
                $this->responseContext($requestZero, $responseZero)
            )
        );

        $outputCtx = $this->outputContext(
            $requestZero,
            $responseZero,
            $outputZero
        );
        self::assertSame(
            $outputTwo,
            $chain->modifyBeforeAttemptCompletion($outputCtx, null)
        );
        self::assertSame(
            $outputTwo,
            $chain->modifyBeforeCompletion($outputCtx, null)
        );
    }

    public function testCompletionStagesClearErrorAfterOutputReplacement(): void
    {
        $error = new \RuntimeException('original');
        $first = $this->createMock(InterceptorInterface::class);
        $second = $this->createMock(InterceptorInterface::class);
        $chain = new InterceptorChain($first, $second);
        $outputOne = new FakeOutput('one');
        $outputTwo = new FakeOutput('two');
        $this->configureOutputStage(
            $first,
            $second,
            'modifyBeforeAttemptCompletion',
            $outputOne,
            $outputTwo,
            $error
        );
        $this->configureOutputStage(
            $first,
            $second,
            'modifyBeforeCompletion',
            $outputOne,
            $outputTwo,
            $error
        );

        self::assertSame(
            $outputTwo,
            $chain->modifyBeforeAttemptCompletion(
                $this->outputContext(),
                $error
            )
        );
        self::assertSame(
            $outputTwo,
            $chain->modifyBeforeCompletion($this->outputContext(), $error)
        );
    }

    public function testReadBeforeExecutionThrowsLatestError(): void
    {
        $firstError = new \RuntimeException('first');
        $secondError = new \RuntimeException('second');
        $first = $this->createMock(InterceptorInterface::class);
        $second = $this->createMock(InterceptorInterface::class);
        $first->expects(self::once())
            ->method('readBeforeExecution')
            ->willThrowException($firstError);
        $second->expects(self::once())
            ->method('readBeforeExecution')
            ->willThrowException($secondError);

        $this->expectExceptionObject($secondError);

        (new InterceptorChain($first, $second))
            ->readBeforeExecution($this->inputContext());
    }

    public function testReadBeforeAttemptThrowsLatestError(): void
    {
        $firstError = new \RuntimeException('first');
        $secondError = new \RuntimeException('second');
        $first = $this->createMock(InterceptorInterface::class);
        $second = $this->createMock(InterceptorInterface::class);
        $first->expects(self::once())
            ->method('readBeforeAttempt')
            ->willThrowException($firstError);
        $second->expects(self::once())
            ->method('readBeforeAttempt')
            ->willThrowException($secondError);

        $this->expectExceptionObject($secondError);

        (new InterceptorChain($first, $second))
            ->readBeforeAttempt($this->requestContext());
    }

    public function testOrdinaryReadHookStopsAtFirstError(): void
    {
        $error = new \RuntimeException('stop');
        $first = $this->createMock(InterceptorInterface::class);
        $second = $this->createMock(InterceptorInterface::class);
        $first->expects(self::once())
            ->method('readBeforeSerialization')
            ->willThrowException($error);
        $second->expects(self::never())->method('readBeforeSerialization');

        $this->expectExceptionObject($error);

        (new InterceptorChain($first, $second))
            ->readBeforeSerialization($this->inputContext());
    }

    public function testReadAfterAttemptKeepsOriginalError(): void
    {
        $error = new \RuntimeException('attempt');
        $first = $this->createMock(InterceptorInterface::class);
        $second = $this->createMock(InterceptorInterface::class);
        $first->expects(self::once())
            ->method('readAfterAttempt')
            ->with(self::anything(), $error);
        $second->expects(self::once())
            ->method('readAfterAttempt')
            ->with(self::anything(), $error);

        (new InterceptorChain($first, $second))
            ->readAfterAttempt($this->outputContext(), $error);
    }

    public function testReadAfterAttemptThrowsLatestReplacementError(): void
    {
        $original = new \RuntimeException('original');
        $firstError = new \RuntimeException('first');
        $secondError = new \RuntimeException('second');
        $first = $this->createMock(InterceptorInterface::class);
        $second = $this->createMock(InterceptorInterface::class);
        $first->expects(self::once())
            ->method('readAfterAttempt')
            ->with(self::anything(), $original)
            ->willThrowException($firstError);
        $second->expects(self::once())
            ->method('readAfterAttempt')
            ->with(self::anything(), $firstError)
            ->willThrowException($secondError);

        $this->expectExceptionObject($secondError);

        (new InterceptorChain($first, $second))
            ->readAfterAttempt($this->outputContext(), $original);
    }

    public function testReadAfterExecutionThrowsOriginalError(): void
    {
        $error = new \RuntimeException('execution');
        $interceptor = $this->createMock(InterceptorInterface::class);
        $interceptor->expects(self::once())
            ->method('readAfterExecution')
            ->with(self::anything(), $error);

        $this->expectExceptionObject($error);

        (new InterceptorChain($interceptor))
            ->readAfterExecution($this->outputContext(), $error);
    }

    public function testReadAfterExecutionThrowsLatestReplacementError(): void
    {
        $original = new \RuntimeException('original');
        $firstError = new \RuntimeException('first');
        $secondError = new \RuntimeException('second');
        $first = $this->createMock(InterceptorInterface::class);
        $second = $this->createMock(InterceptorInterface::class);
        $first->expects(self::once())
            ->method('readAfterExecution')
            ->with(self::anything(), $original)
            ->willThrowException($firstError);
        $second->expects(self::once())
            ->method('readAfterExecution')
            ->with(self::anything(), $firstError)
            ->willThrowException($secondError);

        $this->expectExceptionObject($secondError);

        (new InterceptorChain($first, $second))
            ->readAfterExecution($this->outputContext(), $original);
    }

    public function testEmptyCompletionStagesForwardOutput(): void
    {
        $output = new FakeOutput('output');
        $ctx = $this->outputContext(output: $output);
        $chain = new InterceptorChain();

        self::assertSame(
            $output,
            $chain->modifyBeforeAttemptCompletion($ctx, null)
        );
        self::assertSame(
            $output,
            $chain->modifyBeforeCompletion($ctx, null)
        );
    }

    public function testEmptyAttemptCompletionForwardsError(): void
    {
        $error = new \RuntimeException('attempt');

        $this->expectExceptionObject($error);

        (new InterceptorChain())->modifyBeforeAttemptCompletion(
            $this->outputContext(),
            $error
        );
    }

    public function testEmptyExecutionCompletionForwardsError(): void
    {
        $error = new \RuntimeException('execution');

        $this->expectExceptionObject($error);

        (new InterceptorChain())->modifyBeforeCompletion(
            $this->outputContext(),
            $error
        );
    }

    #[DataProvider('completionChainProvider')]
    public function testAttemptCompletionRejectsMissingOutput(
        InterceptorChain $chain
    ): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Output is not set.');

        $chain->modifyBeforeAttemptCompletion(
            $this->outputContext()->withOutput(null),
            null
        );
    }

    #[DataProvider('completionChainProvider')]
    public function testExecutionCompletionRejectsMissingOutput(
        InterceptorChain $chain
    ): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Output is not set.');

        $chain->modifyBeforeCompletion(
            $this->outputContext()->withOutput(null),
            null
        );
    }

    /**
     * Provide an empty chain and one that runs the default completion hooks.
     *
     * @return iterable<string, array{InterceptorChain}>
     */
    public static function completionChainProvider(): iterable
    {
        yield 'empty chain' => [new InterceptorChain()];
        yield 'default hooks' => [
            new InterceptorChain(new RecordingInterceptor())
        ];
    }

    public function testAttemptCompletionRecoversAfterForwardedError(): void
    {
        $error = new \RuntimeException('attempt');
        $output = new FakeOutput('recovered');
        $rescuer = $this->createMock(InterceptorInterface::class);
        $rescuer->expects(self::once())
            ->method('modifyBeforeAttemptCompletion')
            ->with(self::isInstanceOf(OutputContext::class), $error)
            ->willReturn($output);

        $result = (new InterceptorChain(
            new IdempotencyTokenInterceptor(),
            $rescuer
        ))->modifyBeforeAttemptCompletion(
            $this->outputContext(),
            $error
        );

        self::assertSame($output, $result);
    }

    public function testExecutionCompletionRecoversAfterForwardedError(): void
    {
        $error = new \RuntimeException('execution');
        $output = new FakeOutput('recovered');
        $rescuer = $this->createMock(InterceptorInterface::class);
        $rescuer->expects(self::once())
            ->method('modifyBeforeCompletion')
            ->with(self::isInstanceOf(OutputContext::class), $error)
            ->willReturn($output);

        $result = (new InterceptorChain(
            new IdempotencyTokenInterceptor(),
            $rescuer
        ))->modifyBeforeCompletion(
            $this->outputContext(),
            $error
        );

        self::assertSame($output, $result);
    }

    public function testAttemptCompletionStopsAtFirstError(): void
    {
        $error = new \RuntimeException('stop');
        $first = $this->createMock(InterceptorInterface::class);
        $second = $this->createMock(InterceptorInterface::class);
        $first->expects(self::once())
            ->method('modifyBeforeAttemptCompletion')
            ->willThrowException($error);
        $second->expects(self::never())
            ->method('modifyBeforeAttemptCompletion');

        $this->expectExceptionObject($error);

        (new InterceptorChain($first, $second))
            ->modifyBeforeAttemptCompletion($this->outputContext(), null);
    }

    public function testExecutionCompletionStopsAtFirstError(): void
    {
        $error = new \RuntimeException('stop');
        $first = $this->createMock(InterceptorInterface::class);
        $second = $this->createMock(InterceptorInterface::class);
        $first->expects(self::once())
            ->method('modifyBeforeCompletion')
            ->willThrowException($error);
        $second->expects(self::never())->method('modifyBeforeCompletion');

        $this->expectExceptionObject($error);

        (new InterceptorChain($first, $second))
            ->modifyBeforeCompletion($this->outputContext(), null);
    }

    private function configureRequestStage(
        InterceptorInterface $first,
        InterceptorInterface $second,
        string $method,
        TransportRequestInterface $firstRequest,
        TransportRequestInterface $secondRequest
    ): void
    {
        $first->method($method)->willReturn($firstRequest);
        $second->method($method)->willReturnCallback(
            function (RequestContext $ctx) use (
                $firstRequest,
                $secondRequest
            ) {
                self::assertSame($firstRequest, $ctx->request);

                return $secondRequest;
            }
        );
    }

    private function configureOutputStage(
        InterceptorInterface $first,
        InterceptorInterface $second,
        string $method,
        FakeOutput $firstOutput,
        FakeOutput $secondOutput,
        ?\Throwable $expectedError = null
    ): void
    {
        $first->method($method)->willReturnCallback(
            function (
                OutputContext $ctx,
                ?\Throwable $error
            ) use ($firstOutput, $expectedError) {
                self::assertSame($expectedError, $error);

                return $firstOutput;
            }
        );
        $second->method($method)->willReturnCallback(
            function (
                OutputContext $ctx,
                ?\Throwable $error
            ) use (
                $firstOutput,
                $secondOutput
            ) {
                self::assertSame($firstOutput, $ctx->output);
                self::assertNull($error);

                return $secondOutput;
            }
        );
    }

    private function inputContext(?FakeInput $input = null): InputContext
    {
        return new InputContext(
            FakeOperation::instance(),
            $input ?? new FakeInput(),
            $this->config()
        );
    }

    private function callWrapper(
        string $name,
        \ArrayObject $calls
    ): AbstractInterceptor
    {
        return new class($name, $calls) extends AbstractInterceptor {
            public function __construct(
                private readonly string $name,
                private readonly \ArrayObject $calls
            ) {}

            public function interceptCalls(): bool
            {
                return true;
            }

            public function interceptCall(
                InputContext $ctx,
                callable $next
            ): SerializableStructInterface
            {
                $this->calls[] = "{$this->name}-before";

                try {
                    return $next($ctx);
                } finally {
                    $this->calls[] = "{$this->name}-after";
                }
            }
        };
    }

    private function requestContext(
        ?TransportRequestInterface $request = null
    ): RequestContext
    {
        return new RequestContext(
            FakeOperation::instance(),
            new FakeInput(),
            $this->config(),
            $request ?? new HttpTransportRequest(
                $this->createStub(RequestInterface::class)
            )
        );
    }

    private function responseContext(
        ?TransportRequestInterface $request = null,
        ?TransportResponseInterface $response = null
    ): ResponseContext
    {
        return new ResponseContext(
            FakeOperation::instance(),
            new FakeInput(),
            $this->config(),
            $request ?? new HttpTransportRequest(
                $this->createStub(RequestInterface::class)
            ),
            $response ?? new HttpTransportResponse(
                $this->createStub(ResponseInterface::class)
            )
        );
    }

    private function outputContext(
        ?TransportRequestInterface $request = null,
        ?TransportResponseInterface $response = null,
        ?FakeOutput $output = null
    ): OutputContext
    {
        return new OutputContext(
            FakeOperation::instance(),
            new FakeInput(),
            $this->config(),
            $request,
            $response,
            $output ?? new FakeOutput()
        );
    }

    private function config(): ClientConfig
    {
        return new ClientConfig(
            protocol: new FakeProtocol(),
            transport: new \Smithy\Http\Client\HttpClientTransport(
                new MockHttpClient()
            ),
            endpointResolver: new StaticEndpointResolver(
                new Endpoint('https://example.test')
            )
        );
    }
}
