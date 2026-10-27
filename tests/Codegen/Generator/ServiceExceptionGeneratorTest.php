<?php

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Generator;

use PHPUnit\Framework\TestCase;
use Smithy\Codegen\CodegenSettings;
use Smithy\Codegen\Generator\ServiceExceptionGenerator;

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
            serviceExceptionClass: 'Aws\\Exception\\AwsException'
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
            outputDir: '/tmp/out'
        );
        $generator = new ServiceExceptionGenerator($settings);

        $file = $generator->generate();

        $this->assertStringContainsString(
            'namespace Aws\\S3\\Exception;',
            $file->content
        );
        $this->assertSame(
            '/tmp/out'
                . DIRECTORY_SEPARATOR
                . 'Exception'
                . DIRECTORY_SEPARATOR
                . 'S3Exception.php',
            $file->path
        );
    }
}
