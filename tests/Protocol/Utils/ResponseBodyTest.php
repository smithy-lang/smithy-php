<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Protocol\Utils;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Http\Message\{
    Response,
    StringRequestStream
};
use Smithy\Protocol\Utils\ResponseBody;

#[CoversClass(ResponseBody::class)]
final class ResponseBodyTest extends TestCase
{
    public function testContentLengthDecides(): void
    {
        $body = new StringRequestStream('{}');

        self::assertFalse(ResponseBody::hasContent(
            new Response(200, ['Content-Length' => '0'], $body)
        ));
        self::assertTrue(ResponseBody::hasContent(
            new Response(200, ['Content-Length' => '2'], '')
        ));
    }

    public function testFallsBackToStreamSize(): void
    {
        self::assertFalse(ResponseBody::hasContent(new Response(200, [], '')));
        self::assertTrue(ResponseBody::hasContent(new Response(200, [], '{}')));
    }
}
