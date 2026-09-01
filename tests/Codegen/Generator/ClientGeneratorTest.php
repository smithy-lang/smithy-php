<?php

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Generator;

use PHPUnit\Framework\TestCase;
use Smithy\Codegen\CodegenSettings;
use Smithy\Codegen\Generator\ClientGenerator;
use Smithy\Codegen\Model\SmithyModel;
use Smithy\Codegen\PhpSymbolProvider;

final class ClientGeneratorTest extends TestCase
{
    public function testGeneratesClientClassForKnownServiceShape(): void
    {
        // The generator applies a service-name simplification:
        // `DefaultsService`
        // -> `Example` (drops the `Service` suffix, historical simplification),
        // and emits `<ShortName>Client`.
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/defaults-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new ClientGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate('smithy.example#DefaultsService');

        $this->assertStringStartsWith(
            "<?php\n\ndeclare(strict_types=1);",
            $file->content
        );
        // Class extends AbstractClient and its name ends in `Client`.
        $this->assertStringContainsString(
            'extends AbstractClient',
            $file->content
        );
        $this->assertMatchesRegularExpression(
            '/final class \w+Client extends AbstractClient/',
            $file->content
        );
        $this->assertStringEndsWith('Client.php', $file->path);
        // Sits directly under the configured namespace with flat organization.
        $this->assertStringContainsString(
            'namespace Smithy\\Example;',
            $file->content
        );
        $this->assertStringContainsString(
            'public function updateSettings(',
            $file->content
        );
        $this->assertStringContainsString(
            '): Unit',
            $file->content
        );
    }

    public function testImportsAbstractClientBaseClass(): void
    {
        // The generated client is a thin subclass of Smithy's AbstractClient.
        // the base class holds the pipeline, retry loop, endpoint resolver,
        // etc.
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/defaults-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new ClientGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate('smithy.example#DefaultsService');

        $this->assertStringContainsString(
            'use Smithy\\Client\\AbstractClient;',
            $file->content
        );
    }

    public function testGenerateRaisesForUnknownServiceShapeId(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/defaults-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new ClientGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $this->expectException(\InvalidArgumentException::class);

        $generator->generate('smithy.example#Nonexistent');
    }

    public function testGenerateRaisesForNonServiceShape(): void
    {
        // Point at a structure shape - the generator must reject.
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/defaults-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new ClientGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $this->expectException(\InvalidArgumentException::class);

        $generator->generate('smithy.example#Settings');
    }

    public function testUsesConfiguredBaseClassAndTraitPlugins(): void
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
                    'traits' => [
                        'vendor.example#plugin' => []
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
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat',
            clientBaseClass: 'Vendor\\Runtime\\VendorClient',
            clientPluginsByOperationTrait: [
                'vendor.example#plugin' =>
                    'Vendor\\Runtime\\VendorPlugin'
            ]
        );
        $generator = new ClientGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate(
            'smithy.example#ExampleService'
        );

        $this->assertStringContainsString(
            'use Vendor\\Runtime\\VendorClient;',
            $file->content
        );
        $this->assertStringContainsString(
            'use Vendor\\Runtime\\VendorPlugin;',
            $file->content
        );
        $this->assertStringContainsString(
            'use Smithy\\Client\\Plugin\\PluginCollection;',
            $file->content
        );
        $this->assertStringContainsString(
            'PluginCollection $plugins = new PluginCollection()',
            $file->content
        );
        $this->assertStringContainsString(
            '$plugins = $plugins->add(new VendorPlugin());',
            $file->content
        );
    }

    public function testUsesConfiguredWaiterResourceInitialisms(): void
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
                    'traits' => [
                        'smithy.waiters#waitable' => [
                            'APIReady' => [
                                'acceptors' => [
                                    [
                                        'state' => 'success',
                                        'matcher' => ['success' => true]
                                    ]
                                ]
                            ]
                        ]
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
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat',
            waiterResourceInitialisms: ['api' => 'API']
        );
        $generator = new ClientGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate(
            'smithy.example#ExampleService'
        );

        $this->assertStringContainsString(
            'reports the API as ready.',
            $file->content
        );
    }
}
