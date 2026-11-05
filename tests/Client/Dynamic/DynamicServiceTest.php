<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Dynamic;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Dynamic\{
    DynamicOperation,
    DynamicService
};
use Smithy\Core\Model\SmithyModel;
use Smithy\Core\Serde\SchemaConverter;
use Smithy\Core\Shape\ShapeType;

#[CoversClass(DynamicService::class)]
final class DynamicServiceTest extends TestCase
{
    public function testResolvesAndCachesModeledOperations(): void
    {
        $model = $this->model();
        $converter = new SchemaConverter($model);
        $service = new DynamicService(
            $model,
            $converter,
            protocolId: 'smithy.protocols#rpcv2Cbor'
        );

        self::assertSame('Sprockets', $service->getName());
        self::assertSame('example#Sprockets', $service->getId());
        self::assertSame('2026-08-20', $service->getVersion());
        self::assertSame(
            ShapeType::Service->value,
            $service->getSchema()->type
        );
        self::assertSame(
            ['Upload', 'HttpCall', 'ResourceCall'],
            \array_map(
                static fn(DynamicOperation $operation): string =>
                    $operation->getName(),
                $service->getOperations()
            )
        );

        $upload = $service->getOperation('Upload');
        self::assertInstanceOf(DynamicOperation::class, $upload);
        self::assertSame($upload, $service->getOperation('Upload'));
        self::assertNull($service->getOperation('Missing'));
    }

    public function testRequiresAnExplicitServiceForAmbiguousModels(): void
    {
        $model = SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => [
                'example#One' => ['type' => 'service'],
                'example#Two' => ['type' => 'service']
            ]
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('multiple service shapes');
        new DynamicService($model, new SchemaConverter($model));
    }

    public function testSelectsAnExplicitService(): void
    {
        $model = SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => [
                'example#One' => ['type' => 'service'],
                'example#Two' => [
                    'type' => 'service',
                    'version' => '2'
                ]
            ]
        ]);
        $service = new DynamicService(
            $model,
            new SchemaConverter($model),
            'example#Two'
        );

        self::assertSame('example#Two', $service->getId());
        self::assertSame('Two', $service->getName());
        self::assertSame('2', $service->getVersion());
    }

    public function testRejectsModelsWithoutServices(): void
    {
        $model = SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => []
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no service shapes');
        new DynamicService($model, new SchemaConverter($model));
    }

    public function testWaiterLookupDoesNotBuildUnrelatedOperations(): void
    {
        $model = SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => [
                'example#Sprockets' => [
                    'type' => 'service',
                    'operations' => [
                        ['target' => 'example#Broken'],
                        ['target' => 'example#Waitable']
                    ]
                ],
                'example#Broken' => [
                    'type' => 'operation',
                    'input' => ['target' => 'example#Missing']
                ],
                'example#Waitable' => [
                    'type' => 'operation',
                    'traits' => [
                        'smithy.waiters#waitable' => [
                            'Ready' => [
                                'acceptors' => [[
                                    'state' => 'success',
                                    'matcher' => ['success' => true]
                                ]]
                            ]
                        ]
                    ]
                ]
            ]
        ]);
        $service = new DynamicService(
            $model,
            new SchemaConverter($model)
        );

        $operation = $service->getWaiterOperation('Ready');

        self::assertInstanceOf(DynamicOperation::class, $operation);
        self::assertSame('Waitable', $operation->getName());
    }

    private function model(): SmithyModel
    {
        return SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => [
                'example#Sprockets' => [
                    'type' => 'service',
                    'version' => '2026-08-20',
                    'operations' => [
                        ['target' => 'example#Upload'],
                        ['target' => 'example#HttpCall']
                    ],
                    'resources' => [
                        ['target' => 'example#Root']
                    ],
                    'errors' => [
                        ['target' => 'example#ServiceFailure']
                    ],
                    'traits' => [
                        'smithy.protocols#rpcv2Cbor' => []
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
                    'read' => ['target' => 'example#ResourceCall']
                ],
                'example#Upload' => [
                    'type' => 'operation',
                    'input' => ['target' => 'example#UploadInput'],
                    'output' => ['target' => 'example#UploadOutput'],
                    'errors' => [
                        ['target' => 'example#InvalidUpload']
                    ]
                ],
                'example#HttpCall' => [
                    'type' => 'operation',
                    'traits' => [
                        'smithy.api#http' => [
                            'method' => 'POST',
                            'uri' => '/',
                            'code' => 200
                        ]
                    ]
                ],
                'example#ResourceCall' => [
                    'type' => 'operation',
                    'input' => ['target' => 'example#Message'],
                    'output' => ['target' => 'example#Message']
                ],
                'example#Message' => [
                    'type' => 'structure',
                    'members' => [
                        'message' => ['target' => 'smithy.api#String']
                    ]
                ],
                'example#UploadInput' => [
                    'type' => 'structure',
                    'members' => [
                        'data' => ['target' => 'example#Stream'],
                        'id' => ['target' => 'smithy.api#String']
                    ]
                ],
                'example#UploadOutput' => [
                    'type' => 'structure',
                    'members' => [
                        'events' => ['target' => 'example#Events']
                    ]
                ],
                'example#Stream' => [
                    'type' => 'blob',
                    'traits' => ['smithy.api#streaming' => []]
                ],
                'example#Events' => [
                    'type' => 'union',
                    'traits' => ['smithy.api#streaming' => []],
                    'members' => [
                        'message' => ['target' => 'smithy.api#String']
                    ]
                ],
                'example#InvalidUpload' => [
                    'type' => 'structure',
                    'traits' => [
                        'smithy.api#error' => 'client',
                        'example.protocols#errorCode' => [
                            'code' => 'BadUpload',
                            'httpResponseCode' => 400
                        ]
                    ]
                ],
                'example#ServiceFailure' => [
                    'type' => 'structure',
                    'traits' => [
                        'smithy.api#error' => 'server'
                    ]
                ]
            ]
        ]);
    }
}
