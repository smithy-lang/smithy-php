<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Generator;

use PHPUnit\Framework\TestCase;
use Smithy\Client\Dynamic\DynamicService;
use Smithy\Codegen\CodegenSettings;
use Smithy\Codegen\Generator\SchemasGenerator;
use Smithy\Codegen\Model\SmithyModel;
use Smithy\Codegen\PhpSymbolProvider;
use Smithy\Codegen\PriorCodegenEvaluator;
use Smithy\Core\Model\SmithyModel as RuntimeSmithyModel;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SchemaConverter;
use Smithy\Core\Shape\ServiceInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RpcV2Cbor\RpcV2CborService;
use Smithy\ProtocolTests\RpcV2Json\RpcV2JsonService;

final class SchemasGeneratorTest extends TestCase
{
    public function testGeneratedSchemasMatchRuntimeSemantics(): void
    {
        $root = \dirname(__DIR__, 3);
        $cases = [
            [
                RpcV2CborService::instance(),
                $root . '/tools/codegen-output/models/rpcv2-cbor.json',
                'smithy.protocoltests.rpcv2Cbor#RpcV2Protocol'
            ],
            [
                RpcV2JsonService::instance(),
                $root . '/tools/codegen-output/models/rpcv2-json.json',
                'smithy.protocoltests.rpcv2Json#RpcV2JsonProtocol'
            ]
        ];

        foreach ($cases as [$generated, $modelPath, $serviceId]) {
            $model = RuntimeSmithyModel::fromFile($modelPath);
            $runtime = new DynamicService(
                $model,
                new SchemaConverter($model),
                $serviceId
            );

            self::assertSame(
                $this->normalizeServiceSchemas($runtime),
                $this->normalizeServiceSchemas($generated),
                "Generated schema semantics differ for {$serviceId}."
            );
        }
    }

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

