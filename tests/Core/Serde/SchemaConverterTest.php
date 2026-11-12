<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Core\Serde;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Codec\Json\JsonCodec;
use Smithy\Core\Model\SmithyModel;
use Smithy\Core\Serde\{
    DynamicTraitValue,
    Schema,
    SchemaConverter,
    SchemaReference
};
use Smithy\Core\Shape\Document\StructDocument;
use Smithy\Core\Shape\ShapeType;
use Smithy\Http\Message\StringRequestStream;

#[CoversClass(SchemaConverter::class)]
final class SchemaConverterTest extends TestCase
{
    public function testConvertsEveryScalarShapeType(): void
    {
        $types = [
            'boolean' => ShapeType::Boolean,
            'byte' => ShapeType::Byte,
            'short' => ShapeType::Short,
            'integer' => ShapeType::Integer,
            'long' => ShapeType::Long,
            'float' => ShapeType::Float_,
            'double' => ShapeType::Double,
            'bigInteger' => ShapeType::BigInteger,
            'bigDecimal' => ShapeType::BigDecimal,
            'string' => ShapeType::String_,
            'blob' => ShapeType::Blob,
            'timestamp' => ShapeType::Timestamp,
            'document' => ShapeType::Document
        ];
        $shapes = [];
        foreach ($types as $type => $shapeType) {
            $shapes["example#{$type}"] = ['type' => $type];
        }

        $converter = new SchemaConverter(
            SmithyModel::fromArray([
                'smithy' => '2.0',
                'shapes' => $shapes
            ])
        );

        foreach ($types as $type => $shapeType) {
            self::assertSame(
                $shapeType->value,
                $converter->getSchema("example#{$type}")->type
            );
        }
    }

    public function testConvertsAndCachesRuntimeShapeGraphs(): void
    {
        $converter = new SchemaConverter($this->model());

        $node = $converter->getSchema('example#Node');

        self::assertSame(
            $node,
            $converter->getSchema('example#Node')
        );
        self::assertSame(
            ShapeType::Structure->value,
            $node->type
        );
        self::assertSame(
            ['id', 'name', 'next', 'children'],
            \array_keys($node->getMembers())
        );

        $name = $node->getMember('name');
        self::assertNotNull($name);
        self::assertSame(ShapeType::String_->value, $name->type);
        self::assertTrue($name->hasTrait(Schema::T_SENSITIVE));
        self::assertFalse(
            $name->hasDirectTrait(Schema::T_SENSITIVE)
        );
        self::assertSame(
            'displayName',
            $name->getDirectTrait(Schema::T_JSON_NAME)
        );
        self::assertSame(
            $converter->getSchema('example#Name'),
            $name->resolveTarget()
        );

        $custom = $name->getTraitById('example#customTrait');
        self::assertInstanceOf(DynamicTraitValue::class, $custom);
        self::assertSame('member', $custom->value);
        self::assertSame(
            $custom,
            $name->getDirectTraitById('example#customTrait')
        );
        $pattern = $name->getTraitById('smithy.api#pattern');
        self::assertInstanceOf(DynamicTraitValue::class, $pattern);
        self::assertSame('^[a-z]+$', $pattern->value);
        self::assertNull(
            $name->getDirectTraitById('smithy.api#pattern')
        );

        $next = $node->getMember('next');
        self::assertNotNull($next);
        self::assertInstanceOf(SchemaReference::class, $next->target);
        self::assertSame($node, $next->resolveTarget());

        $children = $node->getMember('children');
        self::assertNotNull($children);
        $list = $children->resolveTarget();
        self::assertSame(ShapeType::List_->value, $list->type);
        self::assertSame(
            $node,
            $list->getListMember()?->resolveTarget()
        );
    }

