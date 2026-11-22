<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Codegen\CodeGenerator;
use Smithy\Codegen\CodegenContext;
use Smithy\Codegen\CodegenSettings;
use Smithy\Codegen\Generator\GeneratedFile;
use Smithy\Codegen\Integration\AbstractCodegenIntegration;
use Smithy\Codegen\Model\SmithyModel;
use Smithy\Codegen\PhpSymbolProviderInterface;
use Smithy\Codegen\PhpType;
use Smithy\Core\Shape\Utils\ArrayShapeSerializer;

#[CoversClass(CodeGenerator::class)]
#[CoversClass(CodegenContext::class)]
final class CodegenIntegrationTest extends TestCase
{
    public function testAppliesEveryIntegrationPhase(): void
    {
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Original',
            outputDir: '/tmp/integration-output',
            organization: 'flat'
        );
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/person-model.json'
        );

        $files = (new CodeGenerator(
            $settings,
            $model,
            [new LifecycleIntegration()]
        ))->generate();
        $filesByName = [];
        foreach ($files as $file) {
            $filesByName[\basename($file->path)] = $file;
        }

        $this->assertArrayHasKey('Human.php', $filesByName);
        $this->assertStringContainsString(
            'namespace Smithy\\Integrated;',
            $filesByName['Human.php']->content
        );
        $this->assertStringContainsString(
            'Integration documentation',
            $filesByName['Human.php']->content
        );
        $this->assertStringContainsString(
            '$integratedName',
            $filesByName['Human.php']->content
        );
        $this->assertArrayHasKey('IntegrationMetadata.php', $filesByName);
        $this->assertStringContainsString(
            'built-in files: ',
            $filesByName['IntegrationMetadata.php']->content
        );
    }

    public function testRejectsAdditionalFilePathCollisions(): void
    {
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/integration-output',
            organization: 'flat'
        );
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/person-model.json'
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Multiple generators emitted /tmp/integration-output/Human.php'
        );

        (new CodeGenerator(
            $settings,
            $model,
            [new CollidingIntegration()]
        ))->generate();
    }

    public function testRejectsNormalizedAdditionalFilePathCollisions(): void
    {
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/integration-output',
            organization: 'flat'
        );
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/person-model.json'
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Multiple generators emitted '
                . '/tmp/integration-output/./Human.php'
        );

        (new CodeGenerator(
            $settings,
            $model,
            [new NormalizedCollidingIntegration()]
        ))->generate();
    }

    public function testPreservesPosixBackslashesInAdditionalFilePaths(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            self::markTestSkipped(
                'Backslashes are directory separators on Windows.'
            );
        }

        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/integration-output',
            organization: 'flat'
        );
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/person-model.json'
        );

        $files = (new CodeGenerator(
            $settings,
            $model,
            [new PosixDistinctPathIntegration()]
        ))->generate();
        $paths = \array_map(
            static fn(GeneratedFile $file): string => $file->path,
            $files
        );

        $this->assertContains(
            '/tmp/integration-output/meta\\index.php',
            $paths
        );
        $this->assertContains(
            '/tmp/integration-output/meta/index.php',
            $paths
        );
    }

    public function testRejectsGeneratedShapeNamespaceChanges(): void
    {
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/integration-output',
            organization: 'flat'
        );
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/person-model.json'
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Codegen integration moved smithy.example#Human outside '
                . 'the configured Smithy\\Example namespace: '
                . 'Vendor\\Dto\\Human'
        );

        new CodeGenerator(
            $settings,
            $model,
            [new NamespaceChangingIntegration()]
        );
    }

    public function testRejectsDuplicateSchemaAccessorNames(): void
    {
        $model = SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#ExampleService' => [
                    'type' => 'service',
                    'version' => '1.0',
                    'operations' => [
                        ['target' => 'smithy.example#GetRecord']
                    ]
                ],
                'smithy.example#GetRecord' => [
                    'type' => 'operation',
                    'input' => [
                        'target' => 'smithy.example#GetRecordInput'
                    ],
                    'output' => [
                        'target' => 'smithy.example#GetRecordOutput'
                    ]
                ],
                'smithy.example#GetRecordInput' => [
                    'type' => 'structure',
                    'members' => []
                ],
                'smithy.example#GetRecordOutput' => [
                    'type' => 'structure',
                    'members' => []
                ]
            ]
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Codegen integrations assigned schema accessor Record to both '
                . 'smithy.example#GetRecordInput and '
                . 'smithy.example#GetRecordOutput'
        );

        (new CodeGenerator(
            new CodegenSettings(
                namespace: 'Smithy\\Example',
                outputDir: '/tmp/integration-output'
            ),
            $model,
            [new SchemaAccessorCollisionIntegration()]
        ))->generate();
    }

    public function testDecoratedPropertiesWorkWithRuntimeArrayHelpers(): void
    {
        $namespace = 'Smithy\\Tests\\Codegen\\Integration\\RuntimeGenerated';
        $model = SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Human' => [
                    'type' => 'structure',
                    'members' => [
                        'name' => ['target' => 'smithy.api#String']
                    ]
                ]
            ]
        ]);
        $files = (new CodeGenerator(
            new CodegenSettings(
                namespace: $namespace,
                outputDir: '/tmp/integration-runtime-output',
                organization: 'flat',
                arrayAccess: [
                    'enabled' => true,
                    'categories' => ['all']
                ]
            ),
            $model,
            [new PropertyNameIntegration()]
        ))->generate();
        $filesByName = [];
        foreach ($files as $file) {
            $filesByName[\basename($file->path)] = $file;
        }
        foreach (['Schemas.php', 'Human.php'] as $fileName) {
            eval($this->evalSource($filesByName[$fileName]->content));
        }

        $className = $namespace . '\\Human';
        $human = ArrayShapeSerializer::fromArray(
            $className,
            ['name' => 'Sam']
        );

        $this->assertSame('Sam', $human->integratedName);
        $this->assertTrue(isset($human['name']));
        $this->assertSame('Sam', $human['name']);
    }

    public function testDecoratesImplicitUnitReferencesConsistently(): void
    {
        $namespace =
            'Smithy\\Tests\\Codegen\\Integration\\UnitRuntimeGenerated';
        $model = SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Ping' => [
                    'type' => 'operation'
                ]
            ]
        ]);
        $files = (new CodeGenerator(
            new CodegenSettings(
                namespace: $namespace,
                outputDir: '/tmp/integration-output',
                organization: 'flat'
            ),
            $model,
            [new UnitNameIntegration()]
        ))->generate();
        $filesByName = [];
        foreach ($files as $file) {
            $filesByName[\basename($file->path)] = $file;
        }

        $this->assertArrayHasKey('EmptyShape.php', $filesByName);
        $this->assertArrayNotHasKey('Unit.php', $filesByName);
        $this->assertStringContainsString(
            'final class EmptyShape',
            $filesByName['EmptyShape.php']->content
        );
        eval($this->evalSource($filesByName['EmptyShape.php']->content));
        eval($this->evalSource($filesByName['PingOperation.php']->content));

        $unitClass = $namespace . '\\EmptyShape';
        $operationClass = $namespace . '\\PingOperation';
        $operation = $operationClass::instance();

        $this->assertSame($unitClass, $operation::INPUT_CLASS);
        $this->assertSame($unitClass, $operation::OUTPUT_CLASS);
        $this->assertInstanceOf(
            $unitClass,
            $operation->createInput([])
        );
        $this->assertInstanceOf(
            $unitClass,
            $operation->createOutput([])
        );
    }

    public function testDoesNotValidateUnusedSyntheticUnit(): void
    {
        $model = SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Unit' => [
                    'type' => 'structure',
                    'members' => []
                ],
                'smithy.api#Unit' => [
                    'type' => 'structure',
                    'members' => []
                ]
            ]
        ]);

        $files = (new CodeGenerator(
            new CodegenSettings(
                namespace: 'Smithy\\Example',
                outputDir: '/tmp/integration-output',
                organization: 'flat'
            ),
            $model
        ))->generate();

        $this->assertNotEmpty($files);
    }

    public function testDecoratedErrorMessagePropertyPopulatesExceptionMessage(): void
    {
        $namespace =
            'Smithy\\Tests\\Codegen\\Integration\\ErrorRuntimeGenerated';
        $model = SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#BadRequest' => [
                    'type' => 'structure',
                    'members' => [
                        'Message' => ['target' => 'smithy.api#String']
                    ],
                    'traits' => [
                        'smithy.api#error' => 'client'
                    ]
                ]
            ]
        ]);
        $files = (new CodeGenerator(
            new CodegenSettings(
                namespace: $namespace,
                outputDir: '/tmp/integration-output',
                organization: 'flat'
            ),
            $model,
            [new PropertyNameIntegration()]
        ))->generate();
        $filesByName = [];
        foreach ($files as $file) {
            $filesByName[\basename($file->path)] = $file;
        }

        $this->assertStringContainsString(
            'parent::__construct($errorMessage ?? \'\', ErrorFault::Client);',
            $filesByName['BadRequestException.php']->content
        );
        eval($this->evalSource($filesByName['Schemas.php']->content));
        eval($this->evalSource($filesByName['BadRequestException.php']->content));

        $className = $namespace . '\\BadRequestException';
        $error = new $className(errorMessage: 'Invalid request');

        $this->assertSame('Invalid request', $error->getMessage());
    }

    private function evalSource(string $source): string
    {
        return (string) \preg_replace(
            '/^<\?php\s+(?:\/\*.*?\*\/\s+)?'
            . 'declare\(strict_types=1\);\s*/s',
            '',
            $source
        );
    }
}

