<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Waiter;

use PHPUnit\Framework\TestCase;
use Smithy\Async\{
    Async,
    RevoltEventLoop
};
use Smithy\Client\Waiter\{
    Acceptor,
    Matcher,
    PathComparator,
    Waiter,
    WaiterFailureException
};
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Tests\Client\Pagination\Fixture\PaginationResult;
use Smithy\Tests\Client\Waiter\Fixture\{
    RecordingEventLoop,
    WaiterModeledError
};
final class WaiterTest extends TestCase
{
    protected function tearDown(): void
    {
        Async::configure(new RevoltEventLoop());
    }

    public function testReturnsOutputAcceptedAsSuccess(): void
    {
        $output = new PaginationResult('done');
        $waiter = $this->waiter(
            [$output],
            [
                new Acceptor(
                    Acceptor::SUCCESS,
                    Matcher::success(true)
                )
            ]
        );

        $result = $waiter->wait(new PaginationResult('request'), 10.0);

        $this->assertSame($output, $result->output);
        $this->assertNull($result->error);
    }

    public function testReturnsModeledErrorAcceptedAsSuccess(): void
    {
        $error = new WaiterModeledError('not found');
        $waiter = $this->waiter(
            [$error],
            [
                new Acceptor(
                    Acceptor::SUCCESS,
                    Matcher::errorType('WaiterModeledError')
                )
            ]
        );

        $result = $waiter->wait(new PaginationResult('request'), 10.0);

        $this->assertNull($result->output);
        $this->assertSame($error, $result->error);
    }

    public function testUnmodeledErrorFailsWaiter(): void
    {
        $error = new \RuntimeException('unavailable');
        $waiter = $this->waiter(
            [$error],
            [
                new Acceptor(
                    Acceptor::SUCCESS,
                    Matcher::success(false)
                )
            ]
        );

        try {
            $waiter->wait(new PaginationResult('request'), 10.0);
            $this->fail('Expected waiter failure.');
        } catch (WaiterFailureException $failure) {
            $this->assertStringContainsString(
                'unexpected polling error',
                $failure->getMessage()
            );
            $this->assertSame($error, $failure->getPrevious());
        }
    }

    public function testFirstMatchingAcceptorWins(): void
    {
        $waiter = $this->waiter(
            [new PaginationResult('done')],
            [
                new Acceptor(
                    Acceptor::FAILURE,
                    Matcher::success(true)
                ),
                new Acceptor(
                    Acceptor::SUCCESS,
                    Matcher::success(true)
                )
            ]
        );

        $this->expectException(WaiterFailureException::class);
        $this->expectExceptionMessage('terminal failure state');
        $waiter->wait(new PaginationResult('request'), 10.0);
    }

    public function testSuccessfulUnmatchedAttemptRetriesImplicitly(): void
    {
        $loop = new RecordingEventLoop();
        Async::configure($loop);
        $waiter = $this->waiter(
            [
                new PaginationResult('pending'),
                new PaginationResult('done')
            ],
            [
                new Acceptor(
                    Acceptor::SUCCESS,
                    Matcher::output(
                        'nextToken',
                        'done',
                        PathComparator::StringEquals
                    )
                )
            ],
            minDelay: 1,
            maxDelay: 1
        );

        $result = $waiter->wait(new PaginationResult('request'), 10.0);

        $this->assertSame('done', $result->output?->nextToken);
        $this->assertSame([1.0], $loop->delays);
    }

    public function testRetryAcceptorUsesAsyncDelay(): void
    {
        $loop = new RecordingEventLoop();
        Async::configure($loop);
        $waiter = $this->waiter(
            [
                new WaiterModeledError('pending'),
                new PaginationResult('done')
            ],
            [
                new Acceptor(
                    Acceptor::RETRY,
                    Matcher::errorType('WaiterModeledError')
                ),
                new Acceptor(
                    Acceptor::SUCCESS,
                    Matcher::success(true)
                )
            ],
            minDelay: 2,
            maxDelay: 2
        );

        $waiter->wait(new PaginationResult('request'), 10.0);

        $this->assertSame([2.0], $loop->delays);
    }

    public function testRemainingBudgetIsUsedBeforeFinalPoll(): void
    {
        $loop = new RecordingEventLoop();
        Async::configure($loop);
        $waiter = $this->waiter(
            [
                new PaginationResult('pending'),
                new PaginationResult('done')
            ],
            [
                new Acceptor(
                    Acceptor::SUCCESS,
                    Matcher::output(
                        'nextToken',
                        'done',
                        PathComparator::StringEquals
                    )
                )
            ],
            minDelay: 2,
            maxDelay: 120
        );

        $result = $waiter->wait(new PaginationResult('request'), 0.5);

        $this->assertSame('done', $result->output?->nextToken);
        $this->assertCount(1, $loop->delays);
        $this->assertGreaterThan(0.0, $loop->delays[0]);
        $this->assertLessThanOrEqual(0.5, $loop->delays[0]);
    }

