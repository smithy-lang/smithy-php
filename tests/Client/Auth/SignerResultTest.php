<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Auth\SignerResult;
use Smithy\Http\Message\Request;

#[CoversClass(SignerResult::class)]
final class SignerResultTest extends TestCase
{
    public function testStoresSignedRequestAndSignature(): void
    {
        $request = new Request('GET', 'https://example.test');
        $result = new SignerResult($request, 'signature');

        self::assertSame($request, $result->signedRequest);
        self::assertSame('signature', $result->signature);
    }

    public function testDefaultsToEmptySignature(): void
    {
        $result = new SignerResult(
            new Request('GET', 'https://example.test')
        );

        self::assertSame('', $result->signature);
    }
}
