<?php

declare(strict_types=1);

namespace Smithy\Tests\Http\Client\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Http\Client\Exception\{
    ExceptionRequestSanitizer,
    HttpException,
    RequestException
};
use Smithy\Http\Message\Request;

#[CoversClass(ExceptionRequestSanitizer::class)]
#[CoversClass(HttpException::class)]
#[CoversClass(RequestException::class)]
final class ExceptionRequestSanitizerTest extends TestCase
{
    public function testSanitizesRequestsStoredByExceptions(): void
    {
        $request = new Request(
            'GET',
            'https://example.com',
            [
                'Authorization' => 'Bearer secret',
                'Proxy-Authorization' => 'Basic secret',
                'X-Request-Id' => 'request-id',
            ]
        );

        foreach ([
            new HttpException('HTTP failure.', $request),
            new RequestException('Request failure.', $request),
        ] as $error) {
            $sanitized = $error->getRequest();

            self::assertNotSame($request, $sanitized);
            self::assertFalse($sanitized->hasHeader('Authorization'));
            self::assertFalse(
                $sanitized->hasHeader('Proxy-Authorization')
            );
            self::assertSame(
                'request-id',
                $sanitized->getHeaderLine('X-Request-Id')
            );
        }

        self::assertSame(
            'Bearer secret',
            $request->getHeaderLine('Authorization')
        );
        self::assertSame(
            'Basic secret',
            $request->getHeaderLine('Proxy-Authorization')
        );
    }

    public function testReturnsOriginalRequestWhenNoHeadersAreRemoved(): void
    {
        $request = new Request('GET', 'https://example.com');

        self::assertSame(
            $request,
            ExceptionRequestSanitizer::sanitize($request)
        );
    }
}
