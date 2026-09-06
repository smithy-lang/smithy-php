<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Auth\{
    AnonymousIdentity,
    NullSigner
};
use Smithy\Http\Message\Request;

#[CoversClass(NullSigner::class)]
final class NullSignerTest extends TestCase
{
    public function testReturnsRequestUnchanged(): void
    {
        $request = new Request('GET', 'https://example.test');
        $signer = NullSigner::instance();

        self::assertSame($signer, NullSigner::instance());
        self::assertSame(
            $request,
            $signer->sign($request, AnonymousIdentity::instance())
        );
    }
}
