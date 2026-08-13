<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Exception;

use PHPUnit\Framework\TestCase;
use Smithy\Core\Exception\ApiException;
use Smithy\Core\Exception\ErrorFault;

/**
 * `ApiException` is the base thrown for every API call error and the
 * default parent for generated modeled errors when no service-specific
 * base is configured. Two things are load-bearing about its shape:
 *
 *   1. It extends `\RuntimeException`, so any `catch (\RuntimeException)`
 *      block anywhere in an app catches it — and, importantly, it can
 *      be thrown from strict-typed function bodies without extra hoops.
 *   2. The `$fault` field is the client/server dispatch axis retry
 *      strategies and error reporters read to classify. Renaming or
 *      re-typing that field breaks every retry policy.
 *
 * The tests pin construction, the RuntimeException parent, and the
 * two fault predicates against all three ErrorFault cases.
 */
final class ApiExceptionTest extends TestCase
{
    public function testExtendsRuntimeException(): void
    {
        // Any downstream `catch (\RuntimeException)` block must catch
        // API errors — this is what lets service errors flow through
        // application error boundaries without special-casing.
        $this->assertInstanceOf(\RuntimeException::class, new ApiException());
    }

    public function testDefaultsToOtherFaultWithEmptyMessage(): void
    {
        // Zero-arg construction is legal — it's the fallback used
        // when the framework has classified nothing about the error.
        $exception = new ApiException();

        $this->assertSame('', $exception->getMessage());
        $this->assertSame(ErrorFault::Other, $exception->fault);
        $this->assertNull($exception->getPrevious());
        // The RuntimeException `code` slot is hard-wired to 0 —
        // Smithy carries error identity through modeled subclasses,
        // not through the numeric code.
        $this->assertSame(0, $exception->getCode());
    }

    public function testStoresMessageAndFaultAndPreviousChain(): void
    {
        $previous = new \RuntimeException('root cause');
        $exception = new ApiException(
            message: 'request failed',
            fault: ErrorFault::Server,
            previous: $previous,
        );

        $this->assertSame('request failed', $exception->getMessage());
        $this->assertSame(ErrorFault::Server, $exception->fault);
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testClientFaultPredicatesReflectClientCase(): void
    {
        $exception = new ApiException(fault: ErrorFault::Client);

        $this->assertTrue($exception->isClientFault());
        $this->assertFalse($exception->isServerFault());
    }

    public function testServerFaultPredicatesReflectServerCase(): void
    {
        $exception = new ApiException(fault: ErrorFault::Server);

        $this->assertFalse($exception->isClientFault());
        $this->assertTrue($exception->isServerFault());
    }

    public function testOtherFaultIsNeitherClientNorServer(): void
    {
        // Retry strategies need this three-way split — "other" means
        // "the framework hasn't classified this, don't apply your
        // client-error-skip / server-error-retry heuristic."
        $exception = new ApiException(fault: ErrorFault::Other);

        $this->assertFalse($exception->isClientFault());
        $this->assertFalse($exception->isServerFault());
    }

    public function testFaultFieldIsReadonly(): void
    {
        // The class declares `public readonly ErrorFault $fault`.
        // Mutating it must fail — modeled errors are never re-classified
        // after construction; a retry loop reading `$fault` mid-flight
        // relies on this being immutable.
        $exception = new ApiException(fault: ErrorFault::Client);

        $this->expectException(\Error::class);
        // @phpstan-ignore-next-line — deliberate readonly write attempt
        $exception->fault = ErrorFault::Server;
    }

    public function testApiExceptionIsSubclassable(): void
    {
        // SDK-specific bases (AwsException, etc.) extend ApiException.
        // The class must not be `final` — pin that by actually
        // subclassing it here.
        $subclass = new class ('boom', ErrorFault::Client) extends ApiException {};

        $this->assertInstanceOf(ApiException::class, $subclass);
        $this->assertSame('boom', $subclass->getMessage());
        $this->assertTrue($subclass->isClientFault());
    }
}
