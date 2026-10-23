<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape;

use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;
use Smithy\Tests\Core\Shape\Utils\SensitiveStructFixture;
use Smithy\Tests\Core\Shape\Utils\SensitiveUnionFixture;

final class SensitiveStringableTraitTest extends TestCase
{
    public function testRedactsSensitiveMembersFromDebugOutput(): void
    {
        $value = new SensitiveStructFixture(
            $this->structureSchema([
                $this->member('visible', 0),
                $this->member('secret', 1, true)
            ]),
            [
                'visible' => 'public-value',
                'secret' => 'member-secret'
            ]
        );

        $this->assertSame(
            [
                'visible' => 'public-value',
                'secret' => '***REDACTED***'
            ],
            $value->__debugInfo()
        );

        foreach ([
            (string) $value,
            $this->printValue($value),
            $this->dumpValue($value),
            \json_encode($value, JSON_THROW_ON_ERROR),
            \serialize($value)
        ] as $output) {
            $this->assertStringContainsString('public-value', $output);
            $this->assertStringContainsString('***REDACTED***', $output);
            $this->assertStringNotContainsString('member-secret', $output);
        }
    }

    public function testRedactsNestedStructuresUnionsAndCollections(): void
    {
        $nested = new SensitiveStructFixture(
            $this->structureSchema([
                $this->member('secret', 0, true)
            ], 'test#Nested'),
            ['secret' => 'nested-secret']
        );
        $unionSchema = new Schema(
            id: 'test#Choice',
            type: ShapeType::Union,
            members: [
                $this->member('visible', 0),
                $this->member('secret', 1, true)
            ]
        );
        $union = new SensitiveUnionFixture(
            $unionSchema,
            'secret',
            1,
            'union-secret'
        );
        $listMember = new Schema(
            id: 'test#SecretList',
            type: ShapeType::List_,
            memberName: 'secrets',
            memberIndex: 1,
            listMember: $this->member('member', 0, true)
        );
        $mapMember = new Schema(
            id: 'test#SecretMap',
            type: ShapeType::Map,
            memberName: 'secretMap',
            memberIndex: 2,
            mapKey: $this->member('key', 0),
            mapValue: $this->member('value', 1, true)
        );
        $unionMember = new Schema(
            id: 'test#Choice',
            type: ShapeType::Union,
            memberName: 'choice',
            memberIndex: 3
        );
        $nestedMember = new Schema(
            id: 'test#Nested',
            type: ShapeType::Structure,
            memberName: 'nested',
            memberIndex: 0
        );
        $value = new SensitiveStructFixture(
            $this->structureSchema([
                $nestedMember,
                $listMember,
                $mapMember,
                $unionMember
            ]),
            [
                'nested' => $nested,
                'secrets' => ['list-secret'],
                'secretMap' => ['safe-key' => 'map-secret'],
                'choice' => $union
            ]
        );

        foreach ([
            $this->printValue($value),
            $this->dumpValue($value),
            \json_encode($value, JSON_THROW_ON_ERROR),
            \serialize($value)
        ] as $output) {
            $this->assertStringNotContainsString('nested-secret', $output);
            $this->assertStringNotContainsString('list-secret', $output);
            $this->assertStringNotContainsString('map-secret', $output);
            $this->assertStringNotContainsString('union-secret', $output);
            $this->assertStringContainsString('safe-key', $output);
        }
    }

    public function testShapeSensitivityRedactsEveryMember(): void
    {
        $value = new SensitiveStructFixture(
            $this->structureSchema(
                [
                    $this->member('first', 0),
                    $this->member('second', 1)
                ],
                traits: [Schema::T_SENSITIVE => true]
            ),
            [
                'first' => 'first-secret',
                'second' => 'second-secret'
            ]
        );

        $this->assertSame(
            [
                'first' => '***REDACTED***',
                'second' => '***REDACTED***'
            ],
            $value->__debugInfo()
        );
        $this->assertStringNotContainsString(
            'first-secret',
            (string) $value
        );
        $this->assertStringNotContainsString(
            'second-secret',
            (string) $value
        );
    }