final class LifecycleIntegration extends AbstractCodegenIntegration
{
    public function preprocessModel(
        SmithyModel $model,
        CodegenSettings $settings
    ): SmithyModel
    {
        $data = $model->toArray();
        $data['shapes']['smithy.example#Human']['traits'][
            'smithy.api#documentation'
        ] = 'Integration documentation';

        return SmithyModel::fromArray($data);
    }

    public function customizeSettings(
        CodegenSettings $settings,
        SmithyModel $model
    ): CodegenSettings
    {
        return $settings->copyWith([
            'namespace' => 'Smithy\\Integrated'
        ]);
    }

    public function decorateSymbolProvider(
        PhpSymbolProviderInterface $symbolProvider,
        SmithyModel $model,
        CodegenSettings $settings
    ): PhpSymbolProviderInterface
    {
        return new PropertyNameSymbolProvider($symbolProvider);
    }

    public function generateAdditionalFiles(CodegenContext $context): iterable
    {
        yield new GeneratedFile(
            $context->settings->outputDir
                . DIRECTORY_SEPARATOR
                . 'IntegrationMetadata.php',
            "<?php\n// built-in files: "
                . \count($context->generatedFiles)
                . "\n"
        );
    }
}

final class CollidingIntegration extends AbstractCodegenIntegration
{
    public function generateAdditionalFiles(CodegenContext $context): iterable
    {
        yield new GeneratedFile(
            $context->settings->outputDir
                . DIRECTORY_SEPARATOR
                . 'Human.php',
            "<?php\n"
        );
    }
}

