<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Generator;

use PHPUnit\Framework\TestCase;
use Smithy\Codegen\CodeGenerator;
use Smithy\Codegen\CodegenSettings;
use Smithy\Codegen\Generator\OperationGenerator;
use Smithy\Codegen\Model\SmithyModel;
use Smithy\Codegen\PhpSymbolProvider;
use Smithy\Core\Serde\Schema;

final class OperationGeneratorTest extends TestCase
{
    public function testSafelyExportsModelStringsAsPhpLiterals(): void
    {
        $outputDirectory = \sys_get_temp_dir()
            . \DIRECTORY_SEPARATOR
            . 'smithy-operation-literal-'
            . \bin2hex(\random_bytes(4));
        \mkdir($outputDirectory, 0755, true);
        $markerPath = $outputDirectory
            . \DIRECTORY_SEPARATOR
            . 'injected';
        $injection = '\\file_put_contents(\\base64_decode("'
            . \base64_encode($markerPath)
            . '"), "injected")';
        $scalarPayload = "value\\' . {$injection} . \"\" , //";
        $arrayPayload = "value\\' . {$injection} . \"\",], //";
        $pathLiteral = "/prefix\\'suffix/";
        $pathUri = $pathLiteral . '{label}';
        $namespace = 'Smithy\\OperationLiteral'
            . \bin2hex(\random_bytes(4));

        try {
            $model = SmithyModel::fromJson(\json_encode([
                'smithy' => '2.0',
                'shapes' => [
                    'smithy.example#Unsafe' => [
                        'type' => 'operation',
                        'traits' => [
                            'smithy.api#http' => [
                                'method' => $scalarPayload,
                                'uri' => $scalarPayload,
                                'code' => 200
                            ],
                            'smithy.api#requestCompression' => [
                                'encodings' => [$arrayPayload]
                            ]
                        ]
                    ],
                    'smithy.example#UnsafePath' => [
                        'type' => 'operation',
                        'input' => [
                            'target' => 'smithy.example#UnsafePathInput'
                        ],
                        'traits' => [
                            'smithy.api#http' => [
                                'method' => 'GET',
                                'uri' => $pathUri,
                                'code' => 200
                            ]
                        ]
                    ],
                    'smithy.example#UnsafePathInput' => [
                        'type' => 'structure',
                        'members' => [
                            'label' => [
                                'target' => 'smithy.api#String',
                                'traits' => [
                                    'smithy.api#httpLabel' => []
                                ]
                            ]
                        ]
                    ]
                ]
            ], JSON_THROW_ON_ERROR));
            $settings = new CodegenSettings(
                namespace: $namespace,
                outputDir: $outputDirectory,
                organization: 'flat'
            );
            $generator = new OperationGenerator(
                $model,
                new PhpSymbolProvider($model, $settings),
                $settings
            );
            $file = $generator->generate('smithy.example#Unsafe');
            $file->writeToDisk($outputDirectory);

            require $file->path;
            $class = $namespace . '\\UnsafeOperation';
            $schema = $class::schema();

            self::assertFileDoesNotExist($markerPath);
            self::assertSame(
                [
                    'method' => $scalarPayload,
                    'uri' => $scalarPayload,
                    'code' => 200
                ],
                $schema->getTrait(Schema::T_HTTP)
            );
            self::assertSame(
                [$arrayPayload],
                $schema->getTrait(Schema::T_REQUEST_COMPRESSION)
            );

            $pathFile = $generator->generate(
                'smithy.example#UnsafePath'
            );
            self::assertStringContainsString(
                "'pathSegments' => [["
                    . \var_export($pathLiteral, true)
                    . ', null, false], [null, 0, false]]',
                $pathFile->content
            );
        } finally {
            @\unlink($markerPath);
            @\unlink(
                $outputDirectory
                    . \DIRECTORY_SEPARATOR
                    . 'UnsafeOperation.php'
            );
            @\rmdir($outputDirectory);
        }
    }

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
            "<?php\n\n"
            . "/*\n"
            . " * Copyright Amazon.com, Inc. or its affiliates."
            . " All Rights Reserved.\n"
            . " * SPDX-License-Identifier: Apache-2.0\n"
            . " */\n\n"
            . "declare(strict_types=1);",
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
        $this->assertStringContainsString(
            'implements OperationInterface',
            $file->content
        );
        $this->assertStringContainsString(
            'return ExampleException::class;',
            $file->content
        );        $this->assertStringContainsString(
            'public function getServiceId(): string',
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

    public function testEmitsDedicatedPaginationTraitKey(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__
                . '/../../Client/Pagination/fixtures/pagination-model.json'
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

        $file = $generator->generate(
            'smithy.pagination#ListStrings'
        );

        $this->assertStringContainsString(
            'Schema::T_PAGINATED => [',
            $file->content
        );
        $this->assertStringNotContainsString(
            'PaginationSchemaExtensionProvider',
            $file->content
        );
    }

    public function testEmitsDedicatedWaitableTraitKey(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../Client/Waiter/fixtures/waiter-model.json'
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

        $file = $generator->generate('smithy.waiter#GetThing');

        $this->assertStringContainsString(
            'Schema::T_WAITABLE => [',
            $file->content
        );
        $this->assertStringContainsString(
            "'ThingReady' => [",
            $file->content
        );
        $this->assertStringNotContainsString(
            'WaiterSchemaExtensionProvider',
            $file->content
        );
    }

    public function testPreservesEndpointOperationTraitsById(): void
    {
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#ExampleOperation' => [
                    'type' => 'operation',
                    'traits' => [
                        'smithy.rules#operationContextParams' => [
                            'Account' => ['path' => 'accountId']
                        ],
                        'smithy.rules#staticContextParams' => [
                            'Stage' => ['value' => 'prod']
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
        $generator = new OperationGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate('smithy.example#ExampleOperation');

        $this->assertStringContainsString(
            "'smithy.rules#operationContextParams' => "
                . "['Account' => ['path' => 'accountId']]",
            $file->content
        );
        $this->assertStringContainsString(
            "'smithy.rules#staticContextParams' => "
                . "['Stage' => ['value' => 'prod']]",
            $file->content
        );
    }

    public function testCountsConfiguredAuthSchemeIds(): void
    {
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#ExampleService' => [
                    'type' => 'service',
                    'version' => '2026-11-21',
                    'operations' => [
                        ['target' => 'smithy.example#GetThing']
                    ],
                    'traits' => [
                        'vendor.auth#token' => []
                    ]
                ],
                'smithy.example#GetThing' => [
                    'type' => 'operation'
                ]
            ]
        ], JSON_THROW_ON_ERROR));
        $defaultSettings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $settings = $defaultSettings->copyWith([
            'authSchemeIds' => ['vendor.auth#token']
        ]);

        $defaultFile = (new OperationGenerator(
            $model,
            new PhpSymbolProvider($model, $defaultSettings),
            $defaultSettings
        ))->generate('smithy.example#GetThing');
        $file = (new OperationGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        ))->generate('smithy.example#GetThing');

        $this->assertMatchesRegularExpression(
            "/AUTH_SCHEMES = \[\s*'smithy\.api#noAuth',?\s*\];/",
            $defaultFile->content
        );
        $this->assertMatchesRegularExpression(
            "/AUTH_SCHEMES = \[\s*'vendor\.auth#token',?\s*\];/",
            $file->content
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
            schemaTraitIndices: [
                'vendor.example#metadata' => 28
            ],
            operationSchemaTraits: [
                'smithy.example#GetThing' => [
                    27 => ['name' => 'value'],
                    'vendor.example#metadata' => ['mode' => 'strict']
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
        $this->assertStringContainsString(
            "28 => ['mode' => 'strict']",
            $file->content
        );
        $this->assertStringContainsString(
            "'vendor.example#metadata' => ['mode' => 'strict']",
            $file->content
        );
    }

    public function testUsesPreservedInputMemberIndexes(): void
    {
        $outputDir = \sys_get_temp_dir()
            . \DIRECTORY_SEPARATOR
            . 'smithy-php-operation-order-'
            . \bin2hex(\random_bytes(4));
        \mkdir($outputDir, 0755, true);
        $schemasPath = $outputDir . \DIRECTORY_SEPARATOR . 'Schemas.php';
        \file_put_contents($schemasPath, <<<'PHP'
<?php

final class Schemas
{
    public static function getThingInput(): mixed
    {
        return new Schema(
            id: 'smithy.example#GetThingInput',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.api#String',
                    type: ShapeType::String_,
                    memberName: 'label',
                    memberIndex: 0
                ),
                new Schema(
                    id: 'smithy.example#StreamingBlob',
                    type: ShapeType::Blob,
                    memberName: 'stream',
                    memberIndex: 1
                ),
                new Schema(
                    id: 'smithy.api#Integer',
                    type: ShapeType::Integer,
                    memberName: 'count',
                    memberIndex: 2
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
                    'smithy.example#GetThing' => [
                        'type' => 'operation',
                        'input' => [
                            'target' => 'smithy.example#GetThingInput'
                        ],
                        'traits' => [
                            'smithy.api#http' => [
                                'method' => 'PUT',
                                'uri' => '/{label}',
                                'code' => 200
                            ]
                        ]
                    ],
                    'smithy.example#GetThingInput' => [
                        'type' => 'structure',
                        'members' => [
                            'label' => [
                                'target' => 'smithy.api#String',
                                'traits' => [
                                    'smithy.api#httpLabel' => new \stdClass()
                                ]
                            ],
                            'stream' => [
                                'target' => 'smithy.example#StreamingBlob'
                            ],
                            'count' => [
                                'target' => 'smithy.api#Integer',
                                'traits' => [
                                    'smithy.api#default' => 0
                                ]
                            ]
                        ]
                    ],
                    'smithy.example#StreamingBlob' => [
                        'type' => 'blob',
                        'traits' => [
                            'smithy.api#streaming' => new \stdClass()
                        ]
                    ]
                ]
            ], JSON_THROW_ON_ERROR));
            $settings = new CodegenSettings(
                namespace: 'Smithy\\Example',
                outputDir: $outputDir,
                organization: 'flat'
            );
            $files = (new CodeGenerator($settings, $model))->generate();
            $content = null;
            foreach ($files as $file) {
                if (\basename($file->path) === 'GetThingOperation.php') {
                    $content = $file->content;
                    break;
                }
            }
            $this->assertNotNull($content);

            $this->assertStringContainsString(
                "'pathSegments' => [['/', null, false], "
                    . '[null, 0, false]]',
                $content
            );
            $this->assertStringContainsString(
                'return GetThingInput::schema()'
                    . '->getMembersByIndex()[1];',
                $content
            );
            $this->assertStringContainsString(
                'public function getInputStreamMember(): Schema',
                $content
            );
            $this->assertStringContainsString(
                'public function getOutputStreamMember(): ?Schema',
                $content
            );
        } finally {
            @\unlink($schemasPath);
            @\rmdir($outputDir);
        }
    }

    public function testRejectsPathLabelWithoutInputMember(): void
    {
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#GetThing' => [
                    'type' => 'operation',
                    'input' => ['target' => 'smithy.example#GetThingInput'],
                    'traits' => [
                        'smithy.api#http' => [
                            'method' => 'GET',
                            'uri' => '/{missing}',
                            'code' => 200
                        ]
                    ]
                ],
                'smithy.example#GetThingInput' => [
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
        $generator = new OperationGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Path label {missing}');

        $generator->generate('smithy.example#GetThing');
    }

    public function testGeneratesServiceIdentityForRpcV2Cbor(): void
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
            "public const SERVICE_ID = 'smithy.example#ExampleService';",
            $file->content
        );
        $this->assertStringContainsString(
            'return self::SERVICE_ID;',
            $file->content
        );
        $this->assertStringNotContainsString(
            'public const TARGET',
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

    public function testPreservesConfiguredTargetForCustomProtocol(): void
    {
        $model = $this->createRequestTargetModel([
            'example.protocols#customRpc' => []
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
        $this->assertStringContainsString(
            'return self::TARGET;',
            $file->content
        );
        $this->assertStringContainsString(
            'public function getRequestTarget(): string',
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
