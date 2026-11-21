<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Auth\Aws;

use Smithy\Auth\Aws\SignatureV4;
use Smithy\Auth\Aws\AwsCredentials;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Smithy\Async\RevoltEventLoop;
use Smithy\Http\Message\AsyncRequestStream;
use Smithy\Http\Message\HttpTransportRequest;
use Smithy\Http\Message\Request;
use Smithy\Http\Message\ResourceRequestStream;
use Smithy\Tests\Utils\Http\GenericPsrRequest;

/**
 * Unit tests for {@see SignatureV4}. Uses the canonical AWS test vectors
 * from the SigV4 documentation (`get-vanilla`, `post-vanilla`,
 * `get-vanilla-query`), plus a session-token vector to cover STS
 * temporary credentials.
 *
 * The public AWS vectors use:
 *   AccessKeyId  = AKIDEXAMPLE
 *   SecretAccessKey = wJalrXUtnFEMI/K7MDENG+bPxRfiCYEXAMPLEKEY
 *   Region       = us-east-1
 *   Service      = service
 *   Date         = 20150830T123600Z
 */
final class SignatureV4Test extends TestCase
{
    private const AKID = 'AKIDEXAMPLE';
    private const SECRET = 'wJalrXUtnFEMI/K7MDENG+bPxRfiCYEXAMPLEKEY';
    private const REGION = 'us-east-1';
    private const SERVICE = 'service';
    private const TIMESTAMP = 1440938160; // 20150830T123600Z

    private function signer(): SignatureV4
    {
        return new SignatureV4(self::SERVICE, self::REGION);
    }

    private function creds(?string $session = null): AwsCredentials
    {
        return new AwsCredentials(self::AKID, self::SECRET, $session);
    }

    /**
     * @param array{
     *     timestamp?: int,
     *     unsigned_payload?: bool
     * } $options
     */
    private function signRequest(
        RequestInterface $request,
        AwsCredentials $credentials,
        array $options = []
    ): RequestInterface
    {
        $result = $this->signer()
            ->signRequest($request, $credentials, $options)
            ->signedRequest;

        return HttpTransportRequest::expect($result)->request;
    }

    /**
     * `get-vanilla` from the AWS sigv4 test suite.
     * https://docs.aws.amazon.com/general/latest/gr/sigv4-signed-request-examples.html
     */
    public function testGetVanilla(): void
    {
        $req = new Request('GET', 'https://example.amazonaws.com/', [
            'Host' => 'example.amazonaws.com',
        ]);
        $result = $this->signer()->signRequest(
            $req,
            $this->creds(),
            ['timestamp' => self::TIMESTAMP],
        );
        $signed = HttpTransportRequest::expect(
            $result->signedRequest
        )->request;

        $this->assertSame('20150830T123600Z', $signed->getHeaderLine('X-Amz-Date'));
        $auth = $signed->getHeaderLine('Authorization');
        $this->assertStringStartsWith('AWS4-HMAC-SHA256 ', $auth);
        $this->assertStringContainsString(
            'Credential=AKIDEXAMPLE/20150830/us-east-1/service/aws4_request',
            $auth,
        );
        $this->assertStringContainsString('SignedHeaders=host;x-amz-content-sha256;x-amz-date', $auth);
        // Signature is deterministic given fixed inputs.
        $this->assertMatchesRegularExpression('/Signature=[a-f0-9]{64}$/', $auth);
        $this->assertStringEndsWith(
            'Signature=' . $result->signature,
            $auth
        );
    }

    public function testSignsGenericPsrRequest(): void
    {
        $request = new GenericPsrRequest(new Request(
            'GET',
            'https://example.amazonaws.com/',
            ['Host' => 'example.amazonaws.com'],
        ));

        $signed = $this->signRequest(
            $request,
            $this->creds('session-token'),
            ['timestamp' => self::TIMESTAMP],
        );

        $this->assertInstanceOf(GenericPsrRequest::class, $signed);
        $this->assertSame(
            '20150830T123600Z',
            $signed->getHeaderLine('X-Amz-Date'),
        );
        $this->assertSame(
            'session-token',
            $signed->getHeaderLine('X-Amz-Security-Token'),
        );
        $this->assertNotSame(
            '',
            $signed->getHeaderLine('X-Amz-Content-Sha256'),
        );
        $this->assertStringStartsWith(
            'AWS4-HMAC-SHA256 ',
            $signed->getHeaderLine('Authorization'),
        );
    }