    public function testConvertsCollectionsEnumsOperationsAndServices(): void
    {
        $converter = new SchemaConverter(
            $this->model(),
            errorDiscriminatorTraits: [
                'example.protocols#errorCode' => 'code'
            ]
        );

        $map = $converter->getSchema('example#NodeMap');
        self::assertSame(ShapeType::Map->value, $map->type);
        self::assertSame(
            ShapeType::String_->value,
            $map->getMapKey()?->type
        );
        self::assertSame(
            'example#Node',
            $map->getMapValue()?->resolveTarget()->id
        );

        $union = $converter->getSchema('example#Choice');
        self::assertSame(ShapeType::Union->value, $union->type);
        self::assertSame(['node', 'text'], \array_keys($union->getMembers()));

        self::assertSame(
            ['red' => 'r', 'blue' => 'b'],
            $converter->getSchema('example#Color')->enumValues
        );
        self::assertSame(
            ['ok' => 200, 'failure' => 500],
            $converter->getSchema('example#Code')->enumValues
        );

        $operation = $converter->getSchema('example#GetNode');
        self::assertSame(ShapeType::Operation->value, $operation->type);
        self::assertSame(
            [
                'example#ServiceProblem' => 'example#ServiceProblem',
                'ServiceProblem' => 'example#ServiceProblem',
                'example#Problem' => 'example#Problem',
                'Problem' => 'example#Problem',
                'BadNode' => 'example#Problem'
            ],
            $operation->getTrait(Schema::T_ERRORS)
        );
        self::assertSame(
            ['team' => 'sprockets'],
            $operation->getTraitById('example#tagging')->value
        );
        self::assertSame(
            'platform',
            $operation->getTraitById('example#owner')->value
        );

        $service = $converter->getSchema('example#Service');
        self::assertSame(ShapeType::Service->value, $service->type);
    }

    public function testOperationErrorsIncludeAllContainingServiceErrors(): void
    {
        $converter = new SchemaConverter(
            SmithyModel::fromArray([
                'smithy' => '2.0',
                'shapes' => [
                    'example#FirstService' => [
                        'type' => 'service',
                        'operations' => [
                            ['target' => 'example#SharedOperation']
                        ],
                        'errors' => [
                            ['target' => 'example#FirstError']
                        ]
                    ],
                    'example#SecondService' => [
                        'type' => 'service',
                        'operations' => [
                            ['target' => 'example#SharedOperation']
                        ],
                        'errors' => [
                            ['target' => 'example#SecondError']
                        ]
                    ],
                    'example#SharedOperation' => [
                        'type' => 'operation',
                        'errors' => [
                            ['target' => 'example#OperationError']
                        ]
                    ],
                    'example#FirstError' => [
                        'type' => 'structure',
                        'traits' => ['smithy.api#error' => 'client']
                    ],
                    'example#SecondError' => [
                        'type' => 'structure',
                        'traits' => ['smithy.api#error' => 'server']
                    ],
                    'example#OperationError' => [
                        'type' => 'structure',
                        'traits' => ['smithy.api#error' => 'client']
                    ]
                ]
            ])
        );

        self::assertSame(
            [
                'example#FirstError' => 'example#FirstError',
                'FirstError' => 'example#FirstError',
                'example#SecondError' => 'example#SecondError',
                'SecondError' => 'example#SecondError',
                'example#OperationError' => 'example#OperationError',
                'OperationError' => 'example#OperationError'
            ],
            $converter->getSchema('example#SharedOperation')
                ->getTrait(Schema::T_ERRORS)
        );
    }

