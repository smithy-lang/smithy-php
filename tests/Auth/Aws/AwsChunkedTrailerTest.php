<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Auth\Aws;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Smithy\Auth\Aws\AwsChunkedRequestStream;
use Smithy\Auth\Aws\AwsChunkedTrailer;
use Smithy\Auth\Aws\AwsCredentials;
use Smithy\Auth\Aws\SigV4Signer;
use Smithy\Core\Checksum\ChecksumAlgorithm;
use Smithy\Http\Message\HttpTransportRequest;
use Smithy\Http\Message\Request;
use Smithy\Tests\Auth\Aws\Utils\NonSeekableStreamFixture;

/**
 * Header and SigV4 tests for the `STREAMING-UNSIGNED-PAYLOAD-TRAILER` path.
 */
final class AwsChunkedTrailerTest extends TestCase
{
    private const TIMESTAMP = 1440938160;

    public function testSetsStreamingHeadersForKnownLength(): void
    {
        $request = AwsChunkedTrailer::applyUnsigned(
            $this->putRequest('Hello world'),
            ChecksumAlgorithm::resolveRequest('CRC32'),
        );
        $encoded = "b\r\nHello world\r\n0\r\nx-amz-checksum-crc32:i9aeUg==\r\n\r\n";

        $this->assertInstanceOf(
            AwsChunkedRequestStream::class,
            $request->getBody()
        );
        $this->assertSame('aws-chunked', $request->getHeaderLine('Content-Encoding'));
        $this->assertSame('x-amz-checksum-crc32', $request->getHeaderLine('x-amz-trailer'));
        $this->assertSame(
            'STREAMING-UNSIGNED-PAYLOAD-TRAILER',
            $request->getHeaderLine('x-amz-content-sha256')
        );
        $this->assertSame('11', $request->getHeaderLine('x-amz-decoded-content-length'));
        $this->assertSame(
            (string) \strlen($encoded),
            $request->getHeaderLine('Content-Length')
        );
        $this->assertFalse($request->hasHeader('Transfer-Encoding'));
        $this->assertSame($encoded, $request->getBody()->getContents());
    }

    public function testUsesTransferEncodingChunkedForUnknownLength(): void
    {
        $request = AwsChunkedTrailer::applyUnsigned(
            $this->putRequest(new NonSeekableStreamFixture('Hello world')),
            ChecksumAlgorithm::resolveRequest('CRC32'),
        );

        $this->assertSame('chunked', $request->getHeaderLine('Transfer-Encoding'));
        $this->assertFalse($request->hasHeader('Content-Length'));
        $this->assertFalse($request->hasHeader('x-amz-decoded-content-length'));
        $this->assertSame('aws-chunked', $request->getHeaderLine('Content-Encoding'));
        $this->assertSame(
            "b\r\nHello world\r\n0\r\nx-amz-checksum-crc32:i9aeUg==\r\n\r\n",
            $request->getBody()->getContents()
        );
    }

    public function testPlacesAwsChunkedBeforeExistingContentEncoding(): void
    {
        $request = AwsChunkedTrailer::applyUnsigned(
            $this->putRequest('Hello world')->withHeader('Content-Encoding', 'gzip'),
            ChecksumAlgorithm::resolveRequest('CRC32'),
        );

        $this->assertSame(
            'aws-chunked, gzip',
            $request->getHeaderLine('Content-Encoding')
        );
    }

    public function testRequiresHttps(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('HTTPS');

        AwsChunkedTrailer::applyUnsigned(
            new Request('PUT', 'http://bucket.s3.us-east-1.amazonaws.com/key', [], 'x'),
            ChecksumAlgorithm::resolveRequest('CRC32'),
        );
    }

    public function testSigV4SignsWithStreamingUnsignedTrailerContentHash(): void
    {
        $request = AwsChunkedTrailer::applyUnsigned(
            $this->putRequest('Hello world'),
            ChecksumAlgorithm::resolveRequest('CRC32'),
        );

        $signed = $this->sign($request, []);

        $this->assertSame(
            'STREAMING-UNSIGNED-PAYLOAD-TRAILER',
            $signed->getHeaderLine('x-amz-content-sha256')
        );
        $this->assertSame(
            $this->expectedAuthorization($signed),
            $signed->getHeaderLine('Authorization')
        );
        $this->assertStringContainsString(
            'SignedHeaders=content-encoding;host;x-amz-content-sha256;'
                . 'x-amz-date;x-amz-decoded-content-length;x-amz-trailer,',
            $signed->getHeaderLine('Authorization')
        );
        $this->assertFalse($signed->getBody()->started());
    }

    public function testSigningPropertySelectsStreamingContentHash(): void
    {
        $request = AwsChunkedTrailer::applyUnsigned(
            $this->putRequest('Hello world'),
            ChecksumAlgorithm::resolveRequest('CRC32'),
        )->withoutHeader('x-amz-content-sha256');

        $signed = $this->sign($request, [
            'streamingUnsignedTrailer' => true,
            'unsignedPayload' => true,
        ]);

        $this->assertSame(
            'STREAMING-UNSIGNED-PAYLOAD-TRAILER',
            $signed->getHeaderLine('x-amz-content-sha256')
        );
        $this->assertSame(
            $this->expectedAuthorization($signed),
            $signed->getHeaderLine('Authorization')
        );
    }

    private function putRequest(string|StreamInterface $body): RequestInterface
    {
        return new Request(
            'PUT',
            'https://bucket.s3.us-east-1.amazonaws.com/key',
            [],
            $body,
        );
    }

    /**
     * @param array<string, mixed> $properties
     */
    private function sign(
        RequestInterface $request,
        array $properties,
    ): RequestInterface
    {
        $result = (new SigV4Signer('s3', 'us-east-1'))->sign(
            new HttpTransportRequest($request),
            new AwsCredentials('AKID', 'SECRET'),
            ['timestamp' => self::TIMESTAMP] + $properties,
        );

        return HttpTransportRequest::expect($result->signedRequest)->request;
    }

    /**
     * Build the Authorization header independently from the SigV4 spec,
     * using the streaming sentinel as the payload hash.
     */
    private function expectedAuthorization(RequestInterface $signed): string
    {
        $headers = [];
        foreach ($signed->getHeaders() as $name => $values) {
            $name = \strtolower($name);
            if (\in_array($name, ['authorization', 'content-length'], true)) {
                continue;
            }
            $headers[$name] = $name . ':' . \implode(',', $values);
        }
        \ksort($headers);

        $signedHeaders = \implode(';', \array_keys($headers));
        $canonicalRequest = \implode("\n", [
            'PUT',
            '/key',
            '',
            \implode("\n", $headers) . "\n",
            $signedHeaders,
            'STREAMING-UNSIGNED-PAYLOAD-TRAILER',
        ]);
        $scope = '20150830/us-east-1/s3/aws4_request';
        $stringToSign = \implode("\n", [
            'AWS4-HMAC-SHA256',
            '20150830T123600Z',
            $scope,
            \hash('sha256', $canonicalRequest),
        ]);
        $key = \hash_hmac('sha256', '20150830', 'AWS4SECRET', true);
        $key = \hash_hmac('sha256', 'us-east-1', $key, true);
        $key = \hash_hmac('sha256', 's3', $key, true);
        $key = \hash_hmac('sha256', 'aws4_request', $key, true);

        return \sprintf(
            'AWS4-HMAC-SHA256 Credential=AKID/%s, SignedHeaders=%s, Signature=%s',
            $scope,
            $signedHeaders,
            \hash_hmac('sha256', $stringToSign, $key),
        );
    }
}