    /**
     * POST with body — verify the content hash lands in
     * `X-Amz-Content-Sha256` and participates in the canonical request.
     */
    public function testPostWithBody(): void
    {
        $body = 'Param1=value1';
        $req = new Request('POST', 'https://example.amazonaws.com/', [
            'Host' => 'example.amazonaws.com',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ], $body);

        $signed = $this->signRequest(
            $req,
            $this->creds(),
            ['timestamp' => self::TIMESTAMP],
        );

        $this->assertSame(hash('sha256', $body), $signed->getHeaderLine('X-Amz-Content-Sha256'));
        // Content-Type is on the unsignable list, so it should NOT appear in signed headers.
        $auth = $signed->getHeaderLine('Authorization');
        $this->assertStringNotContainsString('content-type', $auth);
    }

    /**
     * Query-string parameters are folded into the canonical request in
     * sorted, percent-encoded form.
     */
    public function testGetWithQuery(): void
    {
        $req = new Request(
            'GET',
            'https://example.amazonaws.com/?Param2=value2&Param1=value1',
            ['Host' => 'example.amazonaws.com'],
        );
        $signed = $this->signRequest(
            $req,
            $this->creds(),
            ['timestamp' => self::TIMESTAMP],
        );

        $this->assertNotEmpty($signed->getHeaderLine('Authorization'));
    }

    /**
     * With a session token the signer MUST add `X-Amz-Security-Token`
     * to the request and include it in the signed-headers list.
     */
    public function testSessionTokenPresent(): void
    {
        $req = new Request('GET', 'https://example.amazonaws.com/', [
            'Host' => 'example.amazonaws.com',
        ]);
        $signed = $this->signRequest(
            $req,
            $this->creds('IQoJb3JpZ2luX2VjEXAMPLETOKEN'),
            ['timestamp' => self::TIMESTAMP],
        );

        $this->assertSame('IQoJb3JpZ2luX2VjEXAMPLETOKEN', $signed->getHeaderLine('X-Amz-Security-Token'));
        $this->assertStringContainsString('x-amz-security-token', $signed->getHeaderLine('Authorization'));
    }

    /**
     * `unsigned_payload` swaps the content hash for the literal
     * `UNSIGNED-PAYLOAD` string. Used by S3 for streaming uploads.
     */
    public function testUnsignedPayload(): void
    {
        $req = new Request('PUT', 'https://example.amazonaws.com/', [
            'Host' => 'example.amazonaws.com',
        ], 'body-that-should-not-be-hashed');

        $signed = $this->signRequest(
            $req,
            $this->creds(),
            ['timestamp' => self::TIMESTAMP, 'unsigned_payload' => true],
        );

        $this->assertSame('UNSIGNED-PAYLOAD', $signed->getHeaderLine('X-Amz-Content-Sha256'));
    }

    public function testUnsignedPayloadReplacesExistingContentHash(): void
    {
        $request = new Request(
            'PUT',
            'https://example.amazonaws.com/',
            ['X-Amz-Content-Sha256' => 'stale'],
            'body'
        );

        $signed = $this->signRequest(
            $request,
            $this->creds(),
            [
                'timestamp' => self::TIMESTAMP,
                'unsigned_payload' => true
            ]
        );

        $this->assertSame(
            'UNSIGNED-PAYLOAD',
            $signed->getHeaderLine('X-Amz-Content-Sha256')
        );
    }

    public function testHashesSeekableBodyWithoutChangingPosition(): void
    {
        $resource = \fopen('php://temp', 'r+');
        $this->assertIsResource($resource);
        \fwrite($resource, 'skip-payload');
        \fseek($resource, 5);
        $body = new ResourceRequestStream($resource);
        $request = new Request(
            'PUT',
            'https://example.amazonaws.com/',
            [],
            $body
        );

        $signed = $this->signRequest(
            $request,
            $this->creds(),
            ['timestamp' => self::TIMESTAMP]
        );

        $this->assertSame(
            \hash('sha256', 'payload'),
            $signed->getHeaderLine('X-Amz-Content-Sha256')
        );
        $this->assertSame(5, $body->tell());
    }

