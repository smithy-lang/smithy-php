<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Model;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Codegen\Model\SmithyModel;
use Smithy\Codegen\ShapeCategory;

final class SmithyModelTest extends TestCase
{
    public function testRoundTripsDecodedModel(): void
    {
        $data = [
            'smithy' => '2.0',
            'metadata' => ['example' => true],
            'shapes' => [
                'smithy.example#Value' => [
                    'type' => 'string'
                ]
            ]
        ];

        $this->assertSame(
            $data,
            SmithyModel::fromArray($data)->toArray()
        );
    }

    public function testFromFileLoadsExistingFixture(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/person-model.json'
        );

        $this->assertNotEmpty($model->getShapes());
        $this->assertNotNull($model->getShape('smithy.example#Human'));
    }

    public function testFromFileRaisesWhenPathMissing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Smithy model file not found/');

        SmithyModel::fromFile('/tmp/does-not-exist-' . uniqid() . '.json');
    }

    public function testFromJsonRaisesWhenSmithyVersionMissing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches(
            '/missing "smithy" version field/'
        );

        SmithyModel::fromJson('{"shapes": {}}');
    }

    public function testFromJsonRaisesOnMalformedJson(): void
    {
        $this->expectException(\JsonException::class);

        SmithyModel::fromJson('{not valid json');
    }

    public function testFromJsonHandlesEmptyShapesAndMetadata(): void
    {
        $model = SmithyModel::fromJson('{"smithy": "2.0"}');

        $this->assertSame([], $model->getShapes());
        $this->assertSame([], $model->getMetadata());
    }

    #[DataProvider('invalidIdentifierModels')]
    public function testFromJsonRejectsInvalidIdentifiers(array $model): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid Smithy model');

        SmithyModel::fromJson(\json_encode($model, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function invalidIdentifierModels(): array
    {
        return [
            'traversal shape ID' => [[
                'smithy' => '2.0',
                'shapes' => [
                    'example#../../Outside' => [
                        'type' => 'structure',
                        'members' => []
                    ]
                ]
            ]],
            'Windows traversal shape ID' => [[
                'smithy' => '2.0',
                'shapes' => [
                    'example#..\\..\\Outside' => [
                        'type' => 'structure',
                        'members' => []
                    ]
                ]
            ]],
            'source injection shape ID' => [[
                'smithy' => '2.0',
                'shapes' => [
                    "example#Shape\nfinal class Injected" => [
                        'type' => 'structure',
                        'members' => []
                    ]
                ]
            ]],
            'invalid member name' => [[
                'smithy' => '2.0',
                'shapes' => [
                    'example#Input' => [
                        'type' => 'structure',
                        'members' => [
                            '../value' => ['target' => 'smithy.api#String']
                        ]
                    ]
                ]
            ]],
            'invalid target' => [[
                'smithy' => '2.0',
                'shapes' => [
                    'example#Input' => [
                        'type' => 'structure',
                        'members' => [
                            'value' => ['target' => '../String']
                        ]
                    ]
                ]
            ]],
            'invalid trait ID' => [[
                'smithy' => '2.0',
                'shapes' => [
                    'example#Input' => [
                        'type' => 'structure',
                        'members' => [],
                        'traits' => [
                            '../sensitive' => []
                        ]
                    ]
                ]
            ]],
            'members on unsupported mixin shape' => [[
                'smithy' => '2.0',
                'shapes' => [
                    'example#InvalidMixin' => [
                        'type' => 'string',
                        'members' => [
                            'bad-name' => [
                                'target' => 'smithy.api#String'
                            ]
                        ]
                    ],
                    'example#Input' => [
                        'type' => 'structure',
                        'mixins' => [
                            ['target' => 'example#InvalidMixin']
                        ],
                        'members' => []
                    ]
                ]
            ]],
            'invalid target on enum mixin member' => [[
                'smithy' => '2.0',
                'shapes' => [
                    'example#InvalidMixin' => [
                        'type' => 'enum',
                        'members' => [
                            'VALUE' => [
                                'target' => "smithy.api#String'; phpinfo(); //"
                            ]
                        ]
                    ],
                    'example#Input' => [
                        'type' => 'structure',
                        'mixins' => [
                            ['target' => 'example#InvalidMixin']
                        ],
                        'members' => []
                    ]
                ]
            ]],
            'invalid resource property name' => [[
                'smithy' => '2.0',
                'shapes' => [
                    'example#Resource' => [
                        'type' => 'resource',
                        'properties' => [
                            '../name' => [
                                'target' => 'smithy.api#String'
                            ]
                        ]
                    ]
                ]
            ]],
            'invalid resource property target' => [[
                'smithy' => '2.0',
                'shapes' => [
                    'example#Resource' => [
                        'type' => 'resource',
                        'properties' => [
                            'name' => [
                                'target' => '../String'
                            ]
                        ]
                    ]
                ]
            ]]
        ];
    }

    public function testMetadataIsExposedFromParsedModel(): void
    {
        $model = SmithyModel::fromJson(json_encode([
            'smithy' => '2.0',
            'metadata' => ['authors' => ['alice']],
            'shapes' => []
        ]));

        $this->assertSame(['authors' => ['alice']], $model->getMetadata());
    }

    public function testGetShapeReturnsNullForMissingId(): void
    {
        $model = SmithyModel::fromJson('{"smithy": "2.0"}');
        $this->assertNull($model->getShape('smithy.example#Nope'));
    }

    public function testGetShapeReturnsFullDefinitionForKnownId(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/person-model.json'
        );
        $status = $model->getShape('smithy.example#Status');

        $this->assertIsArray($status);
        $this->assertSame('enum', $status['type']);
    }

    #[DataProvider('preludeShapeVariants')]
    public function testGetShapeTypeResolvesPreludeTypesEvenWhenAbsentFromShapes(
        string $shapeId,
        string $expectedType
    ): void
    {
        $model = SmithyModel::fromJson('{"smithy": "2.0"}');

        $this->assertSame($expectedType, $model->getShapeType($shapeId));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function preludeShapeVariants(): array
    {
        return [
            'string prelude' => ['smithy.api#String', 'string'],
            'integer prelude' => ['smithy.api#Integer', 'integer'],
            'boolean prelude' => ['smithy.api#Boolean', 'boolean'],
            'timestamp prelude' => ['smithy.api#Timestamp', 'timestamp'],
            'blob prelude' => ['smithy.api#Blob', 'blob'],
            'document prelude' => ['smithy.api#Document', 'document'],
            'unit resolves to structure' => ['smithy.api#Unit', 'structure'],
            'primitive boolean' => ['smithy.api#PrimitiveBoolean', 'boolean'],
            'primitive integer' => ['smithy.api#PrimitiveInteger', 'integer'],
            'bigDecimal' => ['smithy.api#BigDecimal', 'bigDecimal']
        ];
    }

    public function testGetShapeTypeReturnsNullForUnknownId(): void
    {
        $model = SmithyModel::fromJson('{"smithy": "2.0"}');
        $this->assertNull($model->getShapeType('smithy.example#Nothing'));
    }

    public function testGetTraitsAndHasTraitOnFixtureShape(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/person-model.json'
        );

        $traits = $model->getTraits('smithy.example#OrderId');
        $this->assertArrayHasKey('smithy.api#length', $traits);
        $this->assertTrue(
            $model->hasTrait('smithy.example#OrderId', 'smithy.api#length')
        );
        $this->assertFalse(
            $model->hasTrait('smithy.example#OrderId', 'smithy.api#error')
        );
    }

    public function testGetTraitReturnsRawValueOrNull(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/person-model.json'
        );

        $lengthTrait = $model->getTrait(
            'smithy.example#OrderId',
            'smithy.api#length'
        );
        $this->assertSame(['min' => 1, 'max' => 128], $lengthTrait);
        $this->assertNull(
            $model->getTrait('smithy.example#OrderId', 'smithy.api#error')
        );
    }

    public function testGetMembersReturnsDirectMembersForNonMixinShape(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/person-model.json'
        );
        $members = $model->getMembers('smithy.example#Human');

        $this->assertSame(
            ['name', 'age', 'address', 'children', 'parents'],
            array_keys($members)
        );
        $this->assertSame('smithy.api#String', $members['name']['target']);
    }

    public function testGetMembersMergesMixinMembersFirstThenLocalOverrides(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/person-model.json'
        );

        // QueryEvent applies Event as a mixin. Event carries itemId +
        // timestamp.
        // QueryEvent has no locals of its own, so the merged view returns
        // exactly
        // the mixin's members in their original order.
        $members = $model->getMembers('smithy.example#QueryEvent');

        $this->assertSame(['itemId', 'timestamp'], array_keys($members));
    }

    public function testGetMembersReturnsEmptyArrayForUnknownShape(): void
    {
        $model = SmithyModel::fromJson('{"smithy": "2.0"}');
        $this->assertSame([], $model->getMembers('smithy.example#Ghost'));
    }

    public function testGetMemberTargetReturnsTargetOrNull(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/person-model.json'
        );

        $this->assertSame(
            'smithy.api#String',
            $model->getMemberTarget('smithy.example#Human', 'name')
        );
        $this->assertNull(
            $model->getMemberTarget('smithy.example#Human', 'nope')
        );
    }

    public function testIsMemberRequiredReflectsRequiredTraitWithoutDefault(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/person-model.json'
        );

        // Human.name has @required and no @default -> required.
        $this->assertTrue(
            $model->isMemberRequired('smithy.example#Human', 'name')
        );
        // Human.children has neither trait -> not required.
        $this->assertFalse(
            $model->isMemberRequired('smithy.example#Human', 'children')
        );
        // Nonexistent member is not required.
        $this->assertFalse(
            $model->isMemberRequired('smithy.example#Human', 'nope')
        );
    }

    public function testIsMemberRequiredReturnsFalseWhenRequiredAndDefaultCoexist(): void
    {
        // Per Smithy spec, `@default` clears `@required` for defaulting
        // purposes.
        $json = json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Struct' => [
                    'type' => 'structure',
                    'members' => [
                        'x' => [
                            'target' => 'smithy.api#Integer',
                            'traits' => [
                                'smithy.api#required' => new \stdClass(),
                                'smithy.api#default' => 0
                            ]
                        ]
                    ]
                ]
            ]
        ]);
        $model = SmithyModel::fromJson($json);

        $this->assertFalse(
            $model->isMemberRequired('smithy.example#Struct', 'x')
        );
    }

    public function testIsMemberRequiredTreatsNullTraitAsAbsent(): void
    {
        $model = SmithyModel::fromJson(\json_encode(
            [
                'smithy' => '2.0',
                'shapes' => [
                    'smithy.example#Struct' => [
                        'type' => 'structure',
                        'members' => [
                            'x' => [
                                'target' => 'smithy.api#Integer',
                                'traits' => [
                                    'smithy.api#required' => null
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            JSON_THROW_ON_ERROR
        ));

        $this->assertFalse(
            $model->isMemberRequired('smithy.example#Struct', 'x')
        );
    }

    public function testPaginationTreatsNullTraitAsAbsent(): void
    {
        $model = SmithyModel::fromJson(\json_encode(
            [
                'smithy' => '2.0',
                'shapes' => [
                    'smithy.example#ListThings' => [
                        'type' => 'operation',
                        'traits' => [
                            'smithy.api#paginated' => null
                        ]
                    ]
                ]
            ],
            JSON_THROW_ON_ERROR
        ));

        $this->assertNull(
            $model->getEffectivePaginationTrait(
                'smithy.example#ListThings'
            )
        );
    }

    public function testIsMemberDefaultedRecognizesRenderableDefaults(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/effective-defaults-model.json'
        );

        foreach ([
            'defaultString',
            'defaultInteger',
            'defaultFloat',
            'defaultTimestamp',
            'defaultBlob',
            'defaultList',
            'defaultMap',
            'defaultEnum',
            'defaultIntEnum',
            'defaultBigInteger',
            'defaultBigDecimal'
        ] as $memberName) {
            $this->assertTrue(
                $model->isMemberDefaulted(
                    'smithy.example#EffectiveDefaults',
                    $memberName
                )
            );
        }

        $this->assertFalse(
            $model->isMemberDefaulted(
                'smithy.example#EffectiveDefaults',
                'clientOptional'
            )
        );
        $this->assertFalse(
            $model->isMemberDefaulted(
                'smithy.example#EffectiveDefaults',
                'nope'
            )
        );
    }

    public function testClientOptionalMembersIgnoreRequiredAndDefaultTraits(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/effective-defaults-model.json'
        );

        foreach (['topLevelDefault', 'requiredMember'] as $memberName) {
            $this->assertTrue(
                $model->isMemberClientOptional(
                    'smithy.example#InputDefaults',
                    $memberName
                )
            );
            $this->assertFalse(
                $model->isMemberRequired(
                    'smithy.example#InputDefaults',
                    $memberName
                )
            );
            $this->assertFalse(
                $model->isMemberDefaulted(
                    'smithy.example#InputDefaults',
                    $memberName
                )
            );
        }

        $this->assertTrue(
            $model->isMemberClientOptional(
                'smithy.example#EffectiveDefaults',
                'clientOptional'
            )
        );
        $this->assertFalse(
            $model->isMemberDefaulted(
                'smithy.example#EffectiveDefaults',
                'clientOptional'
            )
        );
    }

    public function testIsMemberDefaultedRejectsNullDefault(): void
    {
        // A `@default(null)` trait clears any inherited default. Codegen should
        // not
        // treat the member as defaulted for parameter-emission purposes.
        $json = json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Struct' => [
                    'type' => 'structure',
                    'members' => [
                        'x' => [
                            'target' => 'smithy.api#Integer',
                            'traits' => [
                                'smithy.api#default' => null
                            ]
                        ]
                    ]
                ]
            ]
        ]);
        $model = SmithyModel::fromJson($json);

        $this->assertFalse(
            $model->isMemberDefaulted('smithy.example#Struct', 'x')
        );
    }

    public function testGetMemberDefaultReturnsRawTraitValue(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/defaults-model.json'
        );

        $this->assertSame(
            0,
            $model->getMemberDefault('smithy.example#Settings', 'count')
        );
        $this->assertSame(
            'untitled',
            $model->getMemberDefault('smithy.example#Settings', 'label')
        );
        $this->assertSame(
            true,
            $model->getMemberDefault('smithy.example#Settings', 'enabled')
        );
        $this->assertSame(
            [],
            $model->getMemberDefault('smithy.example#Settings', 'tags')
        );
        $this->assertNull(
            $model->getMemberDefault('smithy.example#Settings', 'description')
        );
    }

    public function testCategorizeShapeReturnsErrorForShapeWithErrorTrait(): void
    {
        $json = json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#BadRequest' => [
                    'type' => 'structure',
                    'members' => [],
                    'traits' => ['smithy.api#error' => 'client']
                ]
            ]
        ]);
        $model = SmithyModel::fromJson($json);

        $this->assertSame(
            ShapeCategory::Error,
            $model->categorizeShape('smithy.example#BadRequest')
        );
    }

    public function testCategorizeShapeReturnsInputForOperationInput(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/defaults-model.json'
        );

        // Settings is referenced only as UpdateSettings's input -> Input
        // category.
        $this->assertSame(
            ShapeCategory::Input,
            $model->categorizeShape('smithy.example#Settings')
        );
    }

    public function testCategorizeShapeReturnsShapeForNonOperationNonErrorTypes(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/person-model.json'
        );

        $this->assertSame(
            ShapeCategory::Shape,
            $model->categorizeShape('smithy.example#Human')
        );
        $this->assertSame(
            ShapeCategory::Shape,
            $model->categorizeShape('smithy.example#Status')
        );
        $this->assertSame(
            ShapeCategory::Shape,
            $model->categorizeShape('smithy.example#Payload')
        );
    }

    public function testCategorizeShapeReturnsOutputForOperationOutput(): void
    {
        $json = json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#GetThing' => [
                    'type' => 'operation',
                    'input' => ['target' => 'smithy.api#Unit'],
                    'output' => ['target' => 'smithy.example#GetThingResult']
                ],
                'smithy.example#GetThingResult' => [
                    'type' => 'structure',
                    'members' => []
                ]
            ]
        ]);
        $model = SmithyModel::fromJson($json);

        $this->assertSame(
            ShapeCategory::Output,
            $model->categorizeShape('smithy.example#GetThingResult')
        );
    }

    public function testIsUsedAsInputWalksIntoNestedStructuresListsMapsAndUnions(): void
    {
        // Model: op takes RootInput, which nests via each of the four container
        // types.
        $json = json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Op' => [
                    'type' => 'operation',
                    'input' => ['target' => 'smithy.example#RootInput']
                ],
                'smithy.example#RootInput' => [
                    'type' => 'structure',
                    'members' => [
                        'nested' => ['target' => 'smithy.example#NestedStruct'],
                        'items' => ['target' => 'smithy.example#StructList'],
                        'map' => ['target' => 'smithy.example#StructMap'],
                        'union' => ['target' => 'smithy.example#PickOne']
                    ]
                ],
                'smithy.example#NestedStruct' => [
                    'type' => 'structure',
                    'members' => ['x' => ['target' => 'smithy.api#Integer']]
                ],
                'smithy.example#StructList' => [
                    'type' => 'list',
                    'member' => ['target' => 'smithy.example#ListItem']
                ],
                'smithy.example#ListItem' => [
                    'type' => 'structure',
                    'members' => []
                ],
                'smithy.example#StructMap' => [
                    'type' => 'map',
                    'key' => ['target' => 'smithy.api#String'],
                    'value' => ['target' => 'smithy.example#MapValue']
                ],
                'smithy.example#MapValue' => [
                    'type' => 'structure',
                    'members' => []
                ],
                'smithy.example#PickOne' => [
                    'type' => 'union',
                    'members' => ['a' => ['target' => 'smithy.api#String']]
                ]
            ]
        ]);
        $model = SmithyModel::fromJson($json);

        // Direct operation input.
        $this->assertTrue($model->isUsedAsInput('smithy.example#RootInput'));
        // Reachable through struct member.
        $this->assertTrue($model->isUsedAsInput('smithy.example#NestedStruct'));
        // Reachable through list<Struct>.
        $this->assertTrue($model->isUsedAsInput('smithy.example#ListItem'));
        // Reachable through map<_, Struct>.
        $this->assertTrue($model->isUsedAsInput('smithy.example#MapValue'));
        // Union itself is reachable.
        $this->assertTrue($model->isUsedAsInput('smithy.example#PickOne'));
    }

    public function testIsUsedAsInputReturnsFalseForShapesUnreachableFromInputs(): void
    {
        $json = json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#OutOnly' => [
                    'type' => 'operation',
                    'input' => ['target' => 'smithy.api#Unit'],
                    'output' => ['target' => 'smithy.example#SomeOutput']
                ],
                'smithy.example#SomeOutput' => [
                    'type' => 'structure',
                    'members' => []
                ],
                'smithy.example#Orphan' => [
                    'type' => 'structure',
                    'members' => []
                ]
            ]
        ]);
        $model = SmithyModel::fromJson($json);

        $this->assertFalse($model->isUsedAsInput('smithy.example#SomeOutput'));
        $this->assertFalse($model->isUsedAsInput('smithy.example#Orphan'));
    }

    public function testFilteredShapeGettersPartitionByType(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/person-model.json'
        );

        // Person model has structures, including carrier shapes for operations.
        $structureIds = array_keys($model->getStructureShapes());
        $this->assertContains('smithy.example#Human', $structureIds);
        $this->assertContains('smithy.example#Address', $structureIds);
        // Enum shapes.
        $enumIds = array_keys($model->getEnumShapes());
        $this->assertContains('smithy.example#Status', $enumIds);
        $this->assertContains('smithy.example#Priority', $enumIds);
        // Union shapes.
        $this->assertSame(
            ['smithy.example#Payload'],
            array_keys($model->getUnionShapes())
        );
        $this->assertSame(
            ['smithy.example#ExampleService'],
            array_keys($model->getServiceShapes())
        );
        $this->assertSame(
            [
                'smithy.example#HttpChecksumRequired',
                'smithy.example#PutWithContentEncoding',
                'smithy.example#GreetingWithErrors'
            ],
            array_keys($model->getOperationShapes())
        );
    }

    public function testServiceAndOperationGettersFindDefaultsFixture(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/defaults-model.json'
        );

        $this->assertSame(
            ['smithy.example#DefaultsService'],
            array_keys($model->getServiceShapes())
        );
        $this->assertSame(
            ['smithy.example#UpdateSettings'],
            array_keys($model->getOperationShapes())
        );
    }

    public function testGetMembersOrderedPartitionsRequiredDefaultedOptionalWhenFresh(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/effective-defaults-model.json'
        );

        $ordered = array_keys(
            $model->getMembersOrdered('smithy.example#EffectiveDefaults')
        );

        $this->assertSame('requiredString', $ordered[0]);

        $defaultedSlice = array_slice($ordered, 1, 11);
        sort($defaultedSlice);
        $this->assertSame(
            [
                'defaultBigDecimal',
                'defaultBigInteger',
                'defaultBlob',
                'defaultEnum',
                'defaultFloat',
                'defaultIntEnum',
                'defaultInteger',
                'defaultList',
                'defaultMap',
                'defaultString',
                'defaultTimestamp'
            ],
            $defaultedSlice
        );

        $this->assertSame('clientOptional', $ordered[array_key_last($ordered)]);
    }

    public function testGetMembersOrderedHonorsPreservedOrderThenAppendsNew(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/defaults-model.json'
        );

        // Preserve a specific historical order for four members. The remaining
        // seven must land at the tail in fresh order.
        $preserved = ['label', 'name', 'description', 'ratio'];
        $ordered = array_keys(
            $model->getMembersOrdered('smithy.example#Settings', $preserved)
        );

        // Preserved leaders keep their slots verbatim.
        $this->assertSame(
            ['label', 'name', 'description', 'ratio'],
            array_slice($ordered, 0, 4)
        );

        // Newcomers appear afterwards. Each preserved-list member is present
        // exactly once overall.
        $rest = array_slice($ordered, 4);
        $this->assertContains('count', $rest);
        $this->assertContains('enabled', $rest);
        $this->assertContains('tags', $rest);
        $this->assertContains('marker', $rest);
        $this->assertCount(count($ordered), array_unique($ordered));
    }

    public function testGetMembersOrderedSilentlyDropsStalePreservedEntries(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/defaults-model.json'
        );

        // `oldField` was in a prior sidecar but is no longer in the model.
        // The introspector must skip it, not error.
        $ordered = array_keys($model->getMembersOrdered(
            'smithy.example#Settings',
            ['name', 'oldField', 'label']
        ));

        $this->assertNotContains('oldField', $ordered);
        $this->assertContains('name', $ordered);
        $this->assertContains('label', $ordered);
    }

    #[DataProvider('shapeNameSplitVariants')]
    public function testGetShapeNameExtractsPortionAfterHash(
        string $shapeId,
        string $expected
    ): void
    {
        $this->assertSame($expected, SmithyModel::getShapeName($shapeId));
    }

    #[DataProvider('shapeNamespaceSplitVariants')]
    public function testGetShapeNamespaceExtractsPortionBeforeHash(
        string $shapeId,
        string $expected
    ): void
    {
        $this->assertSame($expected, SmithyModel::getShapeNamespace($shapeId));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function shapeNameSplitVariants(): array
    {
        return [
            'namespaced shape' => ['smithy.example#Human', 'Human'],
            'aws prelude' => ['aws.protocols#restJson1', 'restJson1'],
            'no hash returns whole string' => ['NoHashHere', 'NoHashHere']
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function shapeNamespaceSplitVariants(): array
    {
        return [
            'namespaced shape' => ['smithy.example#Human', 'smithy.example'],
            'aws prelude' => ['aws.protocols#restJson1', 'aws.protocols'],
            'no hash returns whole string' => ['NoHashHere', 'NoHashHere']
        ];
    }
}
