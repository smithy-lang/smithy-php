<?php

declare(strict_types=1);

namespace Smithy\Tests\Http\Client\Cancellation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Http\Client\Cancellation\{
    Cancellation,
    CancelledException
};

#[CoversClass(Cancellation::class)]
final class CancellationTest extends TestCase
{
    public function testCancellationNotifiesEachSubscriberOnce(): void
    {
        $source = new Cancellation();
        $notifications = [];

        $source->subscribe(
            static function (CancelledException $error) use (
                &$notifications
            ): void
{
                $notifications[] = $error;
            }
        );
        $removed = $source->subscribe(
            static function () use (&$notifications): void {
                $notifications[] = 'removed';
            }
        );
        $source->unsubscribe($removed);

        $source->cancel();
        $source->cancel();

        $this->assertTrue($source->isCancelled());
        $this->assertCount(1, $notifications);
        $this->assertSame($source->reason(), $notifications[0]);
    }

    public function testLateSubscriberReceivesCancellation(): void
    {
        $cause = new \RuntimeException('stop');
        $source = new Cancellation();
        $source->cancel($cause);
        $received = null;

        $subscription = $source->subscribe(
            static function (CancelledException $error) use (
                &$received
            ): void
{
                $received = $error;
            }
        );

        $this->assertSame(0, $subscription);
        $this->assertSame($source->reason(), $received);
        $this->assertSame($cause, $received?->getPrevious());
    }
}