    public function testUnmatchedErrorFailsWithoutRetry(): void
    {
        $error = new WaiterModeledError('denied');
        $waiter = $this->waiter(
            [$error],
            [
                new Acceptor(
                    Acceptor::SUCCESS,
                    Matcher::success(true)
                )
            ]
        );

        try {
            $waiter->wait(new PaginationResult('request'), 10.0);
            $this->fail('Expected waiter failure.');
        } catch (WaiterFailureException $failure) {
            $this->assertStringContainsString(
                'unmatched polling error',
                $failure->getMessage()
            );
            $this->assertSame(1, $failure->attempts);
            $this->assertSame($error, $failure->getPrevious());
        }
    }

    public function testTerminalFailurePreservesPollingError(): void
    {
        $error = new WaiterModeledError('failed');
        $waiter = $this->waiter(
            [$error],
            [
                new Acceptor(
                    Acceptor::FAILURE,
                    Matcher::success(false)
                )
            ]
        );

        try {
            $waiter->wait(new PaginationResult('request'), 10.0);
            $this->fail('Expected waiter failure.');
        } catch (WaiterFailureException $failure) {
            $this->assertSame(1, $failure->attempts);
            $this->assertSame($error, $failure->getPrevious());
        }
    }

    public function testFailureMessageIsExtractedFromOutput(): void
    {
        $waiter = $this->waiter(
            [new PaginationResult('failed', ['disk full', 'timed out'])],
            [
                new Acceptor(
                    Acceptor::FAILURE,
                    Matcher::success(true),
                    'items'
                )
            ]
        );

        $this->expectException(WaiterFailureException::class);
        $this->expectExceptionMessage('disk full; timed out');
        $waiter->wait(new PaginationResult('request'), 10.0);
    }

    public function testRequestCompletingAfterDeadlineTimesOut(): void
    {
        $poll = static function (
            SerializableStructInterface $input
        ): SerializableStructInterface {
            \usleep(5_000);

            return new PaginationResult('done');
        };
        $waiter = new Waiter(
            $poll,
            [
                new Acceptor(
                    Acceptor::SUCCESS,
                    Matcher::success(true)
                )
            ]
        );

        try {
            $waiter->wait(new PaginationResult('request'), 0.001);
            $this->fail('Expected waiter timeout.');
        } catch (WaiterFailureException $failure) {
            $this->assertStringContainsString(
                'timed out after 1 attempts',
                $failure->getMessage()
            );
            $this->assertSame(1, $failure->attempts);
            $this->assertGreaterThanOrEqual(
                0.001,
                $failure->elapsedTime
            );
        }
    }

    public function testMatcherEvaluationErrorIsWrapped(): void
    {
        $waiter = $this->waiter(
            [new PaginationResult('done')],
            [
                new Acceptor(
                    Acceptor::SUCCESS,
                    Matcher::output(
                        '[',
                        'done',
                        PathComparator::StringEquals
                    )
                )
            ]
        );

        try {
            $waiter->wait(new PaginationResult('request'), 10.0);
            $this->fail('Expected waiter failure.');
        } catch (WaiterFailureException $failure) {
            $this->assertStringContainsString(
                'could not evaluate an acceptor',
                $failure->getMessage()
            );
            $this->assertNotNull($failure->getPrevious());
        }
    }

    public function testRejectsInvalidMaximumWaitTime(): void
    {
        $waiter = $this->waiter(
            [new PaginationResult('done')],
            [
                new Acceptor(
                    Acceptor::SUCCESS,
                    Matcher::success(true)
                )
            ]
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('finite and greater than zero');
        $waiter->wait(new PaginationResult('request'), 0.0);
    }

    /**
     * @param list<SerializableStructInterface|\Throwable> $responses
     * @param list<Acceptor> $acceptors
     */
    private function waiter(
        array $responses,
        array $acceptors,
        int $minDelay = 2,
        int $maxDelay = 120
    ): Waiter
    {
        $poll = static function () use (&$responses): SerializableStructInterface {
            $response = \array_shift($responses);
            if ($response instanceof \Throwable) {
                throw $response;
            }

            if (!$response instanceof SerializableStructInterface) {
                throw new \LogicException('No polling response remains.');
            }

            return $response;
        };

        return new Waiter($poll, $acceptors, $minDelay, $maxDelay);
    }
}