    public function testCopiesConcreteTargetMetadataOntoMemberSchemas(): void
    {
        $converter = new SchemaConverter(
            SmithyModel::fromArray([
                'smithy' => '2.0',
                'shapes' => [
                    'example#Container' => [
                        'type' => 'structure',
                        'members' => [
                            'nested' => ['target' => 'example#Nested'],
                            'choice' => ['target' => 'example#Choice'],
                            'items' => ['target' => 'example#Items'],
                            'attributes' => [
                                'target' => 'example#Attributes'
                            ],
                            'color' => ['target' => 'example#Color']
                        ]
                    ],
                    'example#Nested' => [
                        'type' => 'structure',
                        'members' => [
                            'value' => ['target' => 'smithy.api#String']
                        ]
                    ],
                    'example#Choice' => [
                        'type' => 'union',
                        'members' => [
                            'text' => ['target' => 'smithy.api#String']
                        ]
                    ],
                    'example#Items' => [
                        'type' => 'list',
                        'member' => ['target' => 'example#Nested']
                    ],
                    'example#Attributes' => [
                        'type' => 'map',
                        'key' => ['target' => 'smithy.api#String'],
                        'value' => ['target' => 'example#Nested']
                    ],
                    'example#Color' => [
                        'type' => 'enum',
                        'members' => [
                            'red' => [
                                'target' => 'smithy.api#Unit',
                                'traits' => [
                                    'smithy.api#enumValue' => 'red'
                                ]
                            ]
                        ]
                    ]
                ]
            ])
        );

        $container = $converter->getSchema('example#Container');
        self::assertSame(
            ['value'],
            \array_keys($container->getMember('nested')->getMembers())
        );
        self::assertSame(
            ['text'],
            \array_keys($container->getMember('choice')->getMembers())
        );
        self::assertSame(
            'example#Items$member',
            $container->getMember('items')->getListMember()?->id
        );
        self::assertSame(
            'example#Attributes$key',
            $container->getMember('attributes')->getMapKey()?->id
        );
        self::assertSame(
            'example#Attributes$value',
            $container->getMember('attributes')->getMapValue()?->id
        );
        self::assertSame(
            ['red' => 'red'],
            $container->getMember('color')->enumValues
        );
    }

    public function testDoesNotInheritDenseTargetXmlNameOntoMember(): void
    {
        $converter = new SchemaConverter(
            SmithyModel::fromArray([
                'smithy' => '2.0',
                'shapes' => [
                    'example#Container' => [
                        'type' => 'structure',
                        'members' => [
                            'implicit' => ['target' => 'example#Target'],
                            'explicit' => [
                                'target' => 'example#Target',
                                'traits' => [
                                    'smithy.api#xmlName' => 'memberName'
                                ]
                            ]
                        ]
                    ],
                    'example#Target' => [
                        'type' => 'structure',
                        'traits' => [
                            'smithy.api#xmlName' => 'TargetName'
                        ]
                    ]
                ]
            ])
        );

        $container = $converter->getSchema('example#Container');
        $implicit = $container->getMember('implicit');
        $explicit = $container->getMember('explicit');

        self::assertNotNull($implicit);
        self::assertFalse($implicit->hasTrait(Schema::T_XML_NAME));
        self::assertSame(
            'TargetName',
            $implicit->getTraitById('smithy.api#xmlName')
        );

        self::assertNotNull($explicit);
        self::assertSame(
            'memberName',
            $explicit->getTrait(Schema::T_XML_NAME)
        );
    }

    public function testCompilesRuntimeHttpUriMetadata(): void
    {
        $converter = new SchemaConverter(
            SmithyModel::fromArray([
                'smithy' => '2.0',
                'shapes' => [
                    'example#Input' => [
                        'type' => 'structure',
                        'members' => [
                            'bucket' => [
                                'target' => 'smithy.api#String',
                                'traits' => [
                                    'smithy.api#httpLabel' => []
                                ]
                            ],
                            'key' => [
                                'target' => 'smithy.api#String',
                                'traits' => [
                                    'smithy.api#httpLabel' => []
                                ]
                            ]
                        ]
                    ],
                    'example#Output' => [
                        'type' => 'structure'
                    ],
                    'example#PutObject' => [
                        'type' => 'operation',
                        'input' => ['target' => 'example#Input'],
                        'output' => ['target' => 'example#Output'],
                        'traits' => [
                            'smithy.api#http' => [
                                'method' => 'PUT',
                                'uri' => '/{bucket}/{key+}'
                                    . '?x-id=PutObject&uploads',
                                'code' => 200
                            ]
                        ]
                    ]
                ]
            ])
        );

        $http = $converter->getSchema('example#PutObject')
            ->getTrait(Schema::T_HTTP);

        self::assertSame(
            [
                ['/', null, false],
                [null, 0, false],
                ['/', null, false],
                [null, 1, true]
            ],
            $http['pathSegments']
        );
        self::assertSame(
            'x-id=PutObject&uploads',
            $http['pathQueryString']
        );
        self::assertSame(
            $http,
            $converter->getSchema('example#PutObject')
                ->getTraitById('smithy.api#http')
        );
    }

