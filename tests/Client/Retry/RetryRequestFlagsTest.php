<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Retry;

use PHPUnit\Framework\Attributes\{
    CoversClass,
    DataProvider
};
use PHPUnit\Framework\TestCase;
use Smithy\Client\Retry\RetryRequestFlags;

#[CoversClass(RetryRequestFlags::class)]
final class RetryRequestFlagsTest extends TestCase
{
    #[DataProvider('longPollingCases')]
    public function testDetectsLongPollingFlag(
        int $flags,
        bool $expected
    ): void
    {
        self::assertSame(
            $expected,
            RetryRequestFlags::isLongPolling($flags)
        );
    }

    /**
     * @return array<string, array{int, bool}>
     */
    public static function longPollingCases(): array
    {
        return [
            'no flags' => [0, false],
            'unrelated flag' => [2, false],
            'long polling' => [RetryRequestFlags::LONG_POLLING, true],
            'combined flags' => [
                RetryRequestFlags::LONG_POLLING | 2,
                true
            ]
        ];
    }
}
