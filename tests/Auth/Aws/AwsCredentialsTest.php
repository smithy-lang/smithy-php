<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Auth\Aws;

use Smithy\Auth\Aws\AwsCredentials;
use Smithy\Auth\Aws\AwsCredentialsIdentityInterface;
use PHPUnit\Framework\TestCase;
use Smithy\Auth\IdentityInterface;

/**
 * Unit tests for {@see AwsCredentials}. Also serve as the contract test
 * for the {@see AwsCredentialsIdentityInterface} interface — the value class is
 * the canonical implementation and consumers depend on the getters
 * returning the exact same values as the readonly properties.
 */
final class AwsCredentialsTest extends TestCase
{
    public function testImplementsIdentityInterfaces(): void
    {
        $creds = new AwsCredentials('AKID', 'SECRET');
        $this->assertInstanceOf(AwsCredentialsIdentityInterface::class, $creds);
        $this->assertInstanceOf(IdentityInterface::class, $creds);
    }

    public function testAccessorsReturnConstructedValues(): void
    {
        $creds = new AwsCredentials('AKID', 'SECRET', 'TOKEN', 1234.5, '123456789012');

        $this->assertSame('AKID', $creds->getAccessKeyId());
        $this->assertSame('SECRET', $creds->getSecretAccessKey());
        $this->assertSame('123456789012', $creds->getAccountId());
        $this->assertSame('TOKEN', $creds->getSessionToken());
        $this->assertSame(1234.5, $creds->getExpirationTime());
    }

    public function testGettersMirrorPublicProperties(): void
    {
        $creds = new AwsCredentials('AKID', 'SECRET', 'TOKEN', 1234.5, '123456789012');

        // Both views must always agree — internal callers may use either.
        $this->assertSame($creds->accessKeyId, $creds->getAccessKeyId());
        $this->assertSame($creds->secretAccessKey, $creds->getSecretAccessKey());
        $this->assertSame($creds->accountId, $creds->getAccountId());
        $this->assertSame($creds->sessionToken, $creds->getSessionToken());
        $this->assertSame($creds->expiration, $creds->getExpirationTime());
    }

    public function testOptionalFieldsDefaultToNull(): void
    {
        $creds = new AwsCredentials('AKID', 'SECRET');

        $this->assertNull($creds->getAccountId());
        $this->assertNull($creds->getSessionToken());
        $this->assertNull($creds->getExpirationTime());
    }
}
