<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Retry;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Retry\{
    RetryState,
    StandardRetryStrategy,
    TokenAcquisitionFailedException
};
use Smithy\Codec\Json\JsonCodec;
use Smithy\Core\Exception\{ApiException, ErrorFault};
use Smithy\Core\Shape\OperationInterface;
use Smithy\Http\Message\Response;
use Smithy\Protocol\Aws\RestJsonErrorDeserializer;
use Smithy\Tests\Protocol\Utils\SdkBaseAwsError;

/**
 * Run the standard-mode test cases of the new-retries SEP (Appendix A).
 *
 * The cases live in `fixtures/standard-mode-cases.json`. Each response with
 * a 5xx status is a server fault, and a 2xx status is a success. Jitter is
 * pinned so a delay equals the computed backoff. The SEP charges 5 tokens
 * per retry, which this test passes as the retry cost.
 *
 * The `header_cases` come from the same appendix and check how an
 * `x-amz-retry-after` response header sets the delay.
 */
#[CoversClass(StandardRetryStrategy::class)]
final class StandardModeCasesTest extends TestCase
{
    private const SCOPE = 'sep-scope';

    /**
     * @param array<string, int|float> $given
     * @param list<array{response: array{status_code: int}, expected: array<string, mixed>}> $responses
     */
    #[DataProvider('cases')]
    public function testStandardModeCase(array $given, array $responses): void
    {
        $strategy = $this->strategy($given);
        $state = $strategy->begin(self::SCOPE);

        foreach ($responses as $step) {
            if ($this->runStep($strategy, $state, $step)) {
                // A success ends the request. The next one starts fresh.
                $state = $strategy->begin(self::SCOPE);
            }
        }
    }

    /**
     * Share one strategy (the client quota) across separate requests.
     *
     * PHP runs one thread, so the threads are interleaved in the order the
     * SEP lists them, each with its own attempt state.
     *
     * @param array<string, int|float> $given
     * @param list<array{thread: string, responses: list<array<string, mixed>>}> $steps
     */
    #[DataProvider('sharedCases')]
    public function testSharedQuotaAcrossRequests(
        array $given,
        array $steps
    ): void
    {
        $strategy = $this->strategy($given);
        $states = [];

        foreach ($steps as $group) {
            $state = $states[$group['thread']]
                ??= $strategy->begin(self::SCOPE);
            foreach ($group['responses'] as $step) {
                if ($this->runStep($strategy, $state, $step)) {
                    unset($states[$group['thread']]);
                }
            }
        }
    }

    /**
     * @return iterable<string, array{array<string, int|float>, list<array<string, mixed>>}>
     */
    public static function cases(): iterable
    {
        foreach (self::fixture()['cases'] as $case) {
            yield $case['name'] => [$case['given'], $case['responses']];
        }
    }

    /**
     * Run a case whose responses carry `x-amz-retry-after`.
     *
     * Each failure goes through the AWS error deserializer, so the hint the
     * strategy sees is the one parsed from the response headers. The
     * strategy uses its default costs and backoff.
     *
     * @param list<array{response: array{status_code: int, headers?: array<string, string>}, expected: array<string, mixed>}> $responses
     */
    #[DataProvider('headerCases')]
    public function testRetryAfterHeaderCase(array $responses): void
    {
        $strategy = new StandardRetryStrategy(
            random: static fn(): float => 0.0
        );
        $state = $strategy->begin(self::SCOPE);

        foreach ($responses as $step) {
            $response = $step['response'];
            $error = $response['status_code'] < 400
                ? null
                : $this->deserializeError(
                    $response['status_code'],
                    $response['headers'] ?? []
                );
            $this->runStep($strategy, $state, $step, $error);
        }
    }

    /**
     * @return iterable<string, array{list<array<string, mixed>>}>
     */
    public static function headerCases(): iterable
    {
        foreach (self::fixture()['header_cases'] as $case) {
            yield $case['name'] => [$case['responses']];
        }
    }

    /**
     * @return iterable<string, array{array<string, int|float>, list<array<string, mixed>>}>
     */
    public static function sharedCases(): iterable
    {
        foreach (self::fixture()['shared_cases'] as $case) {
            yield $case['name'] => [$case['given'], $case['steps']];
        }
    }

    /**
     * @param array{response: array{status_code: int}, expected: array<string, mixed>} $step
     */
    private function runStep(
        StandardRetryStrategy $strategy,
        RetryState $state,
        array $step,
        ?\Throwable $error = null
    ): bool
    {
        $expected = $step['expected'];
        $status = $step['response']['status_code'];

        if ($status < 400) {
            $strategy->recordSuccess($state);
            self::assertSame('success', $expected['outcome']);
            self::assertSame($expected['retry_quota'], $state->capacityRemaining);
            return true;
        }

        try {
            $delay = $strategy->refresh(
                $state,
                $error
                    ?? new ApiException('status ' . $status, ErrorFault::Server)
            );
        } catch (TokenAcquisitionFailedException $e) {
            self::assertSame(
                $expected['outcome'],
                \str_contains($e->getMessage(), 'exhausted')
                    ? 'max_attempts_exceeded'
                    : 'retry_quota_exceeded'
            );
            self::assertSame($expected['retry_quota'], $state->capacityRemaining);
            return false;
        }

        self::assertSame('retry_request', $expected['outcome']);
        self::assertEqualsWithDelta($expected['delay'], $delay, 1e-9);
        self::assertSame($expected['retry_quota'], $state->capacityRemaining);

        return false;
    }

    /**
     * @param array<string, int|float> $given
     */
    private function strategy(array $given): StandardRetryStrategy
    {
        // The strategy doubles the delay on every retry (`br^i`, r = 2).
        self::assertSame(2, $given['exponential_power']);

        return new StandardRetryStrategy(
            maxAttempts: $given['max_attempts'],
            baseDelay: (float) $given['exponential_base'],
            maxBackoff: (float) $given['max_backoff_time'],
            retryCost: 5,
            initialRetryTokens: $given['initial_retry_tokens'],
            random: static fn(): float => 0.0
        );
    }

    /**
     * Build the error a REST JSON service returns for an unmodeled failure.
     *
     * @param array<string, string> $headers
     */
    private function deserializeError(int $status, array $headers): \Throwable
    {
        $operation = $this->createStub(OperationInterface::class);
        $operation->method('getServiceExceptionClass')
            ->willReturn(SdkBaseAwsError::class);

        return (new RestJsonErrorDeserializer(new JsonCodec()))->deserialize(
            $operation,
            new Response(
                $status,
                $headers + ['x-amzn-errortype' => 'InternalError'],
                '{"message":"status ' . $status . '"}'
            )
        );
    }

    /**
     * @return array{cases: list<array<string, mixed>>, shared_cases: list<array<string, mixed>>, header_cases: list<array<string, mixed>>}
     */
    private static function fixture(): array
    {
        return \json_decode(
            \file_get_contents(__DIR__ . '/fixtures/standard-mode-cases.json'),
            true,
            flags: \JSON_THROW_ON_ERROR
        );
    }
}
