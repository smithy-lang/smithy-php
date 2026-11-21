<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Auth\Aws;

use Smithy\Auth\Aws\SigV4Signer;
use Smithy\Auth\Aws\AwsCredentials;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Smithy\Async\RevoltEventLoop;
use Smithy\Auth\IdentityInterface;
use Smithy\Auth\SignerResult;
use Smithy\Http\Message\AsyncRequestStream;
use Smithy\Http\Message\HttpTransportRequest;
use Smithy\Http\Message\Request;

/**
 * Tests for {@see SigV4Signer} — the Smithy `SignerInterface` adapter
 * that wraps {@see SignatureV4}.
 */
final class SigV4SignerTest extends TestCase
{
    /**
     * @param array<string, mixed> $properties
     */
    private function sign(
        SigV4Signer $signer,
        RequestInterface $request,
        IdentityInterface $identity,
        array $properties = []
    ): SignerResult
    {
        return $signer->sign(
            new HttpTransportRequest($request),
            $identity,
            $properties
        );
    }

    private function signedRequest(SignerResult $result): RequestInterface
    {
        return HttpTransportRequest::expect($result->signedRequest)->request;
    }

    public function testSignsRequestWithDefaultServiceAndRegion(): void
    {
        $signer = new SigV4Signer('lambda', 'us-east-1');
        $req = new Request('GET', 'https://lambda.us-east-1.amazonaws.com/2015-03-31/functions/', [
            'Host' => 'lambda.us-east-1.amazonaws.com',
        ]);
        $creds = new AwsCredentials('AKID', 'SECRET');

        $result = $this->sign(
            $signer,
            $req,
            $creds,
            ['timestamp' => 1440938160]
        );
        $signed = $this->signedRequest($result);

        $auth = $signed->getHeaderLine('Authorization');
        $this->assertStringStartsWith('AWS4-HMAC-SHA256 ', $auth);
        $this->assertStringContainsString(
            'Credential=AKID/20150830/us-east-1/lambda/aws4_request',
            $auth,
        );
        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{64}$/',
            $result->signature
        );
        $this->assertStringEndsWith(
            'Signature=' . $result->signature,
            $auth
        );
    }

    public function testOverridesSigningNameAndRegionFromProperties(): void
    {
        $signer = new SigV4Signer('lambda', 'us-east-1');
        $req = new Request('GET', 'https://sts.amazonaws.com/', [
            'Host' => 'sts.amazonaws.com',
        ]);
        $creds = new AwsCredentials('AKID', 'SECRET');

        $result = $this->sign(
            $signer,
            $req,
            $creds,
            [
                'signingName' => 'sts',
                'signingRegion' => 'us-west-2',
                'timestamp' => 1440938160,
            ]
        );
        $signed = $this->signedRequest($result);

        $this->assertStringContainsString(
            'Credential=AKID/20150830/us-west-2/sts/aws4_request',
            $signed->getHeaderLine('Authorization'),
        );
    }

    public function testUnsignedPayloadOptionPropagates(): void
    {
        $signer = new SigV4Signer('s3', 'us-east-1');
        $req = new Request('PUT', 'https://s3.amazonaws.com/bucket/key', [
            'Host' => 'bucket.s3.amazonaws.com',
        ], 'body');

        $result = $this->sign(
            $signer,
            $req,
            new AwsCredentials('AKID', 'SECRET'),
            [
                'unsignedPayload' => true,
                'timestamp' => 1440938160,
            ]
        );
        $signed = $this->signedRequest($result);

        $this->assertSame('UNSIGNED-PAYLOAD', $signed->getHeaderLine('X-Amz-Content-Sha256'));
    }

    public function testS3HashesSeekableBody(): void
    {
        $signer = new SigV4Signer('s3', 'us-east-1');
        $request = new Request(
            'PUT',
            'https://bucket.s3.amazonaws.com/key',
            [],
            'payload'
        );

        $result = $this->sign(
            $signer,
            $request,
            new AwsCredentials('AKID', 'SECRET'),
            ['timestamp' => 1440938160]
        );
        $signed = $this->signedRequest($result);

        $this->assertSame(
            \hash('sha256', 'payload'),
            $signed->getHeaderLine('X-Amz-Content-Sha256')
        );
    }

    public function testExplicitUnsignedPayloadFalseRejectsS3Stream(): void
    {
        $signer = new SigV4Signer('s3', 'us-east-1');
        $request = new Request(
            'PUT',
            'https://bucket.s3.amazonaws.com/key',
            [],
            new AsyncRequestStream(
                new RevoltEventLoop(),
                expectedLength: 16
            )
        );

        $this->expectException(\RuntimeException::class);

        $this->sign(
            $signer,
            $request,
            new AwsCredentials('AKID', 'SECRET'),
            [
                'timestamp' => 1440938160,
                'unsignedPayload' => false
            ]
        );
    }

    public function testNonS3RejectsNonSeekableBody(): void
    {
        $signer = new SigV4Signer('lambda', 'us-east-1');
        $request = new Request(
            'PUT',
            'https://lambda.us-east-1.amazonaws.com/',
            [],
            new AsyncRequestStream(
                new RevoltEventLoop(),
                expectedLength: 16
            )
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Cannot calculate a SigV4 payload hash for a non-seekable '
            . 'request body.'
        );

        $this->sign(
            $signer,
            $request,
            new AwsCredentials('AKID', 'SECRET'),
            ['timestamp' => 1440938160]
        );
    }

    public function testRejectsNonAwsCredentialsIdentity(): void
    {
        $signer = new SigV4Signer('lambda', 'us-east-1');
        $req = new Request('GET', 'https://example.com/');

        // Any IdentityInterface impl other than AwsCredentials is rejected.
        $foreignIdentity = new class implements IdentityInterface {
            public function getExpirationTime(): ?float
            {
                return null;
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SigV4Signer requires AwsCredentialsIdentityInterface');
        $this->sign($signer, $req, $foreignIdentity);
    }

    public function testCloseIsIdempotent(): void
    {
        $signer = new SigV4Signer('lambda', 'us-east-1');

        $signer->close();
        $signer->close();

        $this->addToAssertionCount(1);
    }

    public function testDisableDoubleEncodingPropertySignsPathAsSent(): void
    {
        $signer = new SigV4Signer('s3', 'us-east-1');
        $req = new Request('GET', 'https://s3.amazonaws.com/a//b%20c', [
            'Host' => 's3.amazonaws.com',
        ]);
        $creds = new AwsCredentials('AKID', 'SECRET');

        $default = $this->sign(
            $signer,
            $req,
            $creds,
            ['timestamp' => 1440938160]
        );
        $disabled = $this->sign(
            $signer,
            $req,
            $creds,
            ['timestamp' => 1440938160, 'disableDoubleEncoding' => true]
        );

        $this->assertNotSame($default->signature, $disabled->signature);
        $this->assertSame(
            '/a//b%20c',
            $this->signedRequest($disabled)->getUri()->getPath()
        );
    }

    public function testDisableDoubleEncodingFalseKeepsDefaultSigning(): void
    {
        $signer = new SigV4Signer('s3', 'us-east-1');
        $req = new Request('GET', 'https://s3.amazonaws.com/a//b%20c', [
            'Host' => 's3.amazonaws.com',
        ]);
        $creds = new AwsCredentials('AKID', 'SECRET');

        $default = $this->sign(
            $signer,
            $req,
            $creds,
            ['timestamp' => 1440938160]
        );
        $explicit = $this->sign(
            $signer,
            $req,
            $creds,
            ['timestamp' => 1440938160, 'disableDoubleEncoding' => false]
        );

        $this->assertSame($default->signature, $explicit->signature);
    }
}
