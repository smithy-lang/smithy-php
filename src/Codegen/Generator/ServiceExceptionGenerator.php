<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

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
    private const AWS_ERROR_INTERFACE = 'Smithy\\Protocol\\Aws\\AwsErrorInterface';

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
        $serviceExceptionClass =
            $this->settings->resolveServiceExceptionClass();
        $className = self::shortClassName($serviceExceptionClass);
        $namespace = $this->settings->resolveNamespace(
            \Smithy\Codegen\ShapeCategory::Error
        );
        $outputDir = $this->settings->resolveOutputDir(
            \Smithy\Codegen\ShapeCategory::Error
        );

        $parentFqcn = $this->settings->baseServiceException
            ?? 'Smithy\\Core\\Exception\\ApiException';
        if (\strcasecmp(
            \ltrim($parentFqcn, '\\'),
            \ltrim($serviceExceptionClass, '\\')
        ) === 0) {
            throw new \InvalidArgumentException(
                'baseServiceException must not be the generated service '
                    . 'exception class'
            );
        }
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

        $declaration = "class {$className} extends {$parentClass}";
        if (self::implementsAwsError($parentFqcn)) {
            $this->writeAwsErrorFactory(
                $writer,
                $className,
                $declaration,
                $serviceName
            );
        } elseif (\strlen($declaration . ' {}')
            <= PhpCodeWriter::MAX_GENERATED_LINE_WIDTH
        ) {
            $writer->writeLine($declaration . ' {}');
        } else {
            $writer->writeLine($declaration);
            $writer->writeLine('{}');
        }

        $filePath = $outputDir . DIRECTORY_SEPARATOR . $className . '.php';

        return new GeneratedFile(
            path: $filePath,
            content: $writer->toFileContent()
        );
    }

    /**
     * Report whether the parent carries AWS error metadata.
     *
     * The interface is named by string so codegen keeps no package
     * dependency on `smithy/protocol-aws`.
     */
    private static function implementsAwsError(string $parentFqcn): bool
    {
        return \is_a(\ltrim($parentFqcn, '\\'), self::AWS_ERROR_INTERFACE, true);
    }

    /**
     * Write the class body with a `fromAwsError()` factory.
     *
     * The factory builds the service exception itself, so unmodeled errors
     * keep the service type while modeled errors keep their own constructors.
     */
    private function writeAwsErrorFactory(
        PhpCodeWriter $writer,
        string $className,
        string $declaration,
        string $serviceName
    ): void
    {
        $fault = $writer->addImportReference(
            'Smithy\\Core\\Exception\\ErrorFault',
            'ErrorFault',
            [$className]
        );

        $writer->writeLine($declaration);
        $writer->openBlock('{');
        $writer->writeDocBlock([
            "Create an unmodeled {$serviceName} error from AWS error metadata."
        ]);
        $writer->writeLine('public static function fromAwsError(');
        $writer->pushIndent();
        $writer->writeLine('string $message,');
        $writer->writeLine("{$fault} \$fault,");
        $writer->writeLine('?\\Throwable $previous,');
        $writer->writeLine('?string $awsErrorCode,');
        $writer->writeLine('?string $requestId,');
        $writer->writeLine('?int $httpStatusCode');
        $writer->popIndent();
        $writer->writeLine('): self');
        $writer->openBlock('{');
        $writer->writeLine('return new self(');
        $writer->pushIndent();
        $writer->writeLine('message: $message,');
        $writer->writeLine('fault: $fault,');
        $writer->writeLine('previous: $previous,');
        $writer->writeLine('awsErrorCode: $awsErrorCode,');
        $writer->writeLine('requestId: $requestId,');
        $writer->writeLine('httpStatusCode: $httpStatusCode');
        $writer->popIndent();
        $writer->writeLine(');');
        $writer->closeBlock();
        $writer->closeBlock();
    }

    private static function shortClassName(string $class): string
    {
        $separator = \strrpos($class, '\\');

        return $separator === false
            ? $class
            : \substr($class, $separator + 1);
    }
}