final class NormalizedCollidingIntegration extends AbstractCodegenIntegration
{
    public function generateAdditionalFiles(CodegenContext $context): iterable
    {
        yield new GeneratedFile(
            $context->settings->outputDir
                . DIRECTORY_SEPARATOR
                . '.'
                . DIRECTORY_SEPARATOR
                . 'Human.php',
            "<?php\n"
        );
    }
}

final class PosixDistinctPathIntegration extends AbstractCodegenIntegration
{
    public function generateAdditionalFiles(CodegenContext $context): iterable
    {
        yield new GeneratedFile(
            $context->settings->outputDir
                . DIRECTORY_SEPARATOR
                . 'meta\\index.php',
            "<?php\n"
        );
        yield new GeneratedFile(
            $context->settings->outputDir
                . DIRECTORY_SEPARATOR
                . 'meta'
                . DIRECTORY_SEPARATOR
                . 'index.php',
            "<?php\n"
        );
    }
}

final class NamespaceChangingIntegration extends AbstractCodegenIntegration
{
    public function decorateSymbolProvider(
        PhpSymbolProviderInterface $symbolProvider,
        SmithyModel $model,
        CodegenSettings $settings
    ): PhpSymbolProviderInterface
    {
        return new NamespaceChangingSymbolProvider($symbolProvider);
    }
}

final class SchemaAccessorCollisionIntegration extends
    AbstractCodegenIntegration
{
    public function decorateSymbolProvider(
        PhpSymbolProviderInterface $symbolProvider,
        SmithyModel $model,
        CodegenSettings $settings
    ): PhpSymbolProviderInterface
    {
        return new SchemaAccessorCollisionSymbolProvider($symbolProvider);
    }
}

final class PropertyNameIntegration extends AbstractCodegenIntegration
{
    public function decorateSymbolProvider(
        PhpSymbolProviderInterface $symbolProvider,
        SmithyModel $model,
        CodegenSettings $settings
    ): PhpSymbolProviderInterface
    {
        return new PropertyNameSymbolProvider($symbolProvider);
    }
}

final class UnitNameIntegration extends AbstractCodegenIntegration
{
    public function decorateSymbolProvider(
        PhpSymbolProviderInterface $symbolProvider,
        SmithyModel $model,
        CodegenSettings $settings
    ): PhpSymbolProviderInterface
    {
        return new UnitNameSymbolProvider($symbolProvider);
    }
}

