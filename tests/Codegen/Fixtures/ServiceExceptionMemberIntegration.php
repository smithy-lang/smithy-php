<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Fixtures;

use Smithy\Codegen\CodegenSettings;
use Smithy\Codegen\Integration\AbstractCodegenIntegration;
use Smithy\Codegen\Writer\PhpCodeWriter;

/**
 * Test fixture: adds one constant per entry in `$names` to the service
 * exception, and imports `Countable` through the class body writer.
 */
final class ServiceExceptionMemberIntegration extends AbstractCodegenIntegration
{
    /**
     * @param list<string> $names
     */
    public function __construct(private readonly array $names = ['FIRST']) {}

    public function serviceExceptionMembers(CodegenSettings $settings): iterable
    {
        foreach ($this->names as $name) {
            yield static function (PhpCodeWriter $writer, string $className) use ($name): void {
                $countable = $writer->addImportReference('Countable', 'Countable');
                $writer->writeLine(
                    "public const {$name} = '{$className}:{$countable}';"
                );
            };
        }
    }
}