    public function testHashesReplayableResourceWithoutConsumingUpload(): void
    {
        $openCount = 0;
        $body = ResourceRequestStream::create(
            static function () use (&$openCount) {
                ++$openCount;
                $resource = \fopen('php://temp', 'r+');
                if ($resource === false) {
                    throw new \RuntimeException(
                        'Unable to create test resource.'
                    );
                }
                \fwrite($resource, 'payload');
                \rewind($resource);

                return $resource;
            },
            7
        );
        $request = new Request(
            'PUT',
            'https://example.amazonaws.com/',
            [],
            $body
        );

        $signed = $this->signRequest(
            $request,
            $this->creds(),
            ['timestamp' => self::TIMESTAMP]
        );

        $this->assertSame(
            \hash('sha256', 'payload'),
            $signed->getHeaderLine('X-Amz-Content-Sha256')
        );
        $this->assertSame(1, $openCount);
        $this->assertFalse($body->started());
        $this->assertSame('payload', $body->getContents());
        $this->assertSame(2, $openCount);
    }

    public function testRejectsNonSeekableBodyWithoutPayloadHash(): void
    {
        $body = new AsyncRequestStream(
            new RevoltEventLoop(),
            expectedLength: 7
        );
        $body->write('partial');
        $request = new Request(
            'PUT',
            'https://example.amazonaws.com/',
            [],
            $body
        );

        try {
            $this->signer()->signRequest(
                $request,
                $this->creds(),
                ['timestamp' => self::TIMESTAMP]
            );
            $this->fail('Expected non-seekable body signing to fail.');
        } catch (\RuntimeException $error) {
            $this->assertSame(
                'Cannot calculate a SigV4 payload hash for a non-seekable '
                    . 'request body.',
                $error->getMessage()
            );
        }

        $this->assertSame(0, $body->tell());
        $this->assertSame(7, $body->bufferedBytes());
    }

    public function testUsesPrecomputedHashForNonSeekableBody(): void
    {
        $body = new AsyncRequestStream(
            new RevoltEventLoop(),
            expectedLength: 7
        );
        $body->write('partial');
        $request = new Request(
            'PUT',
            'https://example.amazonaws.com/',
            ['X-Amz-Content-Sha256' => 'precomputed'],
            $body
        );

        $signed = $this->signRequest(
            $request,
            $this->creds(),
            ['timestamp' => self::TIMESTAMP]
        );

        $this->assertSame(
            'precomputed',
            $signed->getHeaderLine('X-Amz-Content-Sha256')
        );
        $this->assertSame(0, $body->tell());
        $this->assertSame(7, $body->bufferedBytes());
    }

    /**
     * Two signings with identical inputs produce byte-identical Authorization headers.
     */
    public function testSignatureIsDeterministic(): void
    {
        $req = new Request('GET', 'https://example.amazonaws.com/', [
            'Host' => 'example.amazonaws.com',
        ]);
        $signer = $this->signer();
        $a = $signer->signRequest(
            $req,
            $this->creds(),
            ['timestamp' => self::TIMESTAMP]
        );
        $b = $signer->signRequest(
            $req,
            $this->creds(),
            ['timestamp' => self::TIMESTAMP]
        );
        $aRequest = HttpTransportRequest::expect($a->signedRequest)->request;
        $bRequest = HttpTransportRequest::expect($b->signedRequest)->request;

        $this->assertSame(
            $aRequest->getHeaderLine('Authorization'),
            $bRequest->getHeaderLine('Authorization'),
        );
        $this->assertSame($a->signature, $b->signature);
    }