    public function testSensitiveMapKeysRedactTheEntireMap(): void
    {
        $mapMember = new Schema(
            id: 'test#SecretKeyMap',
            type: ShapeType::Map,
            memberName: 'values',
            memberIndex: 0,
            mapKey: $this->member('key', 0, true),
            mapValue: $this->member('value', 1)
        );
        $value = new SensitiveStructFixture(
            $this->structureSchema([$mapMember]),
            ['values' => ['secret-key' => 'visible-value']]
        );

        $output = $this->printValue($value);
        $this->assertStringContainsString('***REDACTED***', $output);
        $this->assertStringNotContainsString('secret-key', $output);
        $this->assertStringNotContainsString('visible-value', $output);
    }

    public function testUnknownUnionValueIsRedacted(): void
    {
        $schema = new Schema(
            id: 'test#Choice',
            type: ShapeType::Union,
            members: [$this->member('secret', 0, true)]
        );
        $value = new SensitiveUnionFixture(
            $schema,
            'future',
            -1,
            'unknown-secret'
        );

        $this->assertSame(
            ['tag' => 'future', 'value' => '***REDACTED***'],
            $value->__debugInfo()
        );
        $this->assertStringNotContainsString(
            'unknown-secret',
            $this->dumpValue($value)
        );
    }

    public function testNonSensitiveUnionVariantRemainsVisible(): void
    {
        $schema = new Schema(
            id: 'test#Choice',
            type: ShapeType::Union,
            members: [
                $this->member('visible', 0),
                $this->member('secret', 1, true)
            ]
        );
        $value = new SensitiveUnionFixture(
            $schema,
            'visible',
            0,
            'public-value'
        );

        $this->assertSame(
            ['tag' => 'visible', 'value' => 'public-value'],
            $value->__debugInfo()
        );
    }

    public function testRejectsSerializedRestoration(): void
    {
        $value = new SensitiveStructFixture(
            $this->structureSchema([
                $this->member('secret', 0, true)
            ]),
            ['secret' => 'member-secret']
        );
        $serialized = \serialize($value);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('cannot be restored');

        \unserialize($serialized);
    }

    public function testDebugRedactionHasADepthLimit(): void
    {
        $itemSchema = new Schema(
            id: 'smithy.api#String',
            type: ShapeType::String_
        );
        $nestedValue = 'leaf';
        for ($index = 0; $index < 65; $index++) {
            $itemSchema = new Schema(
                id: "test#List{$index}",
                type: ShapeType::List_,
                listMember: $itemSchema
            );
            $nestedValue = [$nestedValue];
        }
        $member = new Schema(
            id: 'test#DeepList',
            type: ShapeType::List_,
            memberName: 'deep',
            memberIndex: 0,
            listMember: $itemSchema->getListMember()
        );
        $value = new SensitiveStructFixture(
            $this->structureSchema([$member]),
            ['deep' => $nestedValue]
        );

        $this->assertStringContainsString(
            '<maximum debug depth reached>',
            $this->printValue($value)
        );
    }

    /**
     * @param list<Schema> $members
     * @param array<int, mixed> $traits
     */
    private function structureSchema(
        array $members,
        string $id = 'test#Container',
        array $traits = []
    ): Schema
    {
        return new Schema(
            id: $id,
            type: ShapeType::Structure,
            members: $members,
            traits: $traits
        );
    }

    private function member(
        string $name,
        int $index,
        bool $sensitive = false
    ): Schema
    {
        return new Schema(
            id: 'smithy.api#String',
            type: ShapeType::String_,
            memberName: $name,
            memberIndex: $index,
            traits: $sensitive ? [Schema::T_SENSITIVE => true] : []
        );
    }

    private function printValue(mixed $value): string
    {
        return \print_r($value, true);
    }

    private function dumpValue(mixed $value): string
    {
        \ob_start();
        \var_dump($value);

        return (string) \ob_get_clean();
    }
}
