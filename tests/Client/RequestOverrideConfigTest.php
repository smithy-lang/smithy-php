<?php

declare(strict_types=1);

namespace Smithy\Tests\Client;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\RequestOverrideConfig;
use Smithy\Http\Client\{
    HttpRequestOptions,
    RequestTrailers
};
use Smithy\Http\Client\Cancellation\Cancellation;

#[CoversClass(RequestOverrideConfig::class)]
final class RequestOverrideConfigTest extends TestCase
{
    public function testCreatesEmptyOverrides(): void
    {
        $overrides = RequestOverrideConfig::fromArray([]);

        self::assertSame([], $overrides->headers);
        self::assertNull($overrides->httpOptions);
        self::assertNull($overrides->maxAttempts);
        self::assertNull($overrides->authSchemePreference);
        self::assertNull(
            $overrides->resolveHttpOptions(new HttpRequestOptions())
        );
    }

    public function testAcceptsCompleteHttpRequestOptions(): void
    {
        $options = new HttpRequestOptions(timeout: 2.0);
        $overrides = RequestOverrideConfig::fromArray([
            RequestOverrideConfig::HTTP => $options
        ]);

        self::assertSame($options, $overrides->httpOptions);
        self::assertSame(
            $options,
            $overrides->resolveHttpOptions(new HttpRequestOptions())
        );
    }

    public function testResolvesPartialHttpOptions(): void
    {
        $trailers = RequestTrailers::fromArray(['x-checksum' => 'value']);
        $cancellation = new Cancellation();
        $defaults = new HttpRequestOptions(
            timeout: 9.0,
            followRedirects: false,
            proxy: 'http://proxy.example',
            decodeContent: true,
            trailers: $trailers,
            cancellation: $cancellation,
            responseBufferSize: 32768,
            maxBufferedResponseBytes: 2048,
            readTimeout: 8.0,
            maxResponseBodyBytes: 4096,
            uploadBufferSize: 65536,
            bufferedDownloadBufferSize: 32768
        );
        $overrides = RequestOverrideConfig::fromArray([
            RequestOverrideConfig::HTTP => [
                'timeout' => 2,
                'allow_redirects' => true,
                'proxy' => null,
                'decode_content' => false,
                'response_buffer_size' => 65536,
                'read_timeout' => 3.5
            ]
        ]);

        $resolved = $overrides->resolveHttpOptions($defaults);

        self::assertNotNull($resolved);
        self::assertSame(2.0, $resolved->timeout);
        self::assertTrue($resolved->followRedirects);
        self::assertNull($resolved->proxy);
        self::assertFalse($resolved->decodeContent);
        self::assertSame($trailers, $resolved->trailers);
        self::assertSame($cancellation, $resolved->cancellation);
        self::assertSame(65536, $resolved->responseBufferSize);
        self::assertSame(2048, $resolved->maxBufferedResponseBytes);
        self::assertSame(3.5, $resolved->readTimeout);
        self::assertSame(4096, $resolved->maxResponseBodyBytes);
        self::assertSame(65536, $resolved->uploadBufferSize);
        self::assertSame(
            32768,
            $resolved->bufferedDownloadBufferSize
        );
    }

    public function testExtractsHeadersRetriesAndAuthPreference(): void
    {
        $overrides = RequestOverrideConfig::fromArray([
            RequestOverrideConfig::HTTP => [
                'headers' => [
                    'x-one' => 'one',
                    'x-many' => ['two', 'three']
                ]
            ],
            RequestOverrideConfig::RETRIES => 2,
            RequestOverrideConfig::AUTH => [
                'sigv4',
                'httpBearerAuth',
                'sigv4'
            ]
        ]);

        self::assertSame(
            [
                'x-one' => 'one',
                'x-many' => ['two', 'three']
            ],
            $overrides->headers
        );
        self::assertSame(3, $overrides->maxAttempts);
        self::assertSame(
            ['sigv4', 'httpBearerAuth'],
            $overrides->authSchemePreference
        );
    }

    public function testRejectsUnknownOverride(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Unknown request override '@unknown'."
        );

        RequestOverrideConfig::fromArray(['@unknown' => true]);
    }

    public function testRejectsConflictingRedirectOptions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'cannot contain both allow_redirects and follow_redirects'
        );

        RequestOverrideConfig::fromArray([
            RequestOverrideConfig::HTTP => [
                'allow_redirects' => true,
                'follow_redirects' => false
            ]
        ]);
    }

    public function testRejectsInvalidHttpValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('@http.timeout must be numeric.');

        $overrides = RequestOverrideConfig::fromArray([
            RequestOverrideConfig::HTTP => ['timeout' => '2']
        ]);
        $overrides->resolveHttpOptions(new HttpRequestOptions());
    }

    public function testRejectsInvalidHeaders(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "@http.headers['x-test'] values must be strings."
        );

        RequestOverrideConfig::fromArray([
            RequestOverrideConfig::HTTP => [
                'headers' => ['x-test' => [1]]
            ]
        ]);
    }

    public function testRejectsAssociativeHeaderValues(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "@http.headers['x-test'] must be a list of strings."
        );

        RequestOverrideConfig::fromArray([
            RequestOverrideConfig::HTTP => [
                'headers' => ['x-test' => ['first' => 'value']]
            ]
        ]);
    }

    public function testRejectsInvalidRetries(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            '@retries must be a non-negative integer.'
        );

        RequestOverrideConfig::fromArray([
            RequestOverrideConfig::RETRIES => -1
        ]);
    }

    public function testRejectsInvalidAuthPreference(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            '@auth must be a non-empty list of auth scheme names.'
        );

        RequestOverrideConfig::fromArray([
            RequestOverrideConfig::AUTH => []
        ]);
    }

    public function testRejectsAssociativeAuthPreference(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            '@auth must be a non-empty list of auth scheme names.'
        );

        RequestOverrideConfig::fromArray([
            RequestOverrideConfig::AUTH => ['preferred' => 'sigv4']
        ]);
    }
}