    /**
     * A pre-signed URL has `X-Amz-Signature` in its query string,
     * `Authorization` is absent, and required query params are present.
     */
    public function testPresignRequest(): void
    {
        $req = new Request('GET', 'https://example.amazonaws.com/photos/thumbnail.jpg', [
            'Host' => 'example.amazonaws.com',
        ]);
        $presigned = $this->signer()->presignRequest(
            $req,
            $this->creds(),
            expires: 3600,
            options: ['timestamp' => self::TIMESTAMP],
        );

        $this->assertFalse($presigned->hasHeader('Authorization'));
        parse_str($presigned->getUri()->getQuery(), $q);
        $this->assertSame('AWS4-HMAC-SHA256', $q['X-Amz-Algorithm']);
        $this->assertSame('20150830T123600Z', $q['X-Amz-Date']);
        $this->assertSame('3600', $q['X-Amz-Expires']);
        $this->assertArrayHasKey('X-Amz-Signature', $q);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $q['X-Amz-Signature']);
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function canonicalPathProvider(): array
    {
        return [
            'space' => ['/b%20c', '/b%2520c', '/b%20c'],
            'double slash' => ['/a//b', '/a/b', '/a//b'],
            'leading and trailing slash' => ['/a/b/', '/a/b/', '/a/b/'],
            'dot segments' => ['/a/./b/../c', '/a/c', '/a/./b/../c'],
            'encoded slash' => ['/a%2Fb', '/a%252Fb', '/a%2Fb'],
            'encoded unicode' => [
                '/%E2%9C%93',
                '/%25E2%259C%2593',
                '/%E2%9C%93',
            ],
            'root' => ['/', '/', '/'],
        ];
    }

    /**
     * Builds the signature the AWS SigV4 steps give for an empty-body GET
     * with the given canonical path.
     */
    private function expectedSignature(string $canonicalPath): string
    {
        $contentHash = hash('sha256', '');
        $canonicalRequest = implode("\n", [
            'GET',
            $canonicalPath,
            '',
            "host:s3.amazonaws.com\n"
                . "x-amz-content-sha256:{$contentHash}\n"
                . "x-amz-date:20150830T123600Z\n",
            'host;x-amz-content-sha256;x-amz-date',
            $contentHash,
        ]);
        $scope = '20150830/' . self::REGION . '/' . self::SERVICE
            . '/aws4_request';
        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            '20150830T123600Z',
            $scope,
            hash('sha256', $canonicalRequest),
        ]);
        $key = hash_hmac('sha256', 'aws4_request', hash_hmac(
            'sha256',
            self::SERVICE,
            hash_hmac(
                'sha256',
                self::REGION,
                hash_hmac('sha256', '20150830', 'AWS4' . self::SECRET, true),
                true
            ),
            true
        ), true);

        return hash_hmac('sha256', $stringToSign, $key);
    }

    #[DataProvider('canonicalPathProvider')]
    public function testSignsCanonicalPathWithDoubleEncoding(
        string $path,
        string $doubleEncoded,
        string $asSent
    ): void
    {
        $req = new Request('GET', 'https://s3.amazonaws.com' . $path, [
            'Host' => 's3.amazonaws.com',
        ]);
        $result = $this->signer()->signRequest(
            $req,
            $this->creds(),
            ['timestamp' => self::TIMESTAMP],
        );

        $this->assertSame(
            $this->expectedSignature($doubleEncoded),
            $result->signature
        );
    }

    #[DataProvider('canonicalPathProvider')]
    public function testSignsCanonicalPathAsSentWhenDoubleEncodingDisabled(
        string $path,
        string $doubleEncoded,
        string $asSent
    ): void
    {
        $req = new Request('GET', 'https://s3.amazonaws.com' . $path, [
            'Host' => 's3.amazonaws.com',
        ]);
        $result = $this->signer()->signRequest(
            $req,
            $this->creds(),
            [
                'timestamp' => self::TIMESTAMP,
                'disable_double_encoding' => true,
            ],
        );

        $this->assertSame(
            $this->expectedSignature($asSent),
            $result->signature
        );
    }

    public function testPresignsPathAsSentWhenDoubleEncodingDisabled(): void
    {
        $req = new Request('GET', 'https://s3.amazonaws.com/a//b%20c', [
            'Host' => 's3.amazonaws.com',
        ]);
        $default = $this->signer()->presignRequest(
            $req,
            $this->creds(),
            3600,
            ['timestamp' => self::TIMESTAMP],
        );
        $disabled = $this->signer()->presignRequest(
            $req,
            $this->creds(),
            3600,
            [
                'timestamp' => self::TIMESTAMP,
                'disable_double_encoding' => true,
            ],
        );

        parse_str($default->getUri()->getQuery(), $defaultQuery);
        parse_str($disabled->getUri()->getQuery(), $disabledQuery);
        $this->assertSame('/a//b%20c', $disabled->getUri()->getPath());
        $this->assertNotSame(
            $defaultQuery['X-Amz-Signature'],
            $disabledQuery['X-Amz-Signature']
        );
    }
}
