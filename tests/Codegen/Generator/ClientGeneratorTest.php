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
            'use Smithy\\Client\\ClientConfig;',
            $file->content
        );
        $this->assertStringContainsString(
            'use Smithy\\Client\\Plugin\\PluginCollection;',
            $file->content
        );
        $this->assertStringContainsString(
            "public function __construct(\n"
                . "        ClientConfig \$config,\n"
                . "        PluginCollection \$plugins = new "
                . "PluginCollection()\n"
                . '    )',
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

    public function testUsesConfiguredBaseClassAndServiceMetadata(): void
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
            clientConfigClass: 'Vendor\\Runtime\\VendorClientConfig'
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
            'use Vendor\\Runtime\\VendorClientConfig;',
            $file->content
        );
        $this->assertStringContainsString(
            'use Smithy\\Client\\Plugin\\PluginCollection;',
            $file->content
        );
        $this->assertStringContainsString(
            "public function __construct(\n"
                . "        VendorClientConfig \$config,\n"
                . "        PluginCollection \$plugins = new "
                . "PluginCollection()\n"
                . '    )',
            $file->content
        );
        $this->assertStringNotContainsString(
            'ExampleClientConfig $config',
            $file->content
        );
        $this->assertStringContainsString(
            'PluginCollection $plugins = new PluginCollection()',
            $file->content
        );
        $this->assertStringContainsString(
            "parent::__construct(\n"
                . "            \$config->withService("
                . "ExampleService::instance()),\n"
                . "            \$plugins\n"
                . '        );',
            $file->content
        );
        $this->assertStringNotContainsString(
            'VendorPlugin',
            $file->content
        );
    }

    public function testAliasesConstructorTypesToAvoidImportCollisions(): void
    {
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#ExampleService' => [
                    'type' => 'service',
                    'version' => '2026-10-05',
                    'operations' => [
                        ['target' => 'smithy.example#GetThing']
                    ]
                ],
                'smithy.example#GetThing' => [
                    'type' => 'operation',
                    'input' => ['target' => 'smithy.example#ClientConfig'],
                    'output' => [
                        'target' => 'smithy.example#PluginCollection'
                    ],
                    'traits' => [
                        'vendor.example#plugin' => []
                    ]
                ],
                'smithy.example#ClientConfig' => [
                    'type' => 'structure',
                    'members' => []
                ],
                'smithy.example#PluginCollection' => [
                    'type' => 'structure',
                    'members' => []
                ]
            ]
        ], JSON_THROW_ON_ERROR));
        $defaultSettings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out'
        );
        $defaultFile = (new ClientGenerator(
            $model,
            new PhpSymbolProvider($model, $defaultSettings),
            $defaultSettings
        ))->generate('smithy.example#ExampleService');

        $this->assertStringContainsString(
            'use Smithy\\Example\\Input\\ClientConfig;',
            $defaultFile->content
        );
        $this->assertStringContainsString(
            'use Smithy\\Example\\Output\\PluginCollection;',
            $defaultFile->content
        );
        $this->assertStringContainsString(
            'use Smithy\\Client\\ClientConfig as ConfiguredClientConfig;',
            $defaultFile->content
        );
        $this->assertStringContainsString(
            'use Smithy\\Client\\Plugin\\PluginCollection as '
                . 'RuntimePluginCollection;',
            $defaultFile->content
        );
        $this->assertStringContainsString(
            'ConfiguredClientConfig $config,',
            $defaultFile->content
        );
        $this->assertStringContainsString(
            'RuntimePluginCollection $plugins',
            $defaultFile->content
        );

        $pluginCollectionSettings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            clientConfigClass: 'Vendor\\PluginCollection'
        );
        $pluginCollectionFile = (new ClientGenerator(
            $model,
            new PhpSymbolProvider($model, $pluginCollectionSettings),
            $pluginCollectionSettings
        ))->generate('smithy.example#ExampleService');

        $this->assertStringContainsString(
            'use Vendor\\PluginCollection as ConfiguredClientConfig;',
            $pluginCollectionFile->content
        );
        $this->assertStringContainsString(
            'use Smithy\\Client\\Plugin\\PluginCollection as '
                . 'RuntimePluginCollection;',
            $pluginCollectionFile->content
        );
        $this->assertStringContainsString(
            'ConfiguredClientConfig $config,',
            $pluginCollectionFile->content
        );
        $this->assertStringContainsString(
            'RuntimePluginCollection $plugins',
            $pluginCollectionFile->content
        );

        $flatSettings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $flatFile = (new ClientGenerator(
            $model,
            new PhpSymbolProvider($model, $flatSettings),
            $flatSettings
        ))->generate('smithy.example#ExampleService');

        $this->assertStringContainsString(
            'use Smithy\\Client\\ClientConfig as ConfiguredClientConfig;',
            $flatFile->content
        );
        $this->assertStringContainsString(
            'use Smithy\\Client\\Plugin\\PluginCollection as '
                . 'RuntimePluginCollection;',
            $flatFile->content
        );
        $this->assertStringContainsString(
            'function getThing(?ClientConfig $input',
            $flatFile->content
        );
        $this->assertStringContainsString(
            '): PluginCollection',
            $flatFile->content
        );
        $this->assertStringContainsString(
            'ConfiguredClientConfig $config,',
            $flatFile->content
        );
        $this->assertStringContainsString(
            'RuntimePluginCollection $plugins',
            $flatFile->content
        );
    }

    public function testPreservesSameNamespaceConfiguredType(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/defaults-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Vendor',
            outputDir: '/tmp/out',
            organization: 'flat',
            clientConfigClass: 'Vendor\\PluginCollection'
        );

        $file = (new ClientGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        ))->generate('smithy.example#DefaultsService');

        $this->assertStringNotContainsString(
            'use Vendor\\PluginCollection;',
            $file->content
        );
        $this->assertStringContainsString(
            'use Smithy\\Client\\Plugin\\PluginCollection as '
                . 'RuntimePluginCollection;',
            $file->content
        );
        $this->assertStringContainsString(
            'PluginCollection $config,',
            $file->content
        );
        $this->assertStringContainsString(
            'RuntimePluginCollection $plugins',
            $file->content
        );
    }

    public function testAliasesServiceMetadataOnInputCollision(): void
    {
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Example' => [
                    'type' => 'service',
                    'version' => '2026-10-14',
                    'operations' => [
                        ['target' => 'smithy.example#GetThing']
                    ]
                ],
                'smithy.example#GetThing' => [
                    'type' => 'operation',
                    'input' => ['target' => 'smithy.example#ExampleService'],
                    'output' => [
                        'target' => 'smithy.example#ExampleServiceMetadata'
                    ]
                ],
                'smithy.example#ExampleService' => [
                    'type' => 'structure',
                    'members' => []
                ],
                'smithy.example#ExampleServiceMetadata' => [
                    'type' => 'structure',
                    'members' => []
                ]
            ]
        ], JSON_THROW_ON_ERROR));
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out'
        );

        $file = (new ClientGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        ))->generate('smithy.example#Example');

        $this->assertStringContainsString(
            'use Smithy\\Example\\Input\\ExampleService;',
            $file->content
        );
        $this->assertStringContainsString(
            'use Smithy\\Example\\ExampleService '
                . 'as ExampleServiceMetadata2;',
            $file->content
        );
        $this->assertStringContainsString(
            '$config->withService(ExampleServiceMetadata2::instance())',
            $file->content
        );
    }

    public function testDelegatesModeledBehaviorToRuntimePlugins(): void
    {
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#ExampleService' => [
                    'type' => 'service',
                    'version' => '2026-09-04',
                    'operations' => [
                        ['target' => 'smithy.example#PutThing']
                    ]
                ],
                'smithy.example#PutThing' => [
                    'type' => 'operation',
                    'input' => ['target' => 'smithy.example#PutThingInput'],
                    'output' => [
                        'target' => 'smithy.example#PutThingOutput'
                    ],
                    'traits' => [
                        'smithy.api#requestCompression' => [
                            'encodings' => ['gzip']
                        ],
                        'smithy.api#httpChecksumRequired' => []
                    ]
                ],
                'smithy.example#PutThingInput' => [
                    'type' => 'structure',
                    'members' => []
                ],
                'smithy.example#PutThingOutput' => [
                    'type' => 'structure',
                    'members' => []
                ]
            ]
        ], JSON_THROW_ON_ERROR));
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

        $file = $generator->generate(
            'smithy.example#ExampleService'
        );
        $this->assertStringNotContainsString(
            'RequestCompressionPlugin',
            $file->content
        );
        $this->assertStringNotContainsString(
            'HttpChecksumPlugin',
            $file->content
        );
        $this->assertStringContainsString(
            'use Smithy\\Client\\Plugin\\PluginCollection;',
            $file->content
        );
        $this->assertStringContainsString(
            'use Smithy\\Client\\ClientConfig;',
            $file->content
        );
        $this->assertStringContainsString(
            'ClientConfig $config,',
            $file->content
        );
        $this->assertStringContainsString(
            'PluginCollection $plugins = new PluginCollection()',
            $file->content
        );
        $this->assertStringNotContainsString(
            'ExampleClientConfig $config',
            $file->content
        );
        $this->assertStringContainsString(
            '$config->withService(ExampleService::instance())',
            $file->content
        );
    }

    public function testCustomBaseWithoutPluginsUsesConfiguredConstructor(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/defaults-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat',
            clientBaseClass: 'Vendor\\Runtime\\VendorClient',
            clientConfigClass: 'Vendor\\Runtime\\VendorClientConfig'
        );
        $generator = new ClientGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate('smithy.example#DefaultsService');

        $this->assertStringContainsString(
            'final class ExampleClient extends VendorClient',
            $file->content
        );
        $this->assertStringContainsString(
            'use Vendor\\Runtime\\VendorClientConfig;',
            $file->content
        );
        $this->assertStringContainsString(
            'use Smithy\\Client\\Plugin\\PluginCollection;',
            $file->content
        );
        $this->assertStringContainsString(
            'VendorClientConfig $config,',
            $file->content
        );
        $this->assertStringContainsString(
            'PluginCollection $plugins = new PluginCollection()',
            $file->content
        );
        $this->assertStringContainsString(
            '$config->withService(ExampleService::instance())',
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
