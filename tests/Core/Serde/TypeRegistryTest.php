<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Serde;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Model\SmithyModel;
use Smithy\Core\Serde\SchemaConverter;
use Smithy\Core\Serde\TypeRegistry;
use Smithy\Core\Shape\ShapeType;

#[CoversClass(TypeRegistry::class)]
final class TypeRegistryTest extends TestCase
{
    public function testReturnsNullForUnknownShape(): void
    {
        $registry = $this->registry();

        self::assertNull($registry->schemaFor('example#Missing'));
    }

    public function testAliasCacheDoesNotExposeAbsentOriginalShape(): void
    {
        $model = SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => [
                'example#GeneratedInput' => [
                    'type' => 'structure',
                    'traits' => [
                        'smithy.synthetic#originalShapeId' =>
                            'example#MissingOriginal'
                    ]
                ]
            ]
        ]);
        $registry = new TypeRegistry($model, new SchemaConverter($model));

        self::assertNotNull($registry->schemaFor('example#GeneratedInput'));
        self::assertNull($registry->schemaFor('example#MissingOriginal'));
    }

    public function testAliasesWithSameAbsentOriginalDoNotShareSchemas(): void
    {
        $model = SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => [
                'example#FirstGeneratedInput' => [
                    'type' => 'structure',
                    'traits' => [
                        'smithy.synthetic#originalShapeId' =>
                            'example#MissingOriginal'
                    ],
                    'members' => [
                        'first' => ['target' => 'example#Name']
                    ]
                ],
                'example#SecondGeneratedInput' => [
                    'type' => 'structure',
                    'traits' => [
                        'smithy.synthetic#originalShapeId' =>
                            'example#MissingOriginal'
                    ],
                    'members' => [
                        'second' => ['target' => 'example#Name']
                    ]
                ],
                'example#Name' => [
                    'type' => 'string'
                ]
            ]
        ]);
        $registry = new TypeRegistry($model, new SchemaConverter($model));

        $first = $registry->schemaFor('example#FirstGeneratedInput');
        $second = $registry->schemaFor('example#SecondGeneratedInput');

        self::assertNotNull($first);
        self::assertNotNull($second);
        self::assertNotSame($first, $second);
        self::assertSame(['first'], \array_keys($first->getMembers()));
        self::assertSame(['second'], \array_keys($second->getMembers()));
    }

    public function testCachesSchemasByRequestedAndCanonicalIds(): void
    {
        $registry = $this->registry();

        $requested = $registry->schemaFor('example#GeneratedInput');
        $canonical = $registry->schemaFor('example#OriginalInput');

        self::assertNotNull($requested);
        self::assertSame($requested, $canonical);
        self::assertSame('example#OriginalInput', $requested->id);
        self::assertSame(ShapeType::Structure->value, $requested->type);
        self::assertSame(
            ['canonical'],
            \array_keys($requested->getMembers())
        );
    }

    public function testReusesCanonicalSchemaResolvedBeforeAlias(): void
    {
        $registry = $this->registry();

        $canonical = $registry->schemaFor('example#OriginalInput');
        $requested = $registry->schemaFor('example#GeneratedInput');

        self::assertNotNull($canonical);
        self::assertSame($canonical, $requested);
        self::assertSame(
            ['example#OriginalInput' => $canonical],
            $registry->all()
        );
    }

    public function testReturnsResolvedSchemasByCanonicalId(): void
    {
        $registry = $this->registry();

        $input = $registry->schemaFor('example#GeneratedInput');
        $name = $registry->schemaFor('example#Name');

        self::assertSame(
            [
                'example#OriginalInput' => $input,
                'example#Name' => $name
            ],
            $registry->all()
        );
    }

    private function registry(): TypeRegistry
    {
        $model = SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => [
                'example#GeneratedInput' => [
                    'type' => 'structure',
                    'traits' => [
                        'smithy.synthetic#originalShapeId' =>
                            'example#OriginalInput'
                    ],
                    'members' => [
                        'alias' => ['target' => 'example#Name']
                    ]
                ],
                'example#OriginalInput' => [
                    'type' => 'structure',
                    'members' => [
                        'canonical' => ['target' => 'example#Name']
                    ]
                ],
                'example#Name' => [
                    'type' => 'string'
                ]
            ]
        ]);

        return new TypeRegistry($model, new SchemaConverter($model));
    }
}