    public function testImportsTargetClassesAndUsesShortReferences(): void
    {
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Thing' => [
                    'type' => 'structure',
                    'members' => []
                ]
            ]
        ], JSON_THROW_ON_ERROR));
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out'
        );
        $generator = new SchemasGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $content = $generator->generate([
            'smithy.example#Thing' => true
        ])->content;

        $this->assertStringContainsString(
            'use Smithy\\Example\\Shape\\Thing;',
            $content
        );
        $this->assertStringContainsString(
            'targetClass: Thing::class',
            $content
        );
        $this->assertStringNotContainsString(
            'targetClass: \\Smithy\\Example\\Shape\\Thing::class',
            $content
        );
    }

    public function testAliasesCollidingTargetClassImports(): void
    {
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Schema' => [
                    'type' => 'structure',
                    'members' => []
                ],
                'smithy.example#SchemaTarget' => [
                    'type' => 'structure',
                    'members' => []
                ]
            ]
        ], JSON_THROW_ON_ERROR));
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out'
        );
        $generator = new SchemasGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $content = $generator->generate([
            'smithy.example#Schema' => true,
            'smithy.example#SchemaTarget' => true
        ])->content;

        $this->assertStringContainsString(
            'use Smithy\\Example\\Shape\\Schema as SchemaTarget2;',
            $content
        );
        $this->assertStringContainsString(
            'use Smithy\\Example\\Shape\\SchemaTarget;',
            $content
        );
        $this->assertStringContainsString(
            'targetClass: SchemaTarget2::class',
            $content
        );
        $this->assertStringContainsString(
            'targetClass: SchemaTarget::class',
            $content
        );
    }

    public function testRendersSparseTraitIndexesWithoutNullPadding(): void
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
                                'smithy.api#required' => []
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
            'smithy.example#Thing' => true
        ])->content;

        $this->assertStringContainsString(
            'traits: [14 => true]',
            $content
        );
        $this->assertStringNotContainsString('traits: [null', $content);
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
            "traits: [27 => 'wire-name']",
            $file->content
        );
        $this->assertStringContainsString(
            "traits: [27 => 'root-name']",
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

    /**
     * @return array<string, mixed>
     */
    private function normalizeServiceSchemas(ServiceInterface $service): array
    {
        $schemas = [];
        $operations = [];
        $this->addSchema($service->getSchema(), $schemas);

        foreach ($service->getOperations() as $operation) {
            $this->addSchema($operation->getSchema(), $schemas);
            $this->addSchema($operation->getInputSchema(), $schemas);
            $this->addSchema($operation->getOutputSchema(), $schemas);
            $errors = [];
            foreach ($operation->getErrorSchemas() as $error) {
                $this->addSchema($error, $schemas);
                $errors[] = $error->id;
            }
            $operations[$operation->getId()] = [
                'input' => $operation->getInputSchema()->id,
                'output' => $operation->getOutputSchema()->id,
                'errors' => $errors
            ];
        }

        \ksort($operations, SORT_STRING);
        \ksort($schemas, SORT_STRING);

        return [
            'service' => $service->getSchema()->id,
            'operations' => $operations,
            'schemas' => $schemas
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $schemas
     */
    private function addSchema(Schema $schema, array &$schemas): void
    {
        if (isset($schemas[$schema->id])) {
            return;
        }

        $schemas[$schema->id] = $this->normalizeSchema($schema, []);
    }

    /**
     * @param array<string, true> $visiting
     * @return array<string, mixed>
     */
    private function normalizeSchema(Schema $schema, array $visiting): array
    {
        if (isset($visiting[$schema->id])) {
            return ['ref' => $schema->id];
        }
        $visiting[$schema->id] = true;

        $target = null;
        if ($schema->isMember()
            && \in_array(
                $schema->type,
                [
                    ShapeType::Structure->value,
                    ShapeType::Union->value
                ],
                true
            )
        ) {
            $resolved = $schema->resolveTarget();
            if ($resolved !== $schema) {
                $this->assertPopulatedInlineIdsMatchTarget(
                    $schema,
                    $resolved
                );
                $target = $this->normalizeSchema($resolved, $visiting);
            }
        }

        $members = [];
        if ($target === null) {
            foreach ($schema->getMembers() as $name => $member) {
                $members[$name] = $this->normalizeSchema(
                    $member,
                    $visiting
                );
            }
        }
        \ksort($members, SORT_STRING);

        return [
            'id' => $schema->id,
            'type' => $schema->type,
            'isMember' => $schema->isMember(),
            'name' => $schema->memberName,
            'index' => $schema->memberIndex,
            'traits' => $this->normalizeValue($schema->getTraits()),
            'target' => $target,
            'members' => $members,
            'listMember' => $schema->getListMember() === null
                ? null
                : $this->normalizeSchema(
                    $schema->getListMember(),
                    $visiting
                ),
            'mapKey' => $schema->getMapKey() === null
                ? null
                : $this->normalizeSchema(
                    $schema->getMapKey(),
                    $visiting
                ),
            'mapValue' => $schema->getMapValue() === null
                ? null
                : $this->normalizeSchema(
                    $schema->getMapValue(),
                    $visiting
                )
        ];
    }

    private function assertPopulatedInlineIdsMatchTarget(
        Schema $schema,
        Schema $target
    ): void
    {
        $members = $schema->getMembers();
        if ($members !== []) {
            $targetMembers = $target->getMembers();
            $inlineIds = [];
            foreach ($members as $name => $member) {
                $inlineIds[$name] = $member->id;
            }

            $targetIds = [];
            foreach ($targetMembers as $name => $member) {
                $targetIds[$name] = $member->id;
            }

            \ksort($inlineIds, SORT_STRING);
            \ksort($targetIds, SORT_STRING);

            self::assertSame(
                $targetIds,
                $inlineIds,
                "Inline member IDs differ for {$schema->id}."
            );

            foreach ($members as $name => $member) {
                $targetMember = $targetMembers[$name] ?? null;
                self::assertNotNull($targetMember);
                $this->assertPopulatedInlineIdsMatchTarget(
                    $member,
                    $targetMember
                );
            }
        }

        $this->assertPopulatedInlineCollectionIdMatchesTarget(
            $schema->getListMember(),
            $target->getListMember(),
            'list member',
            $schema->id
        );
        $this->assertPopulatedInlineCollectionIdMatchesTarget(
            $schema->getMapKey(),
            $target->getMapKey(),
            'map key',
            $schema->id
        );
        $this->assertPopulatedInlineCollectionIdMatchesTarget(
            $schema->getMapValue(),
            $target->getMapValue(),
            'map value',
            $schema->id
        );
    }

    private function assertPopulatedInlineCollectionIdMatchesTarget(
        ?Schema $schema,
        ?Schema $target,
        string $kind,
        string $containerId
    ): void
    {
        if ($schema === null) {
            return;
        }

        self::assertNotNull(
            $target,
            "Inline {$kind} is missing from target {$containerId}."
        );
        self::assertSame(
            $target->id,
            $schema->id,
            "Inline {$kind} ID differs for {$containerId}."
        );
        $this->assertPopulatedInlineIdsMatchTarget(
            $schema,
            $target
        );
    }

    /**
     * Normalize trait values that may contain schema references.
     */
    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof Schema) {
            return $value->id;
        }
        if (!\is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->normalizeValue($item);
        }
        if (!\array_is_list($value)) {
            \ksort($value, SORT_STRING);
        }

        return $value;
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
            "listMember: SchemasMembers::\$shared"
                . "['smithy.example#ItemList\$member'] ??= "
                . "new Schema(id: 'smithy.example#ItemList\$member', "
                . "type: ShapeType::Structure, memberName: 'member', "
                . "memberIndex: 0",
            $content
        );
        $this->assertStringContainsString(
            "mapKey: SchemasMembers::\$shared"
                . "['smithy.example#AttributeMap\$key'] ??= "
                . "new Schema(id: 'smithy.example#AttributeMap\$key', "
                . "type: ShapeType::String_, memberName: 'key', "
                . "memberIndex: 0",
            $content
        );
        $this->assertStringContainsString(
            "mapValue: SchemasMembers::\$shared"
                . "['smithy.example#AttributeMap\$value'] ??= "
                . "new Schema(id: 'smithy.example#AttributeMap\$value', "
                . "type: ShapeType::String_, memberName: 'value', "
                . "memberIndex: 1",
            $content
        );
    }

    public function testReadsSharedMemberAfterFirstUseInSameMethod(): void
    {
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Container' => [
                    'type' => 'structure',
                    'members' => [
                        'first' => ['target' => 'smithy.example#Names'],
                        'second' => ['target' => 'smithy.example#Names']
                    ]
                ],
                'smithy.example#Names' => [
                    'type' => 'list',
                    'member' => ['target' => 'smithy.api#String']
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

        $shared = "SchemasMembers::\$shared['smithy.example#Names\$member']";
        $this->assertSame(1, \substr_count($content, "{$shared} ??= "));
        $this->assertStringContainsString(
            "memberName: 'second', memberIndex: 1, listMember: {$shared})",
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

    public function testSharesAcyclicMemberSchemasAndCopiesCyclicOnes(): void
    {
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Container' => [
                    'type' => 'structure',
                    'members' => [
                        'item' => ['target' => 'smithy.example#Item'],
                        'items' => ['target' => 'smithy.example#ItemList'],
                        'owner' => ['target' => 'smithy.example#Owner']
                    ]
                ],
                'smithy.example#Other' => [
                    'type' => 'structure',
                    'members' => [
                        'items' => ['target' => 'smithy.example#ItemList']
                    ]
                ],
                'smithy.example#ItemList' => [
                    'type' => 'list',
                    'member' => ['target' => 'smithy.example#Item']
                ],
                'smithy.example#Item' => [
                    'type' => 'structure',
                    'members' => [
                        'name' => ['target' => 'smithy.api#String']
                    ]
                ],
                'smithy.example#Owner' => [
                    'type' => 'structure',
                    'members' => [
                        'pet' => ['target' => 'smithy.example#Pet']
                    ]
                ],
                'smithy.example#Pet' => [
                    'type' => 'structure',
                    'members' => [
                        'owner' => ['target' => 'smithy.example#Owner']
                    ]
                ]
            ]
        ], JSON_THROW_ON_ERROR));
        $namespace = 'Smithy\\Tests\\Generated\\SharedMemberSchemas';
        $settings = new CodegenSettings(
            namespace: $namespace,
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new SchemasGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );
        $content = $generator->generate([
            'smithy.example#Container' => true,
            'smithy.example#Other' => true,
            'smithy.example#ItemList' => true,
            'smithy.example#Item' => true,
            'smithy.example#Owner' => true,
            'smithy.example#Pet' => true
        ])->content;
        eval((string) \preg_replace(
            '/^<\?php\s+(?:\/\*.*?\*\/\s+)?declare\(strict_types=1\);\s*/s',
            '',
            $content
        ));
        $schemas = $namespace . '\\Schemas';
        $container = $schemas::container();
        $name = $schemas::item()->getMember('name');

        // Small acyclic targets (build count <= copy member count) share
        // the canonical member schemas, so the shared instance is reused.
        $this->assertSame(
            $name,
            $container->getMember('item')->getMember('name')
        );
        $this->assertSame(
            $container->getMember('items')->listMember,
            $schemas::other()->getMember('items')->listMember
        );
        $this->assertSame(
            $name,
            $container->getMember('items')->listMember->getMember('name')
        );
        // On a cycle, the cut point depends on the use site, so each site
        // keeps its own copy (not shared).
        $this->assertNotSame(
            $schemas::pet()->getMember('owner'),
            $schemas::owner()->getMember('pet')->getMember('owner')
        );
    }

    public function testKeepsMapMembersOnRecursiveMapCycle(): void
    {
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Container' => [
                    'type' => 'structure',
                    'members' => [
                        'entries' => ['target' => 'smithy.example#ValueMap']
                    ]
                ],
                'smithy.example#ValueMap' => [
                    'type' => 'map',
                    'key' => ['target' => 'smithy.api#String'],
                    'value' => ['target' => 'smithy.example#Value']
                ],
                'smithy.example#Value' => [
                    'type' => 'union',
                    'members' => [
                        'S' => ['target' => 'smithy.api#String'],
                        'M' => ['target' => 'smithy.example#ValueMap']
                    ]
                ]
            ]
        ], JSON_THROW_ON_ERROR));
        $namespace = 'Smithy\\Tests\\Generated\\RecursiveMapSchemas';
        $settings = new CodegenSettings(
            namespace: $namespace,
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $content = (new SchemasGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        ))->generate([
            'smithy.example#Container' => true,
            'smithy.example#ValueMap' => true,
            'smithy.example#Value' => true
        ])->content;
        eval((string) \preg_replace(
            '/^<\?php\s+(?:\/\*.*?\*\/\s+)?declare\(strict_types=1\);\s*/s',
            '',
            $content
        ));
        $schemas = $namespace . '\\Schemas';

        $nested = $schemas::container()->getMember('entries')
            ->getMapValue()
            ->getMember('M');

        $this->assertNotNull($nested->getMapKey());
        $this->assertNotNull($nested->getMapValue());
    }

    /**
     * Guards Major 2: a deep acyclic chain whose eager build count exceeds
     * the container's member count falls back to a one-level copy instead
     * of `self::method()->getMembers()`. That keeps a single operation's
     * cold path from materializing the whole chain (base allocated one
     * level; eager sharing would allocate every level).
     */
    public function testDeepAcyclicTargetFallsBackToOneLevelCopy(): void
    {
        $shapes = [
            'ex#Op' => [
                'type' => 'structure',
                'members' => ['a' => ['target' => 'ex#L0']]
            ]
        ];
        $depth = 6;
        for ($i = 0; $i < $depth; $i++) {
            $next = $i + 1 < $depth
                ? 'ex#L' . ($i + 1)
                : 'smithy.api#String';
            $shapes["ex#L{$i}"] = [
                'type' => 'structure',
                'members' => [
                    'n' => ['target' => $next],
                    's' => ['target' => 'smithy.api#String']
                ]
            ];
        }
        $model = SmithyModel::fromJson(
            \json_encode(['smithy' => '2.0', 'shapes' => $shapes]),
        );
        $namespace = 'Smithy\\Tests\\Generated\\DeepAcyclicFallback';
        $settings = new CodegenSettings(
            namespace: $namespace,
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new SchemasGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );
        $content = $generator->generate(
            \array_fill_keys(\array_keys($shapes), true)
        )->content;

        // `Op` has one member targeting the deep chain `L0`. The chain's
        // build count (6 roots) exceeds `Op`'s member count (1), so the
        // member must be a one-level copy, not a shared `self::l0()` call.
        $this->assertStringContainsString(
            'public static function op(): Schema',
            $content
        );
        eval((string) \preg_replace(
            '/^<\?php\s+(?:\/\*.*?\*\/\s+)?declare\(strict_types=1\);\s*/s',
            '',
            $content
        ));
        $schemas = $namespace . '\\Schemas';

        // The `a` member carries an inline copy of L0's members (one level),
        // and its nested `n` member is a stub (targetClass only, no further
        // inline members) - exactly the base one-level-copy shape.
        $aMember = $schemas::op()->getMember('a');
        $this->assertSame(2, $aMember->getMemberCount());
        $nStub = $aMember->getMember('n');
        $this->assertNotNull($nStub);
        $this->assertSame(0, $nStub->getMemberCount());
        $this->assertTrue($nStub->hasTarget());

        // The copy is NOT the canonical L0 instance (sharing was skipped).
        $this->assertNotSame($schemas::l0(), $aMember);
    }

    /**
     * A shape literally named `collectionMembers` must not collide with the
     * shared list/map member cache. Smithy allows a lower-case first letter,
     * so `Schemas` emits `public static Schema $collectionMembers` for that
     * shape. Holding the cache on a sibling class keeps the two apart; the
     * generated file must load without a redeclare fatal.
     */
    public function testShapeNamedCollectionMembersDoesNotCollide(): void
    {
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Container' => [
                    'type' => 'structure',
                    'members' => [
                        // The colliding shape name.
                        'held' => [
                            'target' => 'smithy.example#collectionMembers'
                        ],
                        // A list so the shared-member cache is actually used.
                        'tags' => ['target' => 'smithy.example#TagList']
                    ]
                ],
                'smithy.example#collectionMembers' => [
                    'type' => 'structure',
                    'members' => [
                        'name' => ['target' => 'smithy.api#String']
                    ]
                ],
                'smithy.example#TagList' => [
                    'type' => 'list',
                    'member' => ['target' => 'smithy.api#String']
                ]
            ]
        ], JSON_THROW_ON_ERROR));
        $namespace = 'Smithy\\Tests\\Generated\\CollectionMembersCollision';
        $settings = new CodegenSettings(
            namespace: $namespace,
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new SchemasGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );
        $content = $generator->generate([
            'smithy.example#Container' => true,
            'smithy.example#collectionMembers' => true,
            'smithy.example#TagList' => true
        ])->content;

        // The shape keeps its own property on Schemas.
        $this->assertStringContainsString(
            'public static Schema $collectionMembers;',
            $content
        );
        // The shared cache lives on the sibling class, not on Schemas.
        $this->assertStringContainsString(
            'final class SchemasMembers',
            $content
        );
        $this->assertStringContainsString(
            'SchemasMembers::$shared',
            $content
        );
        $this->assertStringNotContainsString(
            'private static array $collectionMembers',
            $content
        );

        // The generated file must load without a redeclare fatal.
        eval((string) \preg_replace(
            '/^<\?php\s+(?:\/\*.*?\*\/\s+)?declare\(strict_types=1\);\s*/s',
            '',
            $content
        ));
        $schemas = $namespace . '\\Schemas';
        $this->assertSame(
            ShapeType::Structure->value,
            $schemas::collectionMembers()->type
        );
        // Building the container triggers the `??=` list-member cache on
        // the sibling holder.
        $schemas::container();
        $this->assertNotEmpty(
            ($namespace . '\\SchemasMembers')::$shared
        );
    }
}
