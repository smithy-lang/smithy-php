<?php

declare(strict_types=1);

namespace Smithy\Codegen\Generator;

use Smithy\Codegen\CodegenSettings;
use Smithy\Codegen\Writer\PhpCodeWriter;

/**
 * Generate the service-level exception class.
 *
 * This is a catch-all exception for any error from a specific service.
 * All modeled error shapes for the service extend this class.
 */
final class ServiceExceptionGenerator
{
    /**
     * @internal
     */
    public function __construct(
        private readonly CodegenSettings $settings
    ) {}

    /**
     * Generate the service exception class.
     */
    public function generate(): GeneratedFile
    {
        $serviceName = $this->settings->resolveServiceName();
        $className = $serviceName . 'Exception';
        $namespace = $this->settings->resolveNamespace(
            \Smithy\Codegen\ShapeCategory::Error
        );
        $outputDir = $this->settings->resolveOutputDir(
            \Smithy\Codegen\ShapeCategory::Error
        );

        $parentFqcn = $this->settings->serviceExceptionClass
            ?? 'Smithy\\Core\\Exception\\ApiException';
        $writer = new PhpCodeWriter($namespace);
        $parentClass = $writer->addImportReference(
            $parentFqcn,
            'BaseServiceException',
            [$className]
        );

        $writer->writeDocBlock([
            "Base exception for all {$serviceName} service errors.",
            '',
            "Catch this to handle any error from the {$serviceName} service,",
            'whether modeled or unmodeled.'
        ]);

        $writer->writeLine("class {$className} extends {$parentClass}");
        $writer->openBlock('{');
        $writer->closeBlock();

        $filePath = $outputDir . DIRECTORY_SEPARATOR . $className . '.php';

        return new GeneratedFile(
            path: $filePath,
            content: $writer->toFileContent()
        );
    }
}
