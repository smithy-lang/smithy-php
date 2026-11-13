<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Dynamic;

use PHPUnit\Framework\Attributes\{CoversClass, DataProvider};
use PHPUnit\Framework\TestCase;
use Smithy\Client\Dynamic\DocumentFactory;
use Smithy\Core\Model\SmithyModel;
use Smithy\Core\Serde\SchemaConverter;
use Smithy\Core\Shape\Exception\InvalidShapeValueException;
use Smithy\Core\Shape\Document\{
    AbstractDocument,
    BlobValue,
    StructDocument,
    TimestampValue,
    UnionDocument
};
use Smithy\Tests\Client\Dynamic\Utils\{
    EnumFixture,
    SerializableStructFixture,
    UnionFixture
};

#[CoversClass(DocumentFactory::class)]
final class DocumentFactoryTest extends TestCase
{
    public function testCreatesSchemaBackedRuntimeValues(): void
    {
        $schema = $this->converter()->getSchema('example#Input');
        $document = (new DocumentFactory())->createStruct($schema, [
            'id' => 'sprocket',
            'blob' => new BlobValue("\x00\x01"),
            'at' => new TimestampValue(1_700_000_000.5),
            'details' => ['count' => 3],
            'tags' => ['one', 'two'],
            'scores' => ['quality' => 9],
            'choice' => ['text' => 'selected'],
            'color' => 'red',
            'status' => 200,
            'doc' => ['enabled' => true],
            'sparse' => ['first', null]
        ]);

        self::assertSame(
            'sprocket',
            $this->memberValue($document, 'id')
        );
        self::assertSame(
            "\x00\x01",
            $this->memberValue($document, 'blob')
        );
        self::assertSame(
            1_700_000_000.5,
            $this->memberValue($document, 'at')
        );
        self::assertSame(5, $this->memberValue($document, 'limit'));

        $details = $this->memberValue($document, 'details');
        self::assertInstanceOf(StructDocument::class, $details);
        self::assertSame(
            3,
            $this->memberValue($details, 'count')
        );

        $choice = $this->memberValue($document, 'choice');
        self::assertInstanceOf(UnionDocument::class, $choice);
        self::assertSame('text', $choice->tag());
        self::assertSame('selected', $choice->value());

        self::assertSame(
            ['one', 'two'],
            $this->memberValue($document, 'tags')
        );
        self::assertSame(
            ['quality' => 9],
            $this->memberValue($document, 'scores')
        );
        self::assertSame(
            ['first', null],
            $this->memberValue($document, 'sparse')
        );
        self::assertSame(
            true,
            $this->memberValue($document, 'doc')
                ->getMember('enabled')
                ?->asBoolean()
        );
    }

    public function testCreatesFromAnUntypedDocument(): void
    {
        $schema = $this->converter()->getSchema('example#Input');
        $input = AbstractDocument::fromValue([
            'id' => 'document',
            'blob' => new BlobValue('bytes')
        ]);
        self::assertNotNull($input);

        $document = (new DocumentFactory())->createStruct($schema, $input);

        self::assertSame(
            'document',
            $this->memberValue($document, 'id')
        );
        self::assertSame(
            'bytes',
            $this->memberValue($document, 'blob')
        );
    }

    public function testCreatesRecursiveStructures(): void
    {
        $schema = $this->converter()->getSchema('example#Node');
        $document = (new DocumentFactory())->createStruct($schema, [
            'value' => 'root',
            'next' => ['value' => 'child']
        ]);

        $next = $this->memberValue($document, 'next');
        self::assertInstanceOf(StructDocument::class, $next);
        self::assertSame('child', $this->memberValue($next, 'value'));
    }

