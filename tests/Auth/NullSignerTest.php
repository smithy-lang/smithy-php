<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Auth\{
    AnonymousIdentity,
    NullSigner
};
use Smithy\Http\Message\{
    HttpTransportRequest,
    Request
};

#[CoversClass(NullSigner::class)]
final class NullSignerTest extends TestCase
{
    public function testReturnsRequestUnchanged(): void
    {
        $request = new Request('GET', 'https://example.test');
        $signer = NullSigner::instance();
        $result = $signer->sign(
            new HttpTransportRequest($request),
            AnonymousIdentity::instance()
        );

        self::assertSame($signer, NullSigner::instance());
        self::assertSame(
            $request,
            HttpTransportRequest::expect($result->signedRequest)->request
        );
        self::assertSame('', $result->signature);
    }
}
