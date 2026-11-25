<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tools\CodegenOutput;

use Smithy\Codegen\CodegenSettings;
use Smithy\Codegen\Integration\AbstractCodegenIntegration;
use Smithy\Codegen\Writer\PhpCodeWriter;
use Smithy\Protocol\Aws\AwsErrorInterface;

/**
 * Give the AWS protocol test service exceptions a `fromAwsError()` factory.
 *
 * Applies when `baseServiceException` implements `AwsErrorInterface`. The
 * factory builds the service exception with `new self()`, so unmodeled errors
 * keep the service type and modeled errors keep their own constructors. The
 * base constructor takes the named parameters `message`, `fault`, `previous`,
 * `awsErrorCode`, `requestId` and `httpStatusCode`.
 */
final class AwsErrorFactoryIntegration extends AbstractCodegenIntegration
{
    /** @inheritDoc */
    public function name(): string
    {
        return 'aws-error-factory';
    }

    /** @inheritDoc */
    public function serviceExceptionMembers(CodegenSettings $settings): iterable
    {
        $base = $settings->baseServiceException;
        if ($base === null
            || !\is_a(\ltrim($base, '\\'), AwsErrorInterface::class, true)
        ) {
            return [];
        }
        $serviceName = $settings->resolveServiceName();

        return [
            static function (PhpCodeWriter $writer, string $className) use ($serviceName): void {
                self::writeFactory($writer, $className, $serviceName);
            }
        ];
    }

    private static function writeFactory(
        PhpCodeWriter $writer,
        string $className,
        string $serviceName
    ): void
    {
        $fault = $writer->addImportReference(
            'Smithy\\Core\\Exception\\ErrorFault',
            'ErrorFault',
            [$className]
        );

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
    }
}
