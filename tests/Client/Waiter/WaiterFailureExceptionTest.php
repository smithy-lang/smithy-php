<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Waiter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Waiter\WaiterFailureException;

#[CoversClass(WaiterFailureException::class)]
final class WaiterFailureExceptionTest extends TestCase
{
    public function testPreservesDiagnosticsAndCause(): void
    {
        $cause = new \RuntimeException('poll failed');
        $failure = new WaiterFailureException(
            message: 'Waiter failed.',
            attempts: 3,
            elapsedTime: 1.25,
            previous: $cause
        );

        self::assertSame('Waiter failed.', $failure->getMessage());
        self::assertSame(3, $failure->attempts);
        self::assertSame(1.25, $failure->elapsedTime);
        self::assertSame($cause, $failure->getPrevious());
    }
}
