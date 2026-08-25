<?php

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Generator;

use PHPUnit\Framework\TestCase;
use Smithy\Codegen\CodegenSettings;
use Smithy\Codegen\Generator\OperationGenerator;
use Smithy\Codegen\Model\SmithyModel;
use Smithy\Codegen\PhpSymbolProvider;

final class OperationGeneratorTest extends TestCase
{
    public function testGeneratesOperationMetadataClass(): void
    {
        // UpdateSettings is defined in the defaults fixture and takes Settings
        // as input.
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/defaults-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new OperationGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate('smithy.example#UpdateSettings');

        // Emitted class is `<ShortName>Operation` - the `Operation` suffix
        // disambiguates the metadata carrier from the input/output shapes.
        $this->assertSame(
            '/tmp/out' . DIRECTORY_SEPARATOR . 'UpdateSettingsOperation.php',
            $file->path
        );
        // Standard preamble + class named after the short operation name.
        $this->assertStringStartsWith(
            "<?php\n\ndeclare(strict_types=1);",
            $file->content
        );
        $this->assertStringContainsString(
            'class UpdateSettingsOperation',
            $file->content
        );
        // Namespace matches the flat organization.
        $this->assertStringContainsString(
            'namespace Smithy\\Example;',
            $file->content
        );
        $this->assertStringContainsString(
            'public const OUTPUT_CLASS = Unit::class;',
            $file->content
        );
        $this->assertStringContainsString(
            'return Unit::schema();',
            $file->content
        );
        $this->assertStringContainsString(
            'return Unit::fromMembers($members);',
            $file->content
        );
    }

    public function testOperationClassLandsUnderOperationSubNamespace(): void
    {
        // Default organization is categorized -> operations go under
        // `\Operation`.
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/defaults-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out'
        );
        $generator = new OperationGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate('smithy.example#UpdateSettings');

        $this->assertStringContainsString(
            'namespace Smithy\\Example\\Operation;',
            $file->content
        );
        $this->assertSame(
            '/tmp/out'
                . DIRECTORY_SEPARATOR
                . 'Operation'
                . DIRECTORY_SEPARATOR
                . 'UpdateSettingsOperation.php',
            $file->path
        );
    }

    public function testGenerateRaisesForUnknownOperationShapeId(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/defaults-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new OperationGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $this->expectException(\InvalidArgumentException::class);

        $generator->generate('smithy.example#DoesNotExist');
    }

    public function testGenerateRaisesForNonOperationShape(): void
    {
        // The Settings shape is a structure, not an operation - must be
        // rejected.
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/defaults-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new OperationGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $this->expectException(\InvalidArgumentException::class);

        $generator->generate('smithy.example#Settings');
    }

    public function testOmittedInputAndOutputUseUnit(): void
    {
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Ping' => [
                    'type' => 'operation'
                ]
            ]
        ], JSON_THROW_ON_ERROR));
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new OperationGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate('smithy.example#Ping');

        $this->assertStringContainsString(
            'public const INPUT_CLASS = Unit::class;',
            $file->content
        );
        $this->assertStringContainsString(
            'public const OUTPUT_CLASS = Unit::class;',
            $file->content
        );
        $this->assertSame(
            2,
            \substr_count(
                $file->content,
                'return Unit::fromMembers($members);'
            )
        );
    }

    public function testUsesConfiguredErrorDiscriminatorsAndSchemaTraits(): void
    {
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#ExampleService' => [
                    'type' => 'service',
                    'version' => '2026-08-25',
                    'operations' => [
                        ['target' => 'smithy.example#GetThing']
                    ]
                ],
                'smithy.example#GetThing' => [
                    'type' => 'operation',
                    'input' => ['target' => 'smithy.example#GetThingInput'],
                    'output' => [
                        'target' => 'smithy.example#GetThingOutput'
                    ],
                    'errors' => [
                        ['target' => 'smithy.example#NotFound']
                    ]
                ],
                'smithy.example#GetThingInput' => [
                    'type' => 'structure',
                    'members' => []
                ],
                'smithy.example#GetThingOutput' => [
                    'type' => 'structure',
                    'members' => []
                ],
                'smithy.example#NotFound' => [
                    'type' => 'structure',
                    'members' => [],
                    'traits' => [
                        'smithy.api#error' => 'client'
                    ]
                ]
            ]
        ], JSON_THROW_ON_ERROR));
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat',
            errorDiscriminators: [
                'smithy.example#NotFound' => ['NotFoundAlias']
            ],
            operationSchemaTraits: [
                'smithy.example#GetThing' => [
                    27 => ['name' => 'value']
                ]
            ]
        );
        $generator = new OperationGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate('smithy.example#GetThing');

        $this->assertStringContainsString(
            "'NotFoundAlias' => 'smithy.example#NotFound'",
            $file->content
        );
        $this->assertStringContainsString(
            "27 => ['name' => 'value']",
            $file->content
        );
    }

    public function testGeneratesRequestTargetForRpcV2Cbor(): void
    {
        $model = $this->createRequestTargetModel([
            'smithy.protocols#rpcv2Cbor' => []
        ]);
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new OperationGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate('smithy.example#GetThing');

        $this->assertStringContainsString(
            "public const TARGET = 'ExampleService.GetThing';",
            $file->content
        );
        $this->assertStringContainsString(
            'return self::TARGET;',
            $file->content
        );
    }

    public function testDoesNotGenerateRequestTargetForOtherProtocols(): void
    {
        $model = $this->createRequestTargetModel([
            'example.protocols#customRpc' => []
        ]);
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new OperationGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate('smithy.example#GetThing');

        $this->assertStringNotContainsString(
            'public const TARGET',
            $file->content
        );
        $this->assertStringContainsString(
            "public function getRequestTarget(): ?string\n"
                . "    {\n"
                . "        return null;\n"
                . "    }",
            $file->content
        );
    }

    public function testConfiguredRequestTargetOverridesRpcV2Target(): void
    {
        $model = $this->createRequestTargetModel([
            'smithy.protocols#rpcv2Cbor' => []
        ]);
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat',
            operationConstants: [
                'smithy.example#GetThing' => [
                    'TARGET' => 'Custom.GetThing'
                ]
            ]
        );
        $generator = new OperationGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate('smithy.example#GetThing');

        $this->assertStringContainsString(
            "public const TARGET = 'Custom.GetThing';",
            $file->content
        );
        $this->assertStringNotContainsString(
            'ExampleService.GetThing',
            $file->content
        );
    }

    /**
     * @param array<string, mixed> $serviceTraits
     */
    private function createRequestTargetModel(
        array $serviceTraits
    ): SmithyModel
    {
        return SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#ExampleService' => [
                    'type' => 'service',
                    'version' => '2026-08-25',
                    'operations' => [
                        ['target' => 'smithy.example#GetThing']
                    ],
                    'traits' => $serviceTraits
                ],
                'smithy.example#GetThing' => [
                    'type' => 'operation',
                    'input' => [
                        'target' => 'smithy.example#GetThingInput'
                    ],
                    'output' => [
                        'target' => 'smithy.example#GetThingOutput'
                    ]
                ],
                'smithy.example#GetThingInput' => [
                    'type' => 'structure',
                    'members' => []
                ],
                'smithy.example#GetThingOutput' => [
                    'type' => 'structure',
                    'members' => []
                ]
            ]
        ], JSON_THROW_ON_ERROR));
    }
}