    public function testDeepInvalidChildReportsFullPathQuickly(): void
    {
        $schema = $this->converter()->getSchema('example#Node');
        $input = ['value' => 1];
        for ($i = 0; $i < 30; $i++) {
            $input = ['value' => 'ok', 'next' => $input];
        }

        $start = \microtime(true);
        try {
            (new DocumentFactory())->createStruct($schema, $input);
            $this->fail('Expected invalid input to be rejected.');
        } catch (InvalidShapeValueException $e) {
            $this->assertStringContainsString(
                '$' . \str_repeat('.next', 30) . '.value',
                $e->getMessage()
            );
        }

        // Exponential retries would take minutes at this depth.
        $this->assertLessThan(2.0, \microtime(true) - $start);
    }

    public function testCreatesEmptyUnitInputFromNull(): void
    {
        $schema = $this->converter()->getSchema('smithy.api#Unit');
        $document = (new DocumentFactory())->createStruct($schema, null);

        self::assertSame([], $document->asMap());
    }

    public function testCreatesOptionalAndDefaultedInputFromNull(): void
    {
        $schema = $this->converter()->getSchema('example#OptionalInput');
        $document = (new DocumentFactory())->createStruct($schema, null);

        self::assertNull($this->memberValue($document, 'query'));
        self::assertSame(5, $this->memberValue($document, 'limit'));
    }

    public function testHonorsClientDefaultSemantics(): void
    {
        $converter = $this->defaultSemanticsConverter();
        $document = (new DocumentFactory())->createStruct(
            $converter->getSchema('example#Input'),
            [
                'nested' => [],
                'clientOptional' => []
            ]
        );

        self::assertNull($this->memberValue($document, 'topLevel'));
        self::assertNull(
            $this->memberValue($document, 'topLevelTarget')
        );

        $nested = $this->memberValue($document, 'nested');
        self::assertInstanceOf(StructDocument::class, $nested);
        self::assertSame(
            'abc',
            $this->memberValue($nested, 'blob')
        );
        self::assertSame(
            'target',
            $this->memberValue($nested, 'targetDefault')
        );
        self::assertSame(
            'member',
            $this->memberValue($nested, 'memberDefault')
        );
        self::assertNull(
            $this->memberValue($nested, 'clearedDefault')
        );
        self::assertNull(
            $this->memberValue($nested, 'clientOptionalDefault')
        );
        $this->assertInheritedScalarDefaults($nested);

        $clientOptional = $this->memberValue(
            $document,
            'clientOptional'
        );
        self::assertInstanceOf(StructDocument::class, $clientOptional);
        self::assertNull(
            $this->memberValue($clientOptional, 'value')
        );
    }

    public function testCreatesOutputsFromDecodedMembers(): void
    {
        $schema = $this->defaultSemanticsConverter()
            ->getSchema('example#Output');
        $factory = new DocumentFactory();

        $defaulted = $factory->createStructFromMembers($schema, []);
        self::assertSame(
            'abc',
            $this->memberValue($defaulted, 'blob')
        );
        self::assertSame(
            'target',
            $this->memberValue($defaulted, 'targetDefault')
        );
        self::assertNull(
            $this->memberValue($defaulted, 'clearedDefault')
        );
        $this->assertInheritedScalarDefaults($defaulted);

        $decoded = $factory->createStructFromMembers(
            $schema,
            [
                'wire-bytes',
                StructDocument::fromMembers(
                    $schema->getMember('nested'),
                    []
                ),
                [
                    StructDocument::fromMembers(
                        $schema->getMember('nestedList')
                            ->getListMember(),
                        []
                    )
                ],
                [
                    'first' => StructDocument::fromMembers(
                        $schema->getMember('nestedMap')
                            ->getMapValue(),
                        []
                    )
                ],
                UnionDocument::create(
                    $schema->getMember('nestedUnion'),
                    'value',
                    StructDocument::fromMembers(
                        $schema->getMember('nestedUnion')
                            ->getMember('value'),
                        []
                    )
                )
            ]
        );
        self::assertSame(
            'wire-bytes',
            $this->memberValue($decoded, 'blob')
        );

        $nested = $this->memberValue($decoded, 'nested');
        self::assertInstanceOf(StructDocument::class, $nested);
        self::assertSame('abc', $this->memberValue($nested, 'blob'));

        $nestedList = $this->memberValue($decoded, 'nestedList');
        self::assertInstanceOf(StructDocument::class, $nestedList[0]);
        self::assertSame(
            'abc',
            $this->memberValue($nestedList[0], 'blob')
        );

        $nestedMap = $this->memberValue($decoded, 'nestedMap');
        self::assertInstanceOf(
            StructDocument::class,
            $nestedMap['first']
        );
        self::assertSame(
            'abc',
            $this->memberValue($nestedMap['first'], 'blob')
        );

        $nestedUnion = $this->memberValue($decoded, 'nestedUnion');
        self::assertInstanceOf(UnionDocument::class, $nestedUnion);
        self::assertInstanceOf(
            StructDocument::class,
            $nestedUnion->value()
        );
        self::assertSame(
            'abc',
            $this->memberValue($nestedUnion->value(), 'blob')
        );
    }

