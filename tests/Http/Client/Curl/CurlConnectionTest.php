<?php

declare(strict_types=1);

namespace Smithy\Tests\Http\Client\Curl;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Http\Client\Connection\ConnectionHealthStatus;
use Smithy\Http\Client\Connection\RequestContext;
use Smithy\Http\Client\Curl\CurlConnection;

#[CoversClass(CurlConnection::class)]
final class CurlConnectionTest extends TestCase
{
    protected function setUp(): void
    {
        if (!\extension_loaded('curl')) {
            self::markTestSkipped('The cURL extension is required.');
        }
    }

    public function testStartsWithUnknownHealth(): void
    {
        $connection = new CurlConnection(
            \curl_init(),
            new RequestContext('https', 'example.com', 443)
        );

        $this->assertSame(
            ConnectionHealthStatus::UNKNOWN,
            $connection->healthStatus()
        );

        $connection->markUsed();

        $this->assertSame(
            ConnectionHealthStatus::HEALTHY,
            $connection->healthStatus()
        );
    }

    public function testCreationTimestampIsImmutable(): void
    {
        $connection = new CurlConnection(
            \curl_init(),
            new RequestContext('https', 'example.com', 443)
        );
        $createdAt = $connection->createdAt();

        $connection->markUsed();

        $this->assertSame($createdAt, $connection->createdAt());

        $connection = new CurlConnection(
            \curl_init(),
            new RequestContext('https', 'example.com', 443),
            createdAt: 123.0
        );

        $connection->markUsed();

        $this->assertSame(123.0, $connection->createdAt());
    }
}
