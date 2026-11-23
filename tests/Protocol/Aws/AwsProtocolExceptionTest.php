<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Protocol\Aws;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Exception\ErrorFault;
use Smithy\Core\Retry\{RetryInfoInterface, RetrySafety};
use Smithy\Protocol\Aws\AwsProtocolException;

/**
 * `AwsErrorRetryTrait`, used by `AwsProtocolException`, refines the
 * fault-based retry classification with the HTTP status code: transient 5xx
 * statuses and 429 are retryable, and 429 is a throttle. A modeled override and
 * a captured `x-amz-retry-after` hint still win.
 */
final class AwsProtocolExceptionTest extends TestCase
{
    public function testImplementsRetryInfoInterface(): void
    {
        $this->assertInstanceOf(
            RetryInfoInterface::class,
            new AwsProtocolException()
        );
    }

    /**
     * @return iterable<string, array{int, RetrySafety, bool}>
     */
    public static function statusCases(): iterable
    {
        yield '500 server' => [500, RetrySafety::Yes, false];
        yield '502 bad gateway' => [502, RetrySafety::Yes, false];
        yield '503 unavailable' => [503, RetrySafety::Yes, false];
        yield '504 timeout' => [504, RetrySafety::Yes, false];
        yield '429 throttle' => [429, RetrySafety::Yes, true];
        yield '400 bad request' => [400, RetrySafety::No, false];
        yield '403 forbidden' => [403, RetrySafety::No, false];
        yield '404 not found' => [404, RetrySafety::No, false];
    }

    #[DataProvider('statusCases')]
    public function testClassificationFromHttpStatus(
        int $status,
        RetrySafety $expectedSafety,
        bool $expectedThrottle
    ): void
    {
        $fault = $status >= 500 ? ErrorFault::Server : ErrorFault::Client;
        $error = new AwsProtocolException(
            fault: $fault,
            httpStatusCode: $status
        );

        $this->assertSame($expectedSafety, $error->getRetrySafety());
        $this->assertSame($expectedThrottle, $error->isThrottle());
    }

    public function testFallsBackToFaultWhenNoStatus(): void
    {
        $this->assertSame(
            RetrySafety::Yes,
            (new AwsProtocolException(fault: ErrorFault::Server))
                ->getRetrySafety()
        );
        $this->assertSame(
            RetrySafety::No,
            (new AwsProtocolException(fault: ErrorFault::Client))
                ->getRetrySafety()
        );
    }

    public function testSetRetryAfterIsReturned(): void
    {
        $error = new AwsProtocolException(
            fault: ErrorFault::Server,
            httpStatusCode: 503
        );
        $this->assertNull($error->getRetryAfter());

        $error->setRetryAfter(2.5);
        $this->assertSame(2.5, $error->getRetryAfter());
    }

    public function testMetadataSetterUpdatesGetters(): void
    {
        $error = new AwsProtocolException();
        $error->setAwsErrorMetadata('Gone', 'NotFound', 'req-1', 404);
        $error->setExtendedRequestId('host-1');

        $this->assertSame('Gone', $error->getMessage());
        $this->assertSame('NotFound', $error->getAwsErrorCode());
        $this->assertSame('req-1', $error->getAwsRequestId());
        $this->assertSame(404, $error->getHttpStatusCode());
        $this->assertSame('host-1', $error->getExtendedRequestId());
    }
}
