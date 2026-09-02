<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Waiter;

use PHPUnit\Framework\TestCase;
use Smithy\Client\Waiter\{
    AttemptContext,
    Matcher,
    PathComparator
};
use Smithy\Tests\Client\Pagination\Fixture\PaginationResult;
use Smithy\Tests\Client\Waiter\Fixture\WaiterModeledError;
final class MatcherTest extends TestCase
{
    public function testFactoryCreatesEachMatcherType(): void
    {
        $this->assertInstanceOf(
            Matcher::class,
            Matcher::success(true)
        );
        $this->assertInstanceOf(
            Matcher::class,
            Matcher::errorType('NotFound')
        );
        $this->assertInstanceOf(
            Matcher::class,
            Matcher::output(
                'items',
                'ready',
                PathComparator::AllStringEquals
            )
        );
        $this->assertInstanceOf(
            Matcher::class,
            Matcher::inputOutput(
                'input.nextToken',
                'request',
                PathComparator::StringEquals
            )
        );
    }

    public function testSuccessMatcherMatchesExpectedAttemptStatus(): void
    {
        $successful = $this->successfulAttempt();
        $failed = $this->failedAttempt();

        $this->assertTrue(Matcher::success(true)->matches($successful));
        $this->assertFalse(Matcher::success(false)->matches($successful));
        $this->assertTrue(Matcher::success(false)->matches($failed));
        $this->assertFalse(Matcher::success(true)->matches($failed));
    }

    public function testErrorTypeMatchesShapeNameOrFullShapeId(): void
    {
        $attempt = $this->failedAttempt();

        $this->assertTrue(
            Matcher::errorType('WaiterModeledError')->matches($attempt)
        );
        $this->assertTrue(
            Matcher::errorType(
                'smithy.tests#WaiterModeledError'
            )->matches($attempt)
        );
        $this->assertFalse(
            Matcher::errorType(
                'other.namespace#WaiterModeledError'
            )->matches($attempt)
        );
        $this->assertFalse(
            Matcher::errorType('OtherException')->matches($attempt)
        );
        $this->assertFalse(
            Matcher::errorType('WaiterModeledError')->matches(
                $this->successfulAttempt()
            )
        );
    }

    public function testOutputMatcherEvaluatesJmesPathAgainstOutput(): void
    {
        $attempt = $this->successfulAttempt();

        $this->assertTrue(
            Matcher::output(
                'nextToken',
                'done',
                PathComparator::StringEquals
            )->matches($attempt)
        );
        $this->assertTrue(
            Matcher::output(
                'items',
                'ready',
                PathComparator::AllStringEquals
            )->matches($attempt)
        );
        $this->assertFalse(
            Matcher::output(
                'items',
                'pending',
                PathComparator::AnyStringEquals
            )->matches($attempt)
        );
    }

    public function testOutputMatcherDoesNotMatchModeledError(): void
    {
        $matcher = Matcher::output(
            'nextToken',
            'done',
            PathComparator::StringEquals
        );

        $this->assertFalse($matcher->matches($this->failedAttempt()));
    }

    public function testInputOutputMatcherUsesNamedRootDocuments(): void
    {
        $attempt = $this->successfulAttempt();

        $this->assertTrue(
            Matcher::inputOutput(
                'input.nextToken',
                'request',
                PathComparator::StringEquals
            )->matches($attempt)
        );
        $this->assertTrue(
            Matcher::inputOutput(
                'output.nextToken',
                'done',
                PathComparator::StringEquals
            )->matches($attempt)
        );
    }

    public function testInputOutputMatcherDoesNotMatchModeledError(): void
    {
        $matcher = Matcher::inputOutput(
            'input.nextToken',
            'request',
            PathComparator::StringEquals
        );

        $this->assertFalse($matcher->matches($this->failedAttempt()));
    }

    private function successfulAttempt(): AttemptContext
    {
        return new AttemptContext(
            input: new PaginationResult('request'),
            output: new PaginationResult('done', ['ready', 'ready'])
        );
    }

    private function failedAttempt(): AttemptContext
    {
        return new AttemptContext(
            input: new PaginationResult('request'),
            error: new WaiterModeledError()
        );
    }
}
