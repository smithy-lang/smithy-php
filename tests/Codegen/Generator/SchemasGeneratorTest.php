<?php

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Generator;

use PHPUnit\Framework\TestCase;
use Smithy\Codegen\CodegenSettings;
use Smithy\Codegen\Generator\SchemasGenerator;
use Smithy\Codegen\Model\SmithyModel;
use Smithy\Codegen\PhpSymbolProvider;

final class SchemasGeneratorTest extends TestCase
{
    public function testGeneratesSchemasClassAtBaseNamespace(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/defaults-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new SchemasGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate(['smithy.example#Settings' => true]);

        $this->assertSame(
            '/tmp/out' . DIRECTORY_SEPARATOR . 'Schemas.php',
            $file->path
        );
        // Schemas class sits directly in the service's base namespace.
        $this->assertStringContainsString(
            'namespace Smithy\\Example;',
            $file->content
        );
        $this->assertStringContainsString(
            'final class Schemas',
            $file->content
        );
    }

    public function testGeneratedSchemasImportsCoreSchemaAndShapeType(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/defaults-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new SchemasGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate(['smithy.example#Settings' => true]);

        $this->assertStringContainsString(
            'use Smithy\\Core\\Serde\\Schema;',
            $file->content
        );
        $this->assertStringContainsString(
            'use Smithy\\Core\\Shape\\ShapeType;',
            $file->content
        );
    }

    public function testEmitsPerStructureInitMethodForShapesInClosure(): void
    {
        // The `Settings` structure in the closure should get its own init
        // entry.
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/defaults-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new SchemasGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate(['smithy.example#Settings' => true]);

        // The generated `Schemas::settings()` accessor materializes the shape's
        // schema.
        $this->assertStringContainsString('settings', $file->content);
    }

    public function testShapesOutsideClosureAreSkipped(): void
    {
        // With an empty closure, the class should still be generated but
        // contain
        // no per-shape accessor for `Settings`.
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/defaults-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new SchemasGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate([]);

        $this->assertStringContainsString(
            'final class Schemas',
            $file->content
        );
        // No settings-specific accessor when the shape isn't in the closure.
        $this->assertStringNotContainsString(
            'public static function settings(',
            $file->content
        );
    }

    public function testUsesConfiguredSchemaTraitIndex(): void
    {
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Thing' => [
                    'type' => 'structure',
                    'members' => [
                        'name' => [
                            'target' => 'smithy.api#String',
                            'traits' => [
                                'vendor.example#wireName' => 'wire-name'
                            ]
                        ]
                    ]
                ]
            ]
        ], JSON_THROW_ON_ERROR));
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat',
            schemaTraitIndices: [
                'vendor.example#wireName' => 27
            ]
        );
        $generator = new SchemasGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate([
            'smithy.example#Thing' => true
        ]);

        $this->assertStringContainsString(
            "'wire-name']",
            $file->content
        );
    }

    public function testConfiguredSchemaTraitPreservesNestedLiteral(): void
    {
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Thing' => [
                    'type' => 'structure',
                    'members' => [
                        'name' => [
                            'target' => 'smithy.api#String',
                            'traits' => [
                                'vendor.example#metadata' => [
                                    'text' => "customer's value"
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ], JSON_THROW_ON_ERROR));
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat',
            schemaTraitIndices: [
                'vendor.example#metadata' => 27
            ]
        );
        $generator = new SchemasGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate([
            'smithy.example#Thing' => true
        ]);

        $this->assertStringContainsString(
            "['text' => 'customer\\'s value']",
            $file->content
        );
    }

    public function testPreservesEndpointContextParamTraitById(): void
    {
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Input' => [
                    'type' => 'structure',
                    'members' => [
                        'region' => [
                            'target' => 'smithy.api#String',
                            'traits' => [
                                'smithy.rules#contextParam' => [
                                    'name' => 'Region'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ], JSON_THROW_ON_ERROR));
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new SchemasGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate([
            'smithy.example#Input' => true
        ]);

        $this->assertStringContainsString(
            "directTraitsById: ['smithy.rules#contextParam' => "
                . "['name' => 'Region']]",
            $file->content
        );
    }
}