    public function testRejectsNullInputWithRequiredMembers(): void
    {
        $schema = $this->converter()->getSchema('example#Input');

        $this->expectException(InvalidShapeValueException::class);
        $this->expectExceptionMessage('Missing required member $.id');

        (new DocumentFactory())->createStruct($schema, null);
    }

    public function testCreatesUnitUnionVariantFromNull(): void
    {
        $schema = $this->converter()->getSchema('example#Input');
        $document = (new DocumentFactory())->createStruct($schema, [
            'id' => 'unit',
            'choice' => ['none' => null]
        ]);
        $choice = $this->memberValue($document, 'choice');

        self::assertInstanceOf(UnionDocument::class, $choice);
        self::assertSame('none', $choice->tag());
        self::assertInstanceOf(StructDocument::class, $choice->value());
        self::assertSame([], $choice->value()->asMap());
    }

    public function testRecoercesUnionDocumentWithCurrentSchema(): void
    {
        $schema = $this->converter()->getSchema('example#Choice');
        $sourceSchema = $this->sourceUnionConverter()
            ->getSchema('example#Choice');
        $source = (new DocumentFactory())->create($sourceSchema, [
            'count' => 5
        ]);

        $document = (new DocumentFactory())->create($schema, $source);

        self::assertInstanceOf(UnionDocument::class, $document);
        self::assertSame($schema, $document->getSchema());
        self::assertSame('count', $document->tag());
        self::assertSame(1, $document->getMemberIndex());
        self::assertSame(5, $document->value());
    }

    public function testRevalidatesUnionDocumentVariantValue(): void
    {
        $schema = $this->converter()->getSchema('example#Choice');
        $sourceSchema = $this->sourceUnionConverter(
            countTarget: 'smithy.api#String'
        )->getSchema('example#Choice');
        $source = (new DocumentFactory())->create($sourceSchema, [
            'count' => 'five'
        ]);

        $this->expectException(InvalidShapeValueException::class);
        $this->expectExceptionMessage('Expected integer at $.count');

        (new DocumentFactory())->create($schema, $source);
    }

    public function testNormalizesTypedUnionDocumentValues(): void
    {
        $schema = $this->converter()->getSchema('example#Choice');
        $sourceSchema = $this->sourceUnionConverter()
            ->getSchema('example#Choice');
        $source = (new DocumentFactory())->create($sourceSchema, [
            'blob' => new BlobValue('bytes')
        ]);

        $document = (new DocumentFactory())->create($schema, $source);

        self::assertInstanceOf(UnionDocument::class, $document);
        self::assertSame($schema, $document->getSchema());
        self::assertSame('blob', $document->tag());
        self::assertSame('bytes', $document->value());
    }

