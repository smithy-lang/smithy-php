<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Protocol\Utils;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Endpoint\Endpoint;
use Smithy\Http\Message\{
    HttpTransportRequest,
    Request
};
use Smithy\Protocol\Utils\{
    ComponentUri,
    ServiceEndpoint
};

#[CoversClass(ServiceEndpoint::class)]
final class ServiceEndpointTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function endpointProvider(): iterable
    {
        yield 'root' => [
            'https://example.com',
            'https://example.com/bucket/key%20name?x-id=Put&a=b'
        ];
        yield 'root slash' => [
            'https://example.com/',
            'https://example.com/bucket/key%20name?x-id=Put&a=b'
        ];
        yield 'base path' => [
            'https://example.com/base/',
            'https://example.com/base/bucket/key%20name?x-id=Put&a=b'
        ];
        yield 'port, query and fragment' => [
            'HTTP://Example.com:8080/base?drop=1#frag',
            'http://example.com:8080/base/bucket/key%20name?x-id=Put&a=b'
        ];
        yield 'default port' => [
            'https://example.com:443',
            'https://example.com/bucket/key%20name?x-id=Put&a=b'
        ];
    }

    #[DataProvider('endpointProvider')]
    public function testJoinsEndpointWithRequestPathAndQuery(
        string $url,
        string $expected
    ): void
    {
        $request = self::request();
        $endpoint = new Endpoint($url);

        $first = ServiceEndpoint::apply($request, $endpoint);
        $second = ServiceEndpoint::apply($request, $endpoint);

        self::assertSame($expected, (string) $first->request->getUri());
        self::assertSame($expected, (string) $second->request->getUri());
        self::assertSame(
            $first->request->getHeaderLine('Host'),
            $second->request->getHeaderLine('Host')
        );
    }

    public function testAppliesEndpointHeaders(): void
    {
        $result = ServiceEndpoint::apply(
            self::request(),
            new Endpoint('https://example.com', headers: ['X-Test' => 'yes'])
        );

        self::assertSame('yes', $result->request->getHeaderLine('X-Test'));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function emptyRequestPathProvider(): iterable
    {
        yield 'root endpoint' => [
            'https://bucket.example.com',
            'https://bucket.example.com/?acl'
        ];
        yield 'root slash endpoint' => [
            'https://bucket.example.com/',
            'https://bucket.example.com/?acl'
        ];
        yield 'base path endpoint' => [
            'https://example.com/bucket',
            'https://example.com/bucket?acl'
        ];
    }

    #[DataProvider('emptyRequestPathProvider')]
    public function testEmptyRequestPathKeepsEndpointPathOrRoot(
        string $url,
        string $expected
    ): void
    {
        $request = new HttpTransportRequest(Request::fromNormalized(
            'GET',
            new ComponentUri(path: '', query: 'acl'),
            [],
            ''
        ));

        $result = ServiceEndpoint::apply($request, new Endpoint($url));

        self::assertSame($expected, (string) $result->request->getUri());
    }

    public function testRejectsEndpointUserInfo(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ServiceEndpoint::apply(
            self::request(),
            new Endpoint('https://user@example.com')
        );
    }

    private static function request(): HttpTransportRequest
    {
        return new HttpTransportRequest(Request::fromNormalized(
            'PUT',
            new ComponentUri(path: '/bucket/key%20name', query: 'x-id=Put&a=b'),
            [],
            ''
        ));
    }
}
