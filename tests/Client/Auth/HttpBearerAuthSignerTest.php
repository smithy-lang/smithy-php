<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Auth\{
    AnonymousIdentity,
    BearerToken,
    HttpBearerAuthSigner
};
use Smithy\Http\Message\{
    HttpTransportRequest,
    Request,
    Uri
};

#[CoversClass(HttpBearerAuthSigner::class)]
final class HttpBearerAuthSignerTest extends TestCase
{
    public function testSignsHttpsRequestWithBearerToken(): void
    {
        $request = new Request('GET', new Uri('https://example.test'));

        $result = HttpBearerAuthSigner::instance()->sign(
            new HttpTransportRequest($request),
            new BearerToken('mF_9.B5f-4.1JqM')
        );
        $signed = $result->signedRequest;

        self::assertSame(
            'Bearer mF_9.B5f-4.1JqM',
            $signed->getHeaderLine('Authorization')
        );
        self::assertSame('', $result->signature);
    }

    public function testReplacesExistingAuthorizationHeader(): void
    {
        $request = new Request(
            'GET',
            new Uri('https://example.test'),
            ['Authorization' => 'Bearer old']
        );

        $signed = HttpBearerAuthSigner::instance()
            ->sign(
                new HttpTransportRequest($request),
                new BearerToken('new')
            )
            ->signedRequest;

        self::assertSame(
            'Bearer new',
            $signed->getHeaderLine('Authorization')
        );
    }

    public function testRejectsNonHttpsEndpoint(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('HTTPS');

        HttpBearerAuthSigner::instance()->sign(
            new HttpTransportRequest(
                new Request('GET', new Uri('http://example.test'))
            ),
            new BearerToken('token')
        );
    }

    public function testRejectsOtherIdentityTypes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(BearerToken::class);

        HttpBearerAuthSigner::instance()->sign(
            new HttpTransportRequest(
                new Request('GET', new Uri('https://example.test'))
            ),
            AnonymousIdentity::instance()
        );
    }

    public function testCloseIsIdempotent(): void
    {
        $signer = HttpBearerAuthSigner::instance();

        $signer->close();
        $signer->close();

        self::assertSame($signer, HttpBearerAuthSigner::instance());
    }
}
