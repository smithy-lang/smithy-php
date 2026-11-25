<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Tools;

use PHPUnit\Framework\TestCase;
use Smithy\Codegen\CodegenSettings;
use Smithy\Codegen\Generator\ServiceExceptionGenerator;
use Smithy\Core\Exception\ErrorFault;
use Smithy\Protocol\Aws\AwsProtocolException;
use Smithy\Tools\CodegenOutput\AwsErrorFactoryIntegration;

require_once __DIR__ . '/../../tools/codegen-output/AwsErrorFactoryIntegration.php';

final class AwsErrorFactoryIntegrationTest extends TestCase
{
    public function testWritesFactoryForAwsErrorBase(): void
    {
        $file = self::generate('Aws\\S3', null, AwsProtocolException::class);

        $this->assertStringContainsString(
            'use Smithy\\Core\\Exception\\ErrorFault;',
            $file
        );
        $this->assertStringContainsString(
            "class S3Exception extends AwsProtocolException\n{\n",
            $file
        );
        $this->assertStringContainsString(
            "        ?int \$httpStatusCode\n    ): self\n    {\n"
                . "        return new self(\n",
            $file
        );
    }

    public function testSkipsBaseWithoutAwsError(): void
    {
        $file = self::generate('Aws\\S3', null, \RuntimeException::class);

        $this->assertStringContainsString(
            'class S3Exception extends RuntimeException {}',
            $file
        );
    }

    public function testSkipsDefaultBase(): void
    {
        $file = self::generate('Aws\\S3', null, null);

        $this->assertStringContainsString(
            'class S3Exception extends ApiException {}',
            $file
        );
    }

    public function testFactoryReturnsServiceException(): void
    {
        $namespace = 'Smithy\\Tests\\Codegen\\Generated\\Factory'
            . \bin2hex(\random_bytes(4));
        $file = self::generate(
            $namespace,
            'FactoryException',
            AwsProtocolException::class
        );
        eval(\substr($file, \strlen('<?php')));

        $class = $namespace . '\\FactoryException';
        $error = $class::fromAwsError(
            'denied',
            ErrorFault::Client,
            null,
            'AccessDenied',
            'req-1',
            403
        );

        $this->assertSame($class, $error::class);
        $this->assertSame('denied', $error->getMessage());
        $this->assertSame(ErrorFault::Client, $error->fault);
        $this->assertSame('AccessDenied', $error->getAwsErrorCode());
        $this->assertSame('req-1', $error->getAwsRequestId());
        $this->assertSame(403, $error->getHttpStatusCode());
    }

    private static function generate(
        string $namespace,
        ?string $serviceExceptionClass,
        ?string $baseServiceException
    ): string
    {
        $settings = new CodegenSettings(
            namespace: $namespace,
            outputDir: '/tmp/out',
            organization: 'flat',
            serviceExceptionClass: $serviceExceptionClass,
            baseServiceException: $baseServiceException
        );

        return (new ServiceExceptionGenerator(
            $settings,
            [new AwsErrorFactoryIntegration()]
        ))->generate()->content;
    }
}
