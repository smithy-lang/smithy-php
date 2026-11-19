<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Core\Serde;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Model\SmithyModel;
use Smithy\Core\Serde\CompositeTypeRegistry;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SchemaConverter;
use Smithy\Core\Serde\TypeRegistry;
use Smithy\Core\Serde\TypeRegistryDeserializeTrait;
use Smithy\Core\Serde\TypeRegistryInterface;
use Smithy\Core\Shape\Document\AbstractDocument;
use Smithy\Core\Shape\Document\MapDocument;
use Smithy\Core\Shape\Exception\InvalidShapeValueException;
use Smithy\Tests\Core\Shape\Document\Fixture\WidgetFixture;

#[CoversClass(CompositeTypeRegistry::class)]
#[CoversClass(TypeRegistry::class)]
#[CoversTrait(TypeRegistryDeserializeTrait::class)]
final class CompositeTypeRegistryTest extends TestCase
{
    public function testComposeReturnsFirstMatch(): void
    {
        $first = $this->registry(['smithy.test#A' => WidgetFixture::class]);
        $second = $this->registry([
            'smithy.test#A' => \stdClass::class,
            'smithy.test#B' => \ArrayObject::class
        ]);
        $composed = CompositeTypeRegistry::compose($first, $second);

        self::assertSame(WidgetFixture::class, $composed->classFor('smithy.test#A'));
        self::assertSame(\ArrayObject::class, $composed->classFor('smithy.test#B'));
        self::assertNull($composed->classFor('smithy.test#C'));
    }

    public function testComposeAcceptsMoreThanTwoRegistries(): void
    {
        $composed = CompositeTypeRegistry::compose(
            $this->registry([]),
            $this->registry([]),
            $this->registry(['smithy.test#C' => \ArrayObject::class])
        );

        self::assertSame(\ArrayObject::class, $composed->classFor('smithy.test#C'));
    }

    public function testComposeResolvesSchemasInOrder(): void
    {
        $schema = WidgetFixture::schema();
        $composed = CompositeTypeRegistry::compose(
            $this->registry([]),
            $this->registry([WidgetFixture::SHAPE_ID => WidgetFixture::class])
        );

        self::assertSame($schema, $composed->schemaFor(WidgetFixture::SHAPE_ID));
        self::assertNull($composed->schemaFor('smithy.test#Missing'));
    }

    public function testDeserializeBuildsShapeFromDiscriminator(): void
    {
        $registry = $this->registry([
            WidgetFixture::SHAPE_ID => WidgetFixture::class
        ]);
        $document = new MapDocument(
            ['Name' => AbstractDocument::fromValue('w')],
            null,
            WidgetFixture::SHAPE_ID
        );

        self::assertEquals(new WidgetFixture('w'), $registry->deserialize($document));
    }

    public function testComposedDeserializeUsesLaterRegistry(): void
    {
        $composed = CompositeTypeRegistry::compose(
            $this->registry([]),
            $this->registry([WidgetFixture::SHAPE_ID => WidgetFixture::class])
        );
        $document = new MapDocument(
            ['count' => AbstractDocument::fromValue(2)],
            null,
            WidgetFixture::SHAPE_ID
        );

        self::assertEquals(new WidgetFixture(count: 2), $composed->deserialize($document));
    }

    public function testDeserializeRejectsDocumentWithoutDiscriminator(): void
    {
        $this->expectException(InvalidShapeValueException::class);
        $this->expectExceptionMessage('no discriminator');

        $this->registry([])->deserialize(new MapDocument([]));
    }

    public function testDeserializeRejectsUnregisteredDiscriminator(): void
    {
        $this->expectException(InvalidShapeValueException::class);
        $this->expectExceptionMessage('smithy.test#Other');

        $this->registry([])->deserialize(
            new MapDocument([], null, 'smithy.test#Other')
        );
    }

    public function testRuntimeRegistryImplementsInterfaceWithoutClasses(): void
    {
        $model = SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => ['example#S' => ['type' => 'structure']]
        ]);
        $registry = new TypeRegistry($model, new SchemaConverter($model));

        self::assertInstanceOf(TypeRegistryInterface::class, $registry);
        self::assertNull($registry->classFor('example#S'));

        $this->expectException(InvalidShapeValueException::class);
        $registry->deserialize(new MapDocument([], null, 'example#S'));
    }

    /**
     * @param array<string, class-string> $classes
     */
    private function registry(array $classes): TypeRegistryInterface
    {
        return new class($classes) implements TypeRegistryInterface {
            use TypeRegistryDeserializeTrait;

            /** @param array<string, class-string> $classes */
            public function __construct(private readonly array $classes) {}

            public function schemaFor(string $shapeId): ?Schema
            {
                $class = $this->classes[$shapeId] ?? null;

                return $class !== null && \method_exists($class, 'schema')
                    ? $class::schema()
                    : null;
            }

            public function classFor(string $shapeId): ?string
            {
                return $this->classes[$shapeId] ?? null;
            }
        };
    }
}
