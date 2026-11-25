<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Generator;

use PHPUnit\Framework\TestCase;
use Smithy\Codegen\CodegenSettings;
use Smithy\Codegen\Generator\ServiceExceptionGenerator;
use Smithy\Tests\Codegen\Fixtures\ServiceExceptionMemberIntegration;

final class ServiceExceptionGeneratorTest extends TestCase
{
    public function testGeneratesExceptionClassExtendingDefaultBaseException(): void
    {
        $settings = new CodegenSettings(
            namespace: 'Aws\\S3',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new ServiceExceptionGenerator($settings);

        $file = $generator->generate();

        $this->assertSame(
            '/tmp/out' . DIRECTORY_SEPARATOR . 'S3Exception.php',
            $file->path
        );
        // Default parent is Smithy\Core\Exception\ApiException.
        $this->assertStringContainsString(
            'use Smithy\\Core\\Exception\\ApiException;',
            $file->content
        );
        $this->assertStringContainsString(
            'class S3Exception extends ApiException {}',
            $file->content
        );
    }

    public function testRespectsServiceExceptionParentOverride(): void
    {
        $settings = new CodegenSettings(
            namespace: 'Aws\\S3',
            outputDir: '/tmp/out',
            organization: 'flat',
            baseServiceException: 'Aws\\Exception\\AwsException'
        );
        $generator = new ServiceExceptionGenerator($settings);

        $file = $generator->generate();

        $this->assertStringContainsString(
            'use Aws\\Exception\\AwsException;',
            $file->content
        );
        $this->assertStringContainsString(
            'class S3Exception extends AwsException {}',
            $file->content
        );
    }

    public function testRespectsServiceExceptionClassOverride(): void
    {
        $settings = new CodegenSettings(
            namespace: 'Aws\\S3',
            outputDir: '/tmp/out',
            organization: 'flat',
            serviceExceptionClass: 'Aws\\S3\\Exception\\StorageException'
        );

        $file = (new ServiceExceptionGenerator($settings))->generate();

        $this->assertSame(
            '/tmp/out' . DIRECTORY_SEPARATOR . 'StorageException.php',
            $file->path
        );
        $this->assertStringContainsString(
            'class StorageException extends ApiException {}',
            $file->content
        );
    }

    public function testRejectsServiceExceptionAsItsOwnParent(): void
    {
        $settings = new CodegenSettings(
            namespace: 'Aws\\S3',
            outputDir: '/tmp/out',
            baseServiceException: 'Aws\\S3\\Exception\\S3Exception'
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'baseServiceException must not be the generated service '
                . 'exception class'
        );

        (new ServiceExceptionGenerator($settings))->generate();
    }

    public function testBreaksLongEmptyClassDeclarationBeforeBody(): void
    {
        $serviceName = \str_repeat('Service', 16);
        $settings = new CodegenSettings(
            namespace: "Aws\\{$serviceName}",
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new ServiceExceptionGenerator($settings);

        $file = $generator->generate();

        $this->assertStringContainsString(
            "class {$serviceName}Exception extends ApiException\n{}",
            $file->content
        );
    }

    public function testExceptionClassLandsInErrorCategoryUnderCategorizedOrganization(): void
    {
        $settings = new CodegenSettings(
            namespace: 'Aws\\S3',
            outputDir: '/tmp/out',
            subNamespaces: [
                'input' => 'Input',
                'output' => 'Output',
                'shapes' => 'Shape',
                'errors' => 'Errors\\V2',
                'operations' => 'Operation',
                'endpoint' => 'Endpoint'
            ]
        );
        $generator = new ServiceExceptionGenerator($settings);

        $file = $generator->generate();

        $this->assertStringContainsString(
            'namespace Aws\\S3\\Errors\\V2;',
            $file->content
        );
        $this->assertSame(
            '/tmp/out'
                . DIRECTORY_SEPARATOR
                . 'Errors'
                . DIRECTORY_SEPARATOR
                . 'V2'
                . DIRECTORY_SEPARATOR
                . 'S3Exception.php',
            $file->path
        );
    }

    public function testIntegrationsWriteServiceExceptionMembers(): void
    {
        $settings = new CodegenSettings(
            namespace: 'Aws\\S3',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new ServiceExceptionGenerator($settings, [
            new ServiceExceptionMemberIntegration(['FIRST', 'SECOND'])
        ]);

        $file = $generator->generate();

        $this->assertStringContainsString('use Countable;', $file->content);
        $this->assertStringContainsString(
            "class S3Exception extends ApiException\n{\n"
                . "    public const FIRST = 'S3Exception:Countable';\n"
                . "\n"
                . "    public const SECOND = 'S3Exception:Countable';\n"
                . "}\n",
            $file->content
        );
    }

    public function testIntegrationWithoutMembersKeepsEmptyClass(): void
    {
        $settings = new CodegenSettings(
            namespace: 'Aws\\S3',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new ServiceExceptionGenerator($settings, [
            new ServiceExceptionMemberIntegration([])
        ]);

        $file = $generator->generate();

        $this->assertStringContainsString(
            'class S3Exception extends ApiException {}',
            $file->content
        );
    }
}
