<?php

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Generator;

use PHPUnit\Framework\TestCase;
use Smithy\Codegen\CodegenSettings;
use Smithy\Codegen\Generator\SchemasGenerator;
use Smithy\Codegen\Model\SmithyModel;
use Smithy\Codegen\PhpSymbolProvider;
use Smithy\Codegen\PriorCodegenEvaluator;

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
                    'traits' => [
                        'vendor.example#wireName' => 'root-name'
                    ],
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
        $this->assertStringContainsString(
            "traitsById: ['vendor.example#wireName' => 'root-name']",
            $file->content
        );
        $this->assertStringContainsString(
            "traitsById: ['vendor.example#wireName' => 'wire-name']",
            $file->content
        );
        $this->assertStringContainsString(
            "directTraitsById: ['vendor.example#wireName' => 'wire-name']",
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
        $this->assertStringContainsString(
            "traitsById: ['vendor.example#metadata' => "
                . "['text' => 'customer\\'s value']]",
            $file->content
        );
    }

    public function testPreservesConfiguredTraitIdsOnCollectionSchemas(): void
    {
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Container' => [
                    'type' => 'structure',
                    'members' => [
                        'items' => [
                            'target' => 'smithy.example#ItemList'
                        ],
                        'attributes' => [
                            'target' => 'smithy.example#AttributeMap'
                        ]
                    ]
                ],
                'smithy.example#ItemList' => [
                    'type' => 'list',
                    'member' => [
                        'target' => 'smithy.example#Item',
                        'traits' => [
                            'vendor.example#metadata' => 'list-member'
                        ]
                    ]
                ],
                'smithy.example#AttributeMap' => [
                    'type' => 'map',
                    'key' => [
                        'target' => 'smithy.api#String',
                        'traits' => [
                            'vendor.example#metadata' => 'map-key'
                        ]
                    ],
                    'value' => [
                        'target' => 'smithy.api#String',
                        'traits' => [
                            'vendor.example#metadata' => 'map-value'
                        ]
                    ]
                ],
                'smithy.example#Item' => [
                    'type' => 'structure',
                    'members' => [
                        'name' => [
                            'target' => 'smithy.api#String',
                            'traits' => [
                                'vendor.example#metadata' => 'inline-member'
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

        $content = $generator->generate([
            'smithy.example#Container' => true
        ])->content;

        foreach ([
            'list-member',
            'map-key',
            'map-value',
            'inline-member'
        ] as $value) {
            $this->assertStringContainsString(
                "traitsById: ['vendor.example#metadata' => '{$value}']",
                $content
            );
            $this->assertStringContainsString(
                "directTraitsById: ['vendor.example#metadata' => "
                    . "'{$value}']",
                $content
            );
        }
        $this->assertStringContainsString(
            "listMember: new Schema(id: 'smithy.example#Item', "
                . "type: ShapeType::Structure, memberName: 'member', "
                . "memberIndex: 0",
            $content
        );
        $this->assertStringContainsString(
            "mapKey: new Schema(id: 'smithy.api#String', "
                . "type: ShapeType::String_, memberName: 'key', "
                . "memberIndex: 0",
            $content
        );
        $this->assertStringContainsString(
            "mapValue: new Schema(id: 'smithy.api#String', "
                . "type: ShapeType::String_, memberName: 'value', "
                . "memberIndex: 1",
            $content
        );
    }

    public function testPropagatesConfiguredTargetTraitsToMembers(): void
    {
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Container' => [
                    'type' => 'structure',
                    'members' => [
                        'inherited' => [
                            'target' => 'smithy.example#InheritedString'
                        ],
                        'overridden' => [
                            'target' => 'smithy.example#OverrideString',
                            'traits' => [
                                'vendor.example#metadata' => 'member-value'
                            ]
                        ],
                        'items' => [
                            'target' => 'smithy.example#ItemList'
                        ],
                        'attributes' => [
                            'target' => 'smithy.example#AttributeMap'
                        ]
                    ]
                ],
                'smithy.example#InheritedString' => [
                    'type' => 'string',
                    'traits' => [
                        'vendor.example#metadata' => 'target-value'
                    ]
                ],
                'smithy.example#OverrideString' => [
                    'type' => 'string',
                    'traits' => [
                        'vendor.example#metadata' => 'overridden-target'
                    ]
                ],
                'smithy.example#ItemList' => [
                    'type' => 'list',
                    'member' => [
                        'target' => 'smithy.example#Item'
                    ]
                ],
                'smithy.example#Item' => [
                    'type' => 'structure',
                    'traits' => [
                        'vendor.example#metadata' => 'list-target'
                    ],
                    'members' => [
                        'name' => [
                            'target' => 'smithy.example#InlineString'
                        ]
                    ]
                ],
                'smithy.example#InlineString' => [
                    'type' => 'string',
                    'traits' => [
                        'vendor.example#metadata' => 'inline-target'
                    ]
                ],
                'smithy.example#AttributeMap' => [
                    'type' => 'map',
                    'key' => [
                        'target' => 'smithy.example#MapKey'
                    ],
                    'value' => [
                        'target' => 'smithy.example#MapValue'
                    ]
                ],
                'smithy.example#MapKey' => [
                    'type' => 'string',
                    'traits' => [
                        'vendor.example#metadata' => 'map-key-target'
                    ]
                ],
                'smithy.example#MapValue' => [
                    'type' => 'string',
                    'traits' => [
                        'vendor.example#metadata' => 'map-value-target'
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

        $content = $generator->generate([
            'smithy.example#Container' => true
        ])->content;

        foreach ([
            'target-value',
            'member-value',
            'list-target',
            'inline-target',
            'map-key-target',
            'map-value-target'
        ] as $value) {
            $this->assertStringContainsString(
                "traitsById: ['vendor.example#metadata' => '{$value}']",
                $content
            );
        }
        $this->assertStringContainsString(
            "directTraitsById: ['vendor.example#metadata' => "
                . "'member-value']",
            $content
        );
        $this->assertStringNotContainsString(
            "'overridden-target'",
            $content
        );
        foreach ([
            'target-value',
            'list-target',
            'inline-target',
            'map-key-target',
            'map-value-target'
        ] as $value) {
            $this->assertStringNotContainsString(
                "directTraitsById: ['vendor.example#metadata' => "
                    . "'{$value}']",
                $content
            );
        }
    }

    public function testPropagatesConfiguredTargetTraitsFromMixins(): void
    {
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Container' => [
                    'type' => 'structure',
                    'members' => [
                        'item' => [
                            'target' => 'smithy.example#MixedItem'
                        ]
                    ]
                ],
                'smithy.example#MixedItem' => [
                    'type' => 'structure',
                    'mixins' => [
                        ['target' => 'smithy.example#MetadataMixin']
                    ]
                ],
                'smithy.example#MetadataMixin' => [
                    'type' => 'structure',
                    'mixins' => [
                        ['target' => 'smithy.example#BaseMetadataMixin']
                    ],
                    'traits' => [
                        'smithy.api#mixin' => []
                    ]
                ],
                'smithy.example#BaseMetadataMixin' => [
                    'type' => 'structure',
                    'traits' => [
                        'smithy.api#mixin' => [
                            'localTraits' => [
                                'vendor.example#localMetadata'
                            ]
                        ],
                        'vendor.example#metadata' => 'nested-mixin-value',
                        'vendor.example#localMetadata' => 'local-only'
                    ]
                ]
            ]
        ], JSON_THROW_ON_ERROR));
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat',
            schemaTraitIndices: [
                'vendor.example#metadata' => 27,
                'vendor.example#localMetadata' => 28
            ]
        );
        $generator = new SchemasGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $content = $generator->generate([
            'smithy.example#Container' => true
        ])->content;

        $this->assertStringContainsString(
            "'vendor.example#metadata' => 'nested-mixin-value'",
            $content
        );
        $this->assertStringNotContainsString("'local-only'", $content);
        $this->assertStringNotContainsString(
            "directTraitsById: ['vendor.example#metadata' => "
                . "'nested-mixin-value']",
            $content
        );
    }

    public function testMixinTraitPrecedenceMatchesRuntimeModel(): void
    {
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Container' => [
                    'type' => 'structure',
                    'members' => [
                        'item' => [
                            'target' => 'smithy.example#MixedItem'
                        ]
                    ]
                ],
                'smithy.example#MixedItem' => [
                    'type' => 'structure',
                    'mixins' => [
                        ['target' => 'smithy.example#FirstMixin'],
                        ['target' => 'smithy.example#SecondMixin']
                    ],
                    'traits' => [
                        'vendor.example#localPriority' => 'local-value'
                    ]
                ],
                'smithy.example#FirstMixin' => [
                    'type' => 'structure',
                    'traits' => [
                        'smithy.api#mixin' => [],
                        'vendor.example#mixinPriority' => 'first-value',
                        'vendor.example#localPriority' => 'first-local'
                    ]
                ],
                'smithy.example#SecondMixin' => [
                    'type' => 'structure',
                    'traits' => [
                        'smithy.api#mixin' => [],
                        'vendor.example#mixinPriority' => 'second-value',
                        'vendor.example#localPriority' => 'second-local'
                    ]
                ]
            ]
        ], JSON_THROW_ON_ERROR));
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat',
            schemaTraitIndices: [
                'vendor.example#mixinPriority' => 27,
                'vendor.example#localPriority' => 28
            ]
        );
        $generator = new SchemasGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $content = $generator->generate([
            'smithy.example#Container' => true
        ])->content;

        $this->assertStringContainsString(
            "'vendor.example#mixinPriority' => 'second-value'",
            $content
        );
        $this->assertStringContainsString(
            "'vendor.example#localPriority' => 'local-value'",
            $content
        );
        foreach (['first-value', 'first-local', 'second-local'] as $value) {
            $this->assertStringNotContainsString($value, $content);
        }
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

    public function testInlineStructureUsesTargetConstructorOrder(): void
    {
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Container' => [
                    'type' => 'structure',
                    'members' => [
                        'items' => [
                            'target' => 'smithy.example#ItemList'
                        ]
                    ]
                ],
                'smithy.example#ItemList' => [
                    'type' => 'list',
                    'member' => [
                        'target' => 'smithy.example#Item'
                    ]
                ],
                'smithy.example#Item' => [
                    'type' => 'structure',
                    'members' => [
                        'name' => [
                            'target' => 'smithy.api#String'
                        ],
                        'count' => [
                            'target' => 'smithy.api#Integer',
                            'traits' => [
                                'smithy.api#default' => 0
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

        $content = $generator->generate([
            'smithy.example#Container' => true
        ])->content;
        $count = \strpos(
            $content,
            "memberName: 'count', memberIndex: 0"
        );
        $name = \strpos(
            $content,
            "memberName: 'name', memberIndex: 1"
        );

        $this->assertIsInt($count);
        $this->assertIsInt($name);
        $this->assertLessThan($name, $count);
    }

    public function testInlineStructurePreservesTargetPriorOrder(): void
    {
        $outputDir = \sys_get_temp_dir()
            . \DIRECTORY_SEPARATOR
            . 'smithy-php-inline-order-'
            . \bin2hex(\random_bytes(4));
        \mkdir($outputDir, 0755, true);
        $schemasPath = $outputDir . \DIRECTORY_SEPARATOR . 'Schemas.php';
        \file_put_contents($schemasPath, <<<'PHP'
<?php

final class Schemas
{
    public static function item(): mixed
    {
        return new Schema(
            id: 'smithy.example#Item',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.api#String',
                    type: ShapeType::String_,
                    memberName: 'name',
                    memberIndex: 0
                ),
                new Schema(
                    id: 'smithy.api#Integer',
                    type: ShapeType::Integer,
                    memberName: 'count',
                    memberIndex: 1
                )
            ]
        );
    }
}
PHP);

        try {
            $model = SmithyModel::fromJson(\json_encode([
                'smithy' => '2.0',
                'shapes' => [
                    'smithy.example#Container' => [
                        'type' => 'structure',
                        'members' => [
                            'items' => [
                                'target' => 'smithy.example#ItemList'
                            ]
                        ]
                    ],
                    'smithy.example#ItemList' => [
                        'type' => 'list',
                        'member' => [
                            'target' => 'smithy.example#Item'
                        ]
                    ],
                    'smithy.example#Item' => [
                        'type' => 'structure',
                        'members' => [
                            'name' => [
                                'target' => 'smithy.api#String'
                            ],
                            'count' => [
                                'target' => 'smithy.api#Integer',
                                'traits' => [
                                    'smithy.api#default' => 0
                                ]
                            ]
                        ]
                    ]
                ]
            ], JSON_THROW_ON_ERROR));
            $settings = new CodegenSettings(
                namespace: 'Smithy\\Example',
                outputDir: $outputDir,
                organization: 'flat'
            );
            $priorCodegen = PriorCodegenEvaluator::inspect($outputDir);
            $this->assertNotNull($priorCodegen);
            $generator = new SchemasGenerator(
                $model,
                new PhpSymbolProvider($model, $settings),
                $settings,
                $priorCodegen
            );

            $content = $generator->generate([
                'smithy.example#Container' => true
            ])->content;
            $name = \strpos(
                $content,
                "memberName: 'name', memberIndex: 0"
            );
            $count = \strpos(
                $content,
                "memberName: 'count', memberIndex: 1"
            );

            $this->assertIsInt($name);
            $this->assertIsInt($count);
            $this->assertLessThan($count, $name);
        } finally {
            @\unlink($schemasPath);
            @\rmdir($outputDir);
        }
    }
}