    public function testNormalizesTypedGeneratedUnionValues(): void
    {
        $schema = $this->converter()->getSchema('example#Choice');
        $sourceSchema = $this->sourceUnionConverter()
            ->getSchema('example#Choice');
        $source = new UnionFixture($sourceSchema, 'blob', 'bytes');

        $document = (new DocumentFactory())->create($schema, $source);

        self::assertInstanceOf(UnionDocument::class, $document);
        self::assertSame($schema, $document->getSchema());
        self::assertSame('blob', $document->tag());
        self::assertSame('bytes', $document->value());
    }

    public function testCreatesPreEpochDateTimeWithFraction(): void
    {
        $schema = $this->converter()->getSchema('example#Input');
        $document = (new DocumentFactory())->createStruct($schema, [
            'id' => 'pre-epoch',
            'at' => new \DateTimeImmutable(
                '1969-12-31T23:59:59.500000Z'
            )
        ]);

        self::assertSame(-0.5, $this->memberValue($document, 'at'));
    }

    public function testRecoercesStructDocumentWithCurrentSchema(): void
    {
        $schema = $this->converter()->getSchema('example#Input');
        $sourceSchema = $this->sourceConverter()
            ->getSchema('example#Input');
        $source = (new DocumentFactory())->createStruct($sourceSchema, [
            'id' => 'document'
        ]);

        $document = (new DocumentFactory())->createStruct($schema, $source);

        self::assertSame($schema, $document->getSchema());
        self::assertSame(
            'document',
            $this->memberValue($document, 'id')
        );
        self::assertSame(5, $this->memberValue($document, 'limit'));
    }

    public function testRecoercesSerializableStructWithCurrentSchema(): void
    {
        $schema = $this->converter()->getSchema('example#Input');
        $sourceSchema = $this->sourceConverter()
            ->getSchema('example#Input');
        $source = new SerializableStructFixture(
            $sourceSchema,
            ['document']
        );

        $document = (new DocumentFactory())->createStruct($schema, $source);

        self::assertSame($schema, $document->getSchema());
        self::assertSame(
            'document',
            $this->memberValue($document, 'id')
        );
        self::assertSame(5, $this->memberValue($document, 'limit'));
    }

    public function testNormalizesTypedSerializableStructMembers(): void
    {
        $schema = $this->converter()->getSchema('example#Input');
        $sourceConverter = $this->sourceConverter();
        $sourceSchema = $sourceConverter->getSchema('example#Input');
        $source = new SerializableStructFixture(
            $sourceSchema,
            [
                'document',
                'bytes',
                new EnumFixture(
                    $sourceConverter->getSchema('example#Color'),
                    'green'
                )
            ]
        );

        $document = (new DocumentFactory())->createStruct($schema, $source);

        self::assertSame('bytes', $this->memberValue($document, 'blob'));
        self::assertSame('green', $this->memberValue($document, 'color'));
    }

    public function testRevalidatesRequiredStructDocumentMembers(): void
    {
        $schema = $this->converter()->getSchema('example#Input');
        $sourceSchema = $this->sourceConverter(includeId: false)
            ->getSchema('example#Input');
        $source = (new DocumentFactory())->createStruct($sourceSchema, []);

        $this->expectException(InvalidShapeValueException::class);
        $this->expectExceptionMessage('Missing required member $.id');

        (new DocumentFactory())->createStruct($schema, $source);
    }

    public function testAcceptsUnknownEnumValues(): void
    {
        $schema = $this->converter()->getSchema('example#Input');
        $document = (new DocumentFactory())->createStruct($schema, [
            'id' => 'unknown-enums',
            'color' => 'green',
            'status' => 404
        ]);

        self::assertSame('green', $this->memberValue($document, 'color'));
        self::assertSame(404, $this->memberValue($document, 'status'));
    }

    public function testAcceptsGeneratedEnumValues(): void
    {
        $converter = $this->converter();
        $schema = $converter->getSchema('example#Input');
        $document = (new DocumentFactory())->createStruct($schema, [
            'id' => 'generated-enums',
            'color' => new EnumFixture(
                $converter->getSchema('example#Color'),
                'green'
            ),
            'status' => new EnumFixture(
                $converter->getSchema('example#Status'),
                404
            )
        ]);

        self::assertSame('green', $this->memberValue($document, 'color'));
        self::assertSame(404, $this->memberValue($document, 'status'));
    }

