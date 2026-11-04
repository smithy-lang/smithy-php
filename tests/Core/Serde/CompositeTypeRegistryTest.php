<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Serde;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Model\SmithyModel;
use Smithy\Core\Serde\CompositeTypeRegistry;
use Smithy\Core\Serde\SchemaConverter;
use Smithy\Core\Serde\TypeRegistry;
use Smithy\Core\Shape\Document\StructDocument;

#[CoversClass(CompositeTypeRegistry::class)]
final class CompositeTypeRegistryTest extends TestCase
{
    public function testResolvesAndCreatesFromLaterRegistry(): void
    {
        $first = $this->registry('example#First', 'first');
        $second = $this->registry('example#Second', 'second');
        $registry = new CompositeTypeRegistry($first, $second);
        $schema = $second->schemaFor('example#Second');
        self::assertNotNull($schema);

        self::assertSame(
            $first->schemaFor('example#First'),
            $registry->schemaFor('example#First')
        );

        $result = $registry->deserialize(
            StructDocument::fromMembers($schema, ['value'])
        );

        self::assertInstanceOf(StructDocument::class, $result);
        self::assertSame(
            'value',
            $result->getMemberValue($schema->getMemberByIndex(0))
        );
    }

    public function testFirstRegistryOwnsDuplicateShapeId(): void
    {
        $first = $this->registry('example#Shared', 'first');
        $second = $this->registry('example#Shared', 'second');
        $registry = new CompositeTypeRegistry($first, $second);

        self::assertSame(
            ['first'],
            \array_keys(
                $registry->schemaFor('example#Shared')?->getMembers() ?? []
            )
        );
    }

    private function registry(string $shapeId, string $member): TypeRegistry
    {
        $model = SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => [
                $shapeId => [
                    'type' => 'structure',
                    'members' => [
                        $member => ['target' => 'smithy.api#String']
                    ]
                ]
            ]
        ]);

        return new TypeRegistry($model, new SchemaConverter($model));
    }
}
