<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Model\SmithyModel;

#[CoversClass(SmithyModel::class)]
final class SmithyModelTest extends TestCase
{
    public function testLoadsModeledPreludeAndMixinShapes(): void
    {
        $model = SmithyModel::fromArray([
            'smithy' => '2.0',
            'metadata' => ['example' => true],
            'shapes' => [
                'example#Base' => [
                    'type' => 'structure',
                    'traits' => [
                        'smithy.api#mixin' => [
                            'localTraits' => ['example#localTrait']
                        ],
                        'example#baseTrait' => 'base',
                        'example#localTrait' => 'local'
                    ],
                    'members' => [
                        'inherited' => [
                            'target' => 'smithy.api#String'
                        ]
                    ]
                ],
                'example#Child' => [
                    'type' => 'structure',
                    'mixins' => [['target' => 'example#Base']],
                    'traits' => ['example#childTrait' => 'child'],
                    'members' => [
                        'local' => [
                            'target' => 'smithy.api#Integer'
                        ]
                    ]
                ]
            ]
        ]);

        self::assertSame(
            ['example' => true],
            $model->getMetadata()
        );
        self::assertSame(
            'string',
            $model->getShapeType('smithy.api#String')
        );
        self::assertSame(
            ['inherited', 'local'],
            \array_keys($model->getMembers('example#Child'))
        );
        self::assertSame(
            [
                'example#baseTrait' => 'base',
                'example#childTrait' => 'child'
            ],
            $model->getTraits('example#Child')
        );
    }