    public function testNormalizesRequestCompressionMetadata(): void
    {
        $converter = new SchemaConverter(
            SmithyModel::fromArray([
                'smithy' => '2.0',
                'shapes' => [
                    'example#Compress' => [
                        'type' => 'operation',
                        'traits' => [
                            'smithy.api#requestCompression' => [
                                'encodings' => ['gzip']
                            ]
                        ]
                    ]
                ]
            ])
        );

        $schema = $converter->getSchema('example#Compress');

        self::assertSame(
            ['gzip'],
            $schema->getTrait(Schema::T_REQUEST_COMPRESSION)
        );
        self::assertSame(
            ['gzip'],
            $schema->getTraitById('smithy.api#requestCompression')
        );
    }

    public function testHandlesMutuallyRecursiveShapes(): void
    {
        $converter = new SchemaConverter($this->model());

        $left = $converter->getSchema('example#Left');
        $right = $left->getMember('right')?->resolveTarget();

        self::assertNotNull($right);
        self::assertSame(
            $left,
            $right->getMember('left')?->resolveTarget()
        );
    }

    public function testPreservesRecursiveCollectionMemberMetadata(): void
    {
        $listConverter = new SchemaConverter($this->recursiveCollectionModel());
        $groupList = $listConverter->getSchema('example#GroupList');
        $group = $groupList->getListMember()?->resolveTarget();
        $childGroups = $group?->getMember('childGroups');

        self::assertNotNull($childGroups);
        self::assertInstanceOf(SchemaReference::class, $childGroups->target);
        self::assertSame(ShapeType::List_->value, $childGroups->type);
        self::assertNotNull($childGroups->getListMember());
        self::assertSame(
            $group,
            $childGroups->getListMember()?->resolveTarget()
        );

        $mapConverter = new SchemaConverter($this->recursiveCollectionModel());
        $groupMap = $mapConverter->getSchema('example#GroupMap');
        $mappedGroup = $groupMap->getMapValue()?->resolveTarget();
        $childMap = $mappedGroup?->getMember('childMap');

        self::assertNotNull($childMap);
        self::assertInstanceOf(SchemaReference::class, $childMap->target);
        self::assertSame(ShapeType::Map->value, $childMap->type);
        self::assertSame(
            ShapeType::String_->value,
            $childMap->getMapKey()?->type
        );
        self::assertNotNull($childMap->getMapValue());
        self::assertSame(
            $mappedGroup,
            $childMap->getMapValue()?->resolveTarget()
        );
    }

    public function testPreservesOriginalShapeIds(): void
    {
        $converter = new SchemaConverter($this->model());

        self::assertSame(
            'example#OriginalInput',
            $converter->getSchema('example#GeneratedInput')->id
        );
    }

    public function testConvertedSchemasDriveClasslessJsonDeserialization(): void
    {
        $converter = new SchemaConverter($this->model());
        $node = $converter->getSchema('example#Node');
        $members = \array_fill(0, $node->getMemberCount(), null);

        (new JsonCodec())->createDeserializer(
            new StringRequestStream(
                '{"id":"root","displayName":"Root",'
                . '"next":{"id":"child","displayName":"Child"}}'
            )
        )->deserialize($node, $members);

        $document = StructDocument::fromMembers($node, $members);
        self::assertSame(
            'Root',
            $document->getMemberValue($node->getMember('name'))
        );

        $next = $document->getMemberValue($node->getMember('next'));
        self::assertInstanceOf(StructDocument::class, $next);
        self::assertSame(
            'Child',
            $next->getMemberValue(
                $next->getSchema()->getMember('name')
            )
        );
    }

