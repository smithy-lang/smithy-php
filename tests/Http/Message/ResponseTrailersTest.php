<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Http\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Http\Message\{
    Response,
    ResponseInterface,
    ResponseTrailers
};

#[CoversClass(ResponseTrailers::class)]
final class ResponseTrailersTest extends TestCase
{
    public function testSyntheticResponseHasCompletedEmptyTrailers(): void
    {
        $response = new Response();

        $this->assertInstanceOf(
            ResponseInterface::class,
            $response
        );
        $this->assertTrue($response->getTrailers()->isComplete());
        $this->assertSame([], $response->getTrailers()->getHeaders());
    }

    public function testReadsCompletedTrailersCaseInsensitively(): void
    {
        $trailers = ResponseTrailers::completed([
            'X-Checksum' => ['first', 'second']
        ]);

        $this->assertTrue($trailers->hasHeader('x-CHECKSUM'));
        $this->assertSame(
            ['first', 'second'],
            $trailers->getHeader('X-Checksum')
        );
        $this->assertSame(
            'first, second',
            $trailers->getHeaderLine('x-checksum')
        );
        $this->assertSame(
            ['X-Checksum' => ['first', 'second']],
            $trailers->getHeaders()
        );
    }

    public function testPendingTrailersRejectHeaderAccess(): void
    {
        $trailers = ResponseTrailers::pending();

        $this->assertFalse($trailers->isComplete());
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'unavailable until the response body completes'
        );

        $trailers->getHeaders();
    }

    public function testFailedTrailersRethrowTransportFailure(): void
    {
        $trailers = ResponseTrailers::pending();
        $failure = new \RuntimeException('transfer failed');
        $trailers->fail($failure);

        $this->assertTrue($trailers->isComplete());
        $this->expectExceptionObject($failure);

        $trailers->getHeaders();
    }
}