    public function testFromFileOwnsReadFailures(): void
    {
        \set_error_handler(
            static fn(
                int $severity,
                string $message
            ) => throw new \ErrorException($message, 0, $severity)
        );

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Failed to read Smithy model file:');

            SmithyModel::fromFile(__DIR__ . '/missing-model.json');
        } finally {
            \restore_error_handler();
        }
    }

    public function testFromFileLoadsLocalRegularFiles(): void
    {
        $directory = \sys_get_temp_dir()
            . '/smithy-model-' . \bin2hex(\random_bytes(8));
        $path = $directory . '/model.json';
        $cwd = \getcwd();
        self::assertNotFalse($cwd);
        self::assertTrue(\mkdir($directory));
        self::assertNotFalse(\file_put_contents(
            $path,
            '{"smithy":"2.0","shapes":{}}'
        ));

        try {
            self::assertSame(
                [],
                SmithyModel::fromFile($path)->getMetadata()
            );

            self::assertTrue(\chdir($directory));
            self::assertSame(
                [],
                SmithyModel::fromFile('model.json')->getMetadata()
            );
        } finally {
            \chdir($cwd);
            @\unlink($path);
            @\rmdir($directory);
        }
    }

    #[DataProvider('streamWrapperPaths')]
    public function testFromFileRejectsStreamWrappers(string $path): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Smithy model path must reference a local file.'
        );

        SmithyModel::fromFile($path);
    }

    public function testFromFileRejectsNulBytes(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Smithy model path must reference a local file.'
        );

        SmithyModel::fromFile("model\0.json");
    }

    public function testFromFileRejectsDirectories(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to read Smithy model file:');

        SmithyModel::fromFile(__DIR__);
    }

    public function testFromFileTreatsWindowsDrivePathsAsLocal(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to read Smithy model file:');

        SmithyModel::fromFile('Z:\\missing-smithy-model.json');
    }

    #[DataProvider('windowsUncAndDevicePaths')]
    public function testFromFileRejectsWindowsUncAndDevicePaths(
        string $path
    ): void
    {
        if (DIRECTORY_SEPARATOR !== '\\') {
            self::markTestSkipped('Windows-only path behavior.');
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Smithy model path must reference a local file.'
        );

        SmithyModel::fromFile($path);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function streamWrapperPaths(): array
    {
        return [
            'data' => ['data://text/plain,{}'],
            'file' => ['file:///tmp/model.json'],
            'http' => ['http://127.0.0.1/model.json'],
            'mixed case' => ['PhAr://archive/model.json']
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function windowsUncAndDevicePaths(): array
    {
        return [
            'UNC backslashes' => ['\\\\server\\share\\model.json'],
            'UNC forward slashes' => ['//server/share/model.json'],
            'device backslashes' => ['\\\\?\\C:\\model.json'],
            'device forward slashes' => ['//?/C:/model.json']
        ];
    }

    public function testPreservesOriginalModeledShapeId(): void
    {
        $model = SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => [
                'example#GeneratedInput' => [
                    'type' => 'structure',
                    'traits' => [
                        'smithy.synthetic#originalShapeId' =>
                            'example#OriginalInput'
                    ]
                ],
                'example#UnitInput' => [
                    'type' => 'structure',
                    'traits' => [
                        'smithy.synthetic#originalShapeId' =>
                            'smithy.api#Unit'
                    ]
                ]
            ]
        ]);

        self::assertSame(
            'example#OriginalInput',
            $model->getOriginalShapeId('example#GeneratedInput')
        );
        self::assertSame(
            'example#UnitInput',
            $model->getOriginalShapeId('example#UnitInput')
        );
    }

    public function testRejectsInvalidModelsAndMissingShapes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SmithyModel::fromArray(['shapes' => []]);
    }

    public function testExpectShapeRejectsUnknownShape(): void
    {
        $model = SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => []
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $model->expectShape('example#Missing');
    }

    public function testResolvesServiceOperationAndErrorRelationships(): void
    {
        $model = SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => [
                'example#Service' => [
                    'type' => 'service',
                    'operations' => [
                        ['target' => 'example#Direct']
                    ],
                    'resources' => [
                        ['target' => 'example#Root']
                    ],
                    'errors' => [
                        ['target' => 'example#ServiceError']
                    ]
                ],
                'example#Root' => [
                    'type' => 'resource',
                    'resources' => [
                        ['target' => 'example#Nested']
                    ]
                ],
                'example#Nested' => [
                    'type' => 'resource',
                    'read' => ['target' => 'example#ResourceRead']
                ],
                'example#Direct' => [
                    'type' => 'operation',
                    'input' => ['target' => 'example#Input'],
                    'errors' => [
                        ['target' => 'example#OperationError']
                    ]
                ],
                'example#ResourceRead' => ['type' => 'operation'],
                'example#Input' => ['type' => 'structure'],
                'example#ServiceError' => ['type' => 'structure'],
                'example#OperationError' => ['type' => 'structure']
            ]
        ]);

        self::assertSame(['example#Service'], $model->getServiceIds());
        self::assertSame(
            ['example#Direct', 'example#ResourceRead'],
            $model->getServiceOperationIds('example#Service')
        );
        self::assertSame(
            ['example#ServiceError'],
            $model->getServiceErrorIds('example#Service')
        );
        self::assertSame(
            ['example#OperationError'],
            $model->getOperationErrorIds('example#Direct')
        );
        self::assertSame(
            'example#Input',
            $model->getOperationInputId('example#Direct')
        );
        self::assertSame(
            'smithy.api#Unit',
            $model->getOperationOutputId('example#Direct')
        );
        self::assertSame(
            'ResourceRead',
            SmithyModel::getShapeName('example#ResourceRead')
        );
    }

    public function testResolvesEffectiveAuthSchemes(): void
    {
        $model = SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => [
                'example#AAuth' => [
                    'type' => 'structure',
                    'traits' => [
                        'smithy.api#authDefinition' => []
                    ]
                ],
                'example#ZAuth' => [
                    'type' => 'structure',
                    'traits' => [
                        'smithy.api#authDefinition' => []
                    ]
                ],
                'example#Discovered' => [
                    'type' => 'service',
                    'operations' => [
                        ['target' => 'example#Inherited'],
                        ['target' => 'example#Override'],
                        ['target' => 'example#Optional'],
                        ['target' => 'example#Anonymous']
                    ],
                    'traits' => [
                        'example#ZAuth' => [],
                        'example#AAuth' => []
                    ]
                ],
                'example#Inherited' => ['type' => 'operation'],
                'example#Override' => [
                    'type' => 'operation',
                    'traits' => [
                        'smithy.api#auth' => ['example#ZAuth']
                    ]
                ],
                'example#Optional' => [
                    'type' => 'operation',
                    'traits' => [
                        'smithy.api#optionalAuth' => []
                    ]
                ],
                'example#Anonymous' => [
                    'type' => 'operation',
                    'traits' => [
                        'smithy.api#auth' => []
                    ]
                ],
                'example#Ordered' => [
                    'type' => 'service',
                    'operations' => [
                        ['target' => 'example#OrderedCall']
                    ],
                    'traits' => [
                        'example#AAuth' => [],
                        'example#ZAuth' => [],
                        'smithy.api#auth' => [
                            'example#ZAuth',
                            'example#AAuth'
                        ]
                    ]
                ],
                'example#OrderedCall' => ['type' => 'operation']
            ]
        ]);

        self::assertSame(
            ['example#AAuth', 'example#ZAuth'],
            $model->getEffectiveAuthSchemeIds(
                'example#Discovered',
                'example#Inherited'
            )
        );
        self::assertSame(
            ['example#ZAuth'],
            $model->getEffectiveAuthSchemeIds(
                'example#Discovered',
                'example#Override'
            )
        );
        self::assertSame(
            [
                'example#AAuth',
                'example#ZAuth',
                'smithy.api#noAuth'
            ],
            $model->getEffectiveAuthSchemeIds(
                'example#Discovered',
                'example#Optional'
            )
        );
        self::assertSame(
            ['smithy.api#noAuth'],
            $model->getEffectiveAuthSchemeIds(
                'example#Discovered',
                'example#Anonymous'
            )
        );
        self::assertSame(
            ['example#ZAuth', 'example#AAuth'],
            $model->getEffectiveAuthSchemeIds(
                'example#Ordered',
                'example#OrderedCall'
            )
        );
    }
}