    private function recursiveCollectionModel(): SmithyModel
    {
        return SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => [
                'example#Group' => [
                    'type' => 'structure',
                    'members' => [
                        'childGroups' => [
                            'target' => 'example#GroupList'
                        ],
                        'childMap' => [
                            'target' => 'example#GroupMap'
                        ]
                    ]
                ],
                'example#GroupList' => [
                    'type' => 'list',
                    'member' => ['target' => 'example#Group']
                ],
                'example#GroupMap' => [
                    'type' => 'map',
                    'key' => ['target' => 'smithy.api#String'],
                    'value' => ['target' => 'example#Group']
                ]
            ]
        ]);
    }

    private function model(): SmithyModel
    {
        return SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => [
                'example#Identified' => [
                    'type' => 'structure',
                    'members' => [
                        'id' => [
                            'target' => 'smithy.api#String',
                            'traits' => ['smithy.api#required' => []]
                        ]
                    ]
                ],
                'example#Name' => [
                    'type' => 'string',
                    'traits' => [
                        'smithy.api#sensitive' => [],
                        'smithy.api#pattern' => '^[a-z]+$',
                        'example#customTrait' => 'target'
                    ]
                ],
                'example#Node' => [
                    'type' => 'structure',
                    'mixins' => [['target' => 'example#Identified']],
                    'members' => [
                        'name' => [
                            'target' => 'example#Name',
                            'traits' => [
                                'smithy.api#jsonName' => 'displayName',
                                'example#customTrait' => 'member'
                            ]
                        ],
                        'next' => ['target' => 'example#Node'],
                        'children' => ['target' => 'example#NodeList']
                    ]
                ],
                'example#NodeList' => [
                    'type' => 'list',
                    'member' => ['target' => 'example#Node']
                ],
                'example#NodeMap' => [
                    'type' => 'map',
                    'key' => ['target' => 'smithy.api#String'],
                    'value' => ['target' => 'example#Node']
                ],
                'example#Choice' => [
                    'type' => 'union',
                    'members' => [
                        'node' => ['target' => 'example#Node'],
                        'text' => ['target' => 'smithy.api#String']
                    ]
                ],
                'example#Color' => [
                    'type' => 'enum',
                    'members' => [
                        'red' => [
                            'target' => 'smithy.api#Unit',
                            'traits' => ['smithy.api#enumValue' => 'r']
                        ],
                        'blue' => [
                            'target' => 'smithy.api#Unit',
                            'traits' => ['smithy.api#enumValue' => 'b']
                        ]
                    ]
                ],
                'example#Code' => [
                    'type' => 'intEnum',
                    'members' => [
                        'ok' => [
                            'target' => 'smithy.api#Unit',
                            'traits' => ['smithy.api#enumValue' => 200]
                        ],
                        'failure' => [
                            'target' => 'smithy.api#Unit',
                            'traits' => ['smithy.api#enumValue' => 500]
                        ]
                    ]
                ],
                'example#Problem' => [
                    'type' => 'structure',
                    'traits' => [
                        'smithy.api#error' => 'client',
                        'example.protocols#errorCode' => [
                            'code' => 'BadNode',
                            'httpResponseCode' => 400
                        ]
                    ]
                ],
                'example#GetNode' => [
                    'type' => 'operation',
                    'input' => ['target' => 'example#Node'],
                    'output' => ['target' => 'example#Node'],
                    'errors' => [['target' => 'example#Problem']],
                    'traits' => [
                        'smithy.api#http' => [
                            'method' => 'POST',
                            'uri' => '/',
                            'code' => 200
                        ],
                        'example#tagging' => ['team' => 'sprockets'],
                        'example#owner' => 'platform'
                    ]
                ],
                'example#Service' => [
                    'type' => 'service',
                    'version' => '2026-08-20',
                    'operations' => [['target' => 'example#GetNode']],
                    'errors' => [['target' => 'example#ServiceProblem']]
                ],
                'example#ServiceProblem' => [
                    'type' => 'structure',
                    'traits' => ['smithy.api#error' => 'server']
                ],
                'example#Left' => [
                    'type' => 'structure',
                    'members' => [
                        'right' => ['target' => 'example#Right']
                    ]
                ],
                'example#Right' => [
                    'type' => 'structure',
                    'members' => [
                        'left' => ['target' => 'example#Left']
                    ]
                ],
                'example#GeneratedInput' => [
                    'type' => 'structure',
                    'traits' => [
                        'smithy.synthetic#originalShapeId' =>
                            'example#OriginalInput'
                    ]
                ]
            ]
        ]);
    }
}