    /**
     * @param array<string, mixed> $input Invalid input.
     */
    #[DataProvider('invalidInputProvider')]
    public function testRejectsIncompatibleInput(
        array $input,
        string $message
    ): void
    {
        $schema = $this->converter()->getSchema('example#Input');

        $this->expectException(InvalidShapeValueException::class);
        $this->expectExceptionMessage($message);
        (new DocumentFactory())->createStruct($schema, $input);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function invalidInputProvider(): iterable
    {
        yield 'missing required member' => [
            [],
            'Missing required member $.id'
        ];
        yield 'unknown member' => [
            ['id' => 'x', 'unknown' => true],
            'Unknown member $.unknown'
        ];
        yield 'string is not a blob' => [
            ['id' => 'x', 'blob' => 'base64-looking'],
            'Expected BlobValue at $.blob'
        ];
        yield 'invalid enum type' => [
            ['id' => 'x', 'color' => 1],
            'Expected string enum at $.color'
        ];
        yield 'invalid integer enum type' => [
            ['id' => 'x', 'status' => '200'],
            'Expected integer enum at $.status'
        ];
        yield 'union has multiple variants' => [
            [
                'id' => 'x',
                'choice' => ['text' => 'x', 'count' => 1]
            ],
            'Expected single-entry union at $.choice'
        ];
        yield 'union variant contains null' => [
            ['id' => 'x', 'choice' => ['text' => null]],
            'Union variant $.choice.text cannot be null'
        ];
        yield 'dense list contains null' => [
            ['id' => 'x', 'tags' => ['x', null]],
            'Null value is not allowed at $.tags[1]'
        ];
    }

    private function memberValue(
        StructDocument $document,
        string $name
    ): mixed
    {
        return $document->getMemberValue(
            $document->getSchema()->getMember($name)
        );
    }

    private function assertInheritedScalarDefaults(
        StructDocument $document
    ): void
    {
        self::assertEqualsWithDelta(
            1_577_836_800.123,
            $this->memberValue($document, 'timestamp'),
            0.000_001
        );
        self::assertNan($this->memberValue($document, 'nan'));
        self::assertSame(
            \INF,
            $this->memberValue($document, 'positiveInfinity')
        );
        self::assertSame(
            -\INF,
            $this->memberValue($document, 'negativeInfinity')
        );
    }

    private function converter(): SchemaConverter
    {
        return new SchemaConverter(SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => [
                'example#Input' => [
                    'type' => 'structure',
                    'members' => [
                        'id' => [
                            'target' => 'smithy.api#String',
                            'traits' => ['smithy.api#required' => []]
                        ],
                        'blob' => ['target' => 'smithy.api#Blob'],
                        'at' => ['target' => 'smithy.api#Timestamp'],
                        'details' => ['target' => 'example#Details'],
                        'tags' => ['target' => 'example#Tags'],
                        'scores' => ['target' => 'example#Scores'],
                        'choice' => ['target' => 'example#Choice'],
                        'color' => ['target' => 'example#Color'],
                        'status' => ['target' => 'example#Status'],
                        'doc' => ['target' => 'smithy.api#Document'],
                        'sparse' => ['target' => 'example#SparseTags'],
                        'limit' => [
                            'target' => 'smithy.api#Integer',
                            'traits' => ['smithy.api#default' => 5]
                        ]
                    ]
                ],
                'example#OptionalInput' => [
                    'type' => 'structure',
                    'members' => [
                        'query' => ['target' => 'smithy.api#String'],
                        'limit' => [
                            'target' => 'smithy.api#Integer',
                            'traits' => ['smithy.api#default' => 5]
                        ]
                    ]
                ],
                'example#Details' => [
                    'type' => 'structure',
                    'members' => [
                        'count' => ['target' => 'smithy.api#Integer']
                    ]
                ],
                'example#Tags' => [
                    'type' => 'list',
                    'member' => ['target' => 'smithy.api#String']
                ],
                'example#SparseTags' => [
                    'type' => 'list',
                    'member' => ['target' => 'smithy.api#String'],
                    'traits' => ['smithy.api#sparse' => []]
                ],
                'example#Scores' => [
                    'type' => 'map',
                    'key' => ['target' => 'smithy.api#String'],
                    'value' => ['target' => 'smithy.api#Integer']
                ],
                'example#Choice' => [
                    'type' => 'union',
                    'members' => [
                        'text' => ['target' => 'smithy.api#String'],
                        'count' => ['target' => 'smithy.api#Integer'],
                        'none' => ['target' => 'smithy.api#Unit'],
                        'blob' => ['target' => 'smithy.api#Blob']
                    ]
                ],
                'example#Color' => [
                    'type' => 'enum',
                    'members' => [
                        'red' => [
                            'target' => 'smithy.api#Unit',
                            'traits' => [
                                'smithy.api#enumValue' => 'red'
                            ]
                        ],
                        'blue' => [
                            'target' => 'smithy.api#Unit',
                            'traits' => [
                                'smithy.api#enumValue' => 'blue'
                            ]
                        ]
                    ]
                ],
                'example#Status' => [
                    'type' => 'intEnum',
                    'members' => [
                        'ok' => [
                            'target' => 'smithy.api#Unit',
                            'traits' => [
                                'smithy.api#enumValue' => 200
                            ]
                        ]
                    ]
                ],
                'example#Node' => [
                    'type' => 'structure',
                    'members' => [
                        'value' => [
                            'target' => 'smithy.api#String',
                            'traits' => ['smithy.api#required' => []]
                        ],
                        'next' => ['target' => 'example#Node']
                    ]
                ]
            ]
        ]));
    }

    private function sourceConverter(bool $includeId = true): SchemaConverter
    {
        return new SchemaConverter(SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => [
                'example#Input' => [
                    'type' => 'structure',
                    'members' => $includeId
                        ? [
                            'id' => ['target' => 'smithy.api#String'],
                            'blob' => ['target' => 'smithy.api#Blob'],
                            'color' => ['target' => 'example#Color']
                        ]
                        : []
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
        ]));
    }

    private function defaultSemanticsConverter(): SchemaConverter
    {
        return new SchemaConverter(SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => [
                'example#Input' => [
                    'type' => 'structure',
                    'members' => [
                        'topLevel' => [
                            'target' => 'smithy.api#String',
                            'traits' => [
                                'smithy.api#default' => 'default'
                            ]
                        ],
                        'topLevelTarget' => [
                            'target' => 'example#DefaultString'
                        ],
                        'nested' => ['target' => 'example#Defaults'],
                        'clientOptional' => [
                            'target' => 'example#ClientOptional'
                        ]
                    ],
                    'traits' => ['smithy.api#input' => []]
                ],
                'example#Output' => [
                    'type' => 'structure',
                    'members' => [
                        'blob' => [
                            'target' => 'smithy.api#Blob',
                            'traits' => [
                                'smithy.api#default' => 'YWJj'
                            ]
                        ],
                        'nested' => ['target' => 'example#Defaults'],
                        'nestedList' => [
                            'target' => 'example#DefaultsList'
                        ],
                        'nestedMap' => [
                            'target' => 'example#DefaultsMap'
                        ],
                        'nestedUnion' => [
                            'target' => 'example#DefaultsUnion'
                        ],
                        'targetDefault' => [
                            'target' => 'example#DefaultString'
                        ],
                        'clearedDefault' => [
                            'target' => 'example#DefaultString',
                            'traits' => [
                                'smithy.api#default' => null
                            ]
                        ],
                        'timestamp' => [
                            'target' => 'example#DefaultTimestamp'
                        ],
                        'nan' => [
                            'target' => 'example#DefaultFloatNaN'
                        ],
                        'positiveInfinity' => [
                            'target' => 'example#DefaultDoubleInfinity'
                        ],
                        'negativeInfinity' => [
                            'target' => 'example#DefaultDoubleNegInfinity'
                        ]
                    ],
                    'traits' => ['smithy.api#output' => []]
                ],
                'example#Defaults' => [
                    'type' => 'structure',
                    'members' => [
                        'blob' => [
                            'target' => 'smithy.api#Blob',
                            'traits' => [
                                'smithy.api#default' => 'YWJj'
                            ]
                        ],
                        'targetDefault' => [
                            'target' => 'example#DefaultString'
                        ],
                        'memberDefault' => [
                            'target' => 'example#DefaultString',
                            'traits' => [
                                'smithy.api#default' => 'member'
                            ]
                        ],
                        'clearedDefault' => [
                            'target' => 'example#DefaultString',
                            'traits' => [
                                'smithy.api#default' => null
                            ]
                        ],
                        'clientOptionalDefault' => [
                            'target' => 'example#DefaultString',
                            'traits' => [
                                'smithy.api#clientOptional' => []
                            ]
                        ],
                        'timestamp' => [
                            'target' => 'example#DefaultTimestamp'
                        ],
                        'nan' => [
                            'target' => 'example#DefaultFloatNaN'
                        ],
                        'positiveInfinity' => [
                            'target' => 'example#DefaultDoubleInfinity'
                        ],
                        'negativeInfinity' => [
                            'target' => 'example#DefaultDoubleNegInfinity'
                        ]
                    ]
                ],
                'example#DefaultString' => [
                    'type' => 'string',
                    'traits' => [
                        'smithy.api#default' => 'target'
                    ]
                ],
                'example#DefaultTimestamp' => [
                    'type' => 'timestamp',
                    'traits' => [
                        'smithy.api#default' =>
                            '2020-01-01T00:00:00.123Z'
                    ]
                ],
                'example#DefaultFloatNaN' => [
                    'type' => 'float',
                    'traits' => [
                        'smithy.api#default' => 'NaN'
                    ]
                ],
                'example#DefaultDoubleInfinity' => [
                    'type' => 'double',
                    'traits' => [
                        'smithy.api#default' => 'Infinity'
                    ]
                ],
                'example#DefaultDoubleNegInfinity' => [
                    'type' => 'double',
                    'traits' => [
                        'smithy.api#default' => '-Infinity'
                    ]
                ],
                'example#DefaultsList' => [
                    'type' => 'list',
                    'member' => ['target' => 'example#Defaults']
                ],
                'example#DefaultsMap' => [
                    'type' => 'map',
                    'key' => ['target' => 'smithy.api#String'],
                    'value' => ['target' => 'example#Defaults']
                ],
                'example#DefaultsUnion' => [
                    'type' => 'union',
                    'members' => [
                        'value' => ['target' => 'example#Defaults']
                    ]
                ],
                'example#ClientOptional' => [
                    'type' => 'structure',
                    'members' => [
                        'value' => [
                            'target' => 'smithy.api#Integer',
                            'traits' => [
                                'smithy.api#clientOptional' => [],
                                'smithy.api#default' => 0
                            ]
                        ]
                    ]
                ]
            ]
        ]));
    }

    private function sourceUnionConverter(
        string $countTarget = 'smithy.api#Integer'
    ): SchemaConverter
    {
        return new SchemaConverter(SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => [
                'example#Choice' => [
                    'type' => 'union',
                    'members' => [
                        'count' => ['target' => $countTarget],
                        'text' => ['target' => 'smithy.api#String'],
                        'blob' => ['target' => 'smithy.api#Blob']
                    ]
                ]
            ]
        ]));
    }
}