final readonly class PropertyNameSymbolProvider implements
    PhpSymbolProviderInterface
{
    public function __construct(
        private PhpSymbolProviderInterface $delegate
    ) {}

    public function toPhpType(string $shapeId): PhpType
    {
        return $this->delegate->toPhpType($shapeId);
    }

    public function toMemberType(
        string $parentShapeId,
        string $memberName
    ): PhpType
    {
        return $this->delegate->toMemberType($parentShapeId, $memberName);
    }

    public function toPropertyName(string $memberName): string
    {
        return match ($memberName) {
            'name' => 'integratedName',
            'Message' => 'errorMessage',
            default => $this->delegate->toPropertyName($memberName)
        };
    }

    public function toClassName(string $shapeId): string
    {
        return $this->delegate->toClassName($shapeId);
    }

    public function toShortClassName(string $shapeId): string
    {
        return $this->delegate->toShortClassName($shapeId);
    }
}

final readonly class UnitNameSymbolProvider implements
    PhpSymbolProviderInterface
{
    public function __construct(
        private PhpSymbolProviderInterface $delegate
    ) {}

    public function toPhpType(string $shapeId): PhpType
    {
        return $shapeId === 'smithy.api#Unit'
            ? new PhpType(
                typeHint: 'EmptyShape',
                fqcn: $this->toClassName($shapeId)
            )
            : $this->delegate->toPhpType($shapeId);
    }

    public function toMemberType(
        string $parentShapeId,
        string $memberName
    ): PhpType
    {
        return $this->delegate->toMemberType($parentShapeId, $memberName);
    }

    public function toPropertyName(string $memberName): string
    {
        return $this->delegate->toPropertyName($memberName);
    }

    public function toClassName(string $shapeId): string
    {
        if ($shapeId !== 'smithy.api#Unit') {
            return $this->delegate->toClassName($shapeId);
        }

        return (string) \preg_replace(
            '/\\\\Unit$/',
            '\\\\EmptyShape',
            $this->delegate->toClassName($shapeId)
        );
    }

    public function toShortClassName(string $shapeId): string
    {
        return $shapeId === 'smithy.api#Unit'
            ? 'EmptyShape'
            : $this->delegate->toShortClassName($shapeId);
    }
}

final readonly class SchemaAccessorCollisionSymbolProvider implements
    PhpSymbolProviderInterface
{
    private const SHAPE_IDS = [
        'smithy.example#GetRecordInput' => true,
        'smithy.example#GetRecordOutput' => true
    ];

    public function __construct(
        private PhpSymbolProviderInterface $delegate
    ) {}

    public function toPhpType(string $shapeId): PhpType
    {
        return isset(self::SHAPE_IDS[$shapeId])
            ? new PhpType(
                typeHint: 'Record',
                fqcn: $this->toClassName($shapeId)
            )
            : $this->delegate->toPhpType($shapeId);
    }

    public function toMemberType(
        string $parentShapeId,
        string $memberName
    ): PhpType
    {
        return $this->delegate->toMemberType($parentShapeId, $memberName);
    }

    public function toPropertyName(string $memberName): string
    {
        return $this->delegate->toPropertyName($memberName);
    }

    public function toClassName(string $shapeId): string
    {
        if (!isset(self::SHAPE_IDS[$shapeId])) {
            return $this->delegate->toClassName($shapeId);
        }

        return (string) \preg_replace(
            '/\\\\GetRecord(?:Input|Output)$/',
            '\\\\Record',
            $this->delegate->toClassName($shapeId)
        );
    }

    public function toShortClassName(string $shapeId): string
    {
        return isset(self::SHAPE_IDS[$shapeId])
            ? 'Record'
            : $this->delegate->toShortClassName($shapeId);
    }
}

final readonly class NamespaceChangingSymbolProvider implements
    PhpSymbolProviderInterface
{
    public function __construct(
        private PhpSymbolProviderInterface $delegate
    ) {}

    public function toPhpType(string $shapeId): PhpType
    {
        return $this->delegate->toPhpType($shapeId);
    }

    public function toMemberType(
        string $parentShapeId,
        string $memberName
    ): PhpType
    {
        return $this->delegate->toMemberType($parentShapeId, $memberName);
    }

    public function toPropertyName(string $memberName): string
    {
        return $this->delegate->toPropertyName($memberName);
    }

    public function toClassName(string $shapeId): string
    {
        return $shapeId === 'smithy.example#Human'
            ? 'Vendor\\Dto\\Human'
            : $this->delegate->toClassName($shapeId);
    }

    public function toShortClassName(string $shapeId): string
    {
        return $this->delegate->toShortClassName($shapeId);
    }
}
