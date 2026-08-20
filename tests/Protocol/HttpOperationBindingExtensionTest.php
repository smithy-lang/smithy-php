<?php

declare(strict_types=1);

namespace Smithy\Tests\Protocol;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Protocol\HttpOperationBindingExtension;

#[CoversClass(HttpOperationBindingExtension::class)]
final class HttpOperationBindingExtensionTest extends TestCase
{
    public function testStoresConstructorValues(): void
    {
        $extension = new HttpOperationBindingExtension(
            method: 'GET',
            path: '/items/{id}',
            pathSegments: [['/items/', null, false], [null, 0, false]],
            pathQueryString: 'fixed=yes',
            httpResponseCode: 204
        );

        self::assertSame('GET', $extension->method);
        self::assertSame('/items/{id}', $extension->path);
        self::assertSame(
            [['/items/', null, false], [null, 0, false]],
            $extension->pathSegments
        );
        self::assertSame('fixed=yes', $extension->pathQueryString);
        self::assertSame(204, $extension->httpResponseCode);
    }
}
