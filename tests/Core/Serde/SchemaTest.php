<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Serde;

use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeType;

/**
 * Named helper for the `resolveTarget()` recursive-reference test. Schema dispatches to
 * `($targetClass)::schema()` when it holds a lazy stub, so the test needs an actual class with a
 * static `schema()` method, an inline anonymous class doesn't work here because we need its FQCN as
 * a string to hand to the Schema constructor.
 */
final class SchemaTestTarget
{
    public static Schema $canonical;

    public static function schema(): Schema
    {
        return self::$canonical ??= new Schema(
            id: 'test#Target',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'test#Target$leaf',
                    type: ShapeType::String_,
                    memberName: 'leaf',
                    memberIndex: 0,
                ),
            ],
        );
    }
}

/**
 * `Schema` is the load-bearing runtime type: every codec, every generated shape, and every hot-path
 * dispatch reads from it. The BC contract on the class docblock, properties
 * `$id`/`$type`/`$memberName`/`$memberIndex`/ `$targetClass`, plus `getMember*`, `hasTrait`,
 * `getTrait`, `getWireName`, `isRequired`, `resolveTarget`, `withMembers`, is what the tests below
 * pin. Extension slot behavior is separately covered by {@see SchemaExtensionTest} and is
 * intentionally out of scope here.
 */
final class SchemaTest extends TestCase
{
    public function testStoresIdAndUnwrapsShapeTypeToInt(): void
    {
        // `$type` is declared as `int`, not ShapeType, so codec switches
        // can dispatch on a literal-int discriminant. The unwrap happens
        // once, in the constructor.
        $schema = new Schema(id: 'com.example#Foo', type: ShapeType::Structure);

        $this->assertSame('com.example#Foo', $schema->id);
        $this->assertSame(ShapeType::Structure->value, $schema->type);
    }

    public function testRootSchemaIsNotAMember(): void
    {
        $schema = new Schema(
            id: 'com.example#Root',
            type: ShapeType::Structure,
        );

        $this->assertNull($schema->memberName);
        $this->assertNull($schema->memberIndex);
        $this->assertFalse($schema->isMember());
    }

    public function testMemberSchemaCarriesMemberNameAndIndex(): void
    {
        $member = new Schema(
            id: 'com.example#Root$field',
            type: ShapeType::String_,
            memberName: 'field',
            memberIndex: 3,
        );

        $this->assertTrue($member->isMember());
        $this->assertSame('field', $member->memberName);
        $this->assertSame(3, $member->memberIndex);
    }

    public function testMembersAreIndexedByMemberNameNotByListOrder(): void
    {
        // The constructor accepts `$members` in any order and keys by the
        // member's own `memberIndex`, not the foreach position. Pass
        // members deliberately out of positional order to ensure the
        // implementation is really consulting `memberIndex`.
        $first = new Schema(
            id: 'com.example#S$alpha',
            type: ShapeType::String_,
            memberName: 'alpha',
            memberIndex: 0,
        );
        $second = new Schema(
            id: 'com.example#S$beta',
            type: ShapeType::Integer,
            memberName: 'beta',
            memberIndex: 1,
        );

        $schema = new Schema(
            id: 'com.example#S',
            type: ShapeType::Structure,
            members: [$second, $first], // deliberately reversed
        );

        $this->assertSame($first, $schema->getMember('alpha'));
        $this->assertSame($second, $schema->getMember('beta'));
        $this->assertSame($first, $schema->getMemberByIndex(0));
        $this->assertSame($second, $schema->getMemberByIndex(1));
    }

    public function testGetMemberReturnsNullForUnknownName(): void
    {
        $schema = new Schema(
            id: 'com.example#Empty',
            type: ShapeType::Structure,
        );

        $this->assertNull($schema->getMember('missing'));
        $this->assertNull($schema->getMemberByIndex(99));
    }

    public function testGetMembersReturnsNameKeyedMap(): void
    {
        $member = new Schema(
            id: 'com.example#S$only',
            type: ShapeType::String_,
            memberName: 'only',
            memberIndex: 0,
        );
        $schema = new Schema(
            id: 'com.example#S',
            type: ShapeType::Structure,
            members: [$member],
        );

        $this->assertSame(['only' => $member], $schema->getMembers());
        $this->assertSame([0 => $member], $schema->getMembersByIndex());
    }

    public function testMemberCountLazilyReflectsMemberMap(): void
    {
        $schema = new Schema(
            id: 'com.example#Pair',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'a',
                    type: ShapeType::String_,
                    memberName: 'a',
                    memberIndex: 0,
                ),
                new Schema(
                    id: 'b',
                    type: ShapeType::String_,
                    memberName: 'b',
                    memberIndex: 1,
                ),
            ],
        );

        // Memoized on first call.
        $this->assertSame(2, $schema->getMemberCount());
        // Second call must hit the cache and return the same value.
        $this->assertSame(2, $schema->getMemberCount());
    }

    public function testEmptyStructureHasZeroMembers(): void
    {
        $schema = new Schema(
            id: 'com.example#Unit',
            type: ShapeType::Structure,
        );

        $this->assertSame(0, $schema->getMemberCount());
        $this->assertSame([], $schema->getMembers());
    }

    public function testExposesCollectionMemberForListShapes(): void
    {
        $item = new Schema(
            id: 'com.example#StringList$member',
            type: ShapeType::String_,
        );
        $list = new Schema(
            id: 'com.example#StringList',
            type: ShapeType::List_,
            listMember: $item,
        );

        $this->assertSame($item, $list->getListMember());
        $this->assertNull($list->getMapKey());
        $this->assertNull($list->getMapValue());
    }

    public function testExposesMapKeyAndValueSchemas(): void
    {
        $key = new Schema(id: 'com.example#Map$key', type: ShapeType::String_);
        $value = new Schema(
            id: 'com.example#Map$value',
            type: ShapeType::Integer,
        );
        $map = new Schema(
            id: 'com.example#Map',
            type: ShapeType::Map,
            mapKey: $key,
            mapValue: $value,
        );

        $this->assertSame($key, $map->getMapKey());
        $this->assertSame($value, $map->getMapValue());
        $this->assertNull($map->getListMember());
    }

    public function testHasTraitAndGetTraitReadFromIntKeyedPackedArray(): void
    {
        // Traits are keyed by the `T_*` int constants. A codec asks
        // "does this member carry this trait" via `hasTrait($intKey)`
        // and reads the value with `getTrait($intKey)`. Both are
        // O(1) lookups against the packed array.
        $member = new Schema(
            id: 'com.example#S$name',
            type: ShapeType::String_,
            memberName: 'name',
            memberIndex: 0,
            traits: [
                Schema::T_REQUIRED => true,
                Schema::T_JSON_NAME => 'name_out',
                Schema::T_XML_ATTRIBUTE => true,
            ],
        );

        $this->assertTrue($member->hasTrait(Schema::T_REQUIRED));
        $this->assertTrue($member->hasTrait(Schema::T_JSON_NAME));
        $this->assertTrue($member->hasTrait(Schema::T_XML_ATTRIBUTE));
        $this->assertFalse($member->hasTrait(Schema::T_HTTP_HEADER));

        $this->assertSame('name_out', $member->getTrait(Schema::T_JSON_NAME));
        $this->assertNull($member->getTrait(Schema::T_HTTP_HEADER));
    }

    public function testGetTraitsReturnsWholePackedTable(): void
    {
        $traits = [
            Schema::T_REQUIRED => true,
            Schema::T_SENSITIVE => true,
        ];
        $schema = new Schema(
            id: 'com.example#S',
            type: ShapeType::String_,
            traits: $traits,
        );

        $this->assertSame($traits, $schema->getTraits());
    }

    public function testIsRequiredReadsFromRequiredTraitSlot(): void
    {
        $required = new Schema(
            id: 'com.example#S$r',
            type: ShapeType::String_,
            memberName: 'r',
            memberIndex: 0,
            traits: [Schema::T_REQUIRED => true],
        );
        $optional = new Schema(
            id: 'com.example#S$o',
            type: ShapeType::String_,
            memberName: 'o',
            memberIndex: 1,
        );

        $this->assertTrue($required->isRequired());
        $this->assertFalse($optional->isRequired());
    }

    public function testWireNamePrefersJsonNameOverMemberName(): void
    {
        $member = new Schema(
            id: 'com.example#S$foo',
            type: ShapeType::String_,
            memberName: 'foo',
            memberIndex: 0,
            traits: [Schema::T_JSON_NAME => 'Foo'],
        );

        $this->assertSame('Foo', $member->getWireName());
    }

    public function testWireNameFallsBackToMemberNameWithoutJsonName(): void
    {
        $member = new Schema(
            id: 'com.example#S$foo',
            type: ShapeType::String_,
            memberName: 'foo',
            memberIndex: 0,
        );

        $this->assertSame('foo', $member->getWireName());
    }

    public function testWireNameReturnsEmptyStringForRootSchemaWithNoJsonName(): void
    {
        // A root shape has no memberName, without an @jsonName trait,
        // `getWireName()` returns '' rather than raising. Codecs are
        // responsible for never asking a root schema for a wire name,
        // but the guard is still worth pinning.
        $root = new Schema(
            id: 'com.example#Root',
            type: ShapeType::Structure,
        );

        $this->assertSame('', $root->getWireName());
    }

    public function testResolveTargetReturnsSelfForFullyPopulatedSchema(): void
    {
        // No `targetClass` means this is not a recursive stub, the
        // schema is its own resolution.
        $schema = new Schema(id: 'com.example#Leaf', type: ShapeType::String_);

        $this->assertSame($schema, $schema->resolveTarget());
    }

    public function testResolveTargetDispatchesToTargetClassSchema(): void
    {
        // A stub schema references its target by FQCN. `resolveTarget()`
        // calls `($targetClass)::schema()` to get the fully-populated
        // graph. This is what breaks the cycle in recursive shape
        // references, the stub sits in the constructor, the real
        // graph is fetched on demand.
        $stub = new Schema(
            id: 'test#TargetRef',
            type: ShapeType::Structure,
            targetClass: SchemaTestTarget::class,
        );

        $resolved = $stub->resolveTarget();

        $this->assertSame(SchemaTestTarget::schema(), $resolved);
        $this->assertSame('test#Target', $resolved->id);
        $this->assertNotNull($resolved->getMember('leaf'));
    }

    public function testWithMembersReturnsFilteredCopy(): void
    {
        // Protocol layer uses this to build a body-only schema, 
        // strips HTTP-bound members while keeping every other property
        // intact. The result is a fresh Schema, not a mutation.
        $a = new Schema(
            id: 'com.example#S$a',
            type: ShapeType::String_,
            memberName: 'a',
            memberIndex: 0,
        );
        $b = new Schema(
            id: 'com.example#S$b',
            type: ShapeType::String_,
            memberName: 'b',
            memberIndex: 1,
        );
        $c = new Schema(
            id: 'com.example#S$c',
            type: ShapeType::String_,
            memberName: 'c',
            memberIndex: 2,
        );

        $full = new Schema(
            id: 'com.example#S',
            type: ShapeType::Structure,
            members: [$a, $b, $c],
            traits: [Schema::T_REQUIRED => true],
        );

        $filtered = $full->withMembers([0, 2]);

        $this->assertNotSame($full, $filtered);
        $this->assertSame($full->id, $filtered->id);
        $this->assertSame($full->type, $filtered->type);
        // Only the requested indices survived.
        $this->assertSame(['a' => $a, 'c' => $c], $filtered->getMembers());
        $this->assertSame($a, $filtered->getMemberByIndex(0));
        $this->assertNull($filtered->getMemberByIndex(1));
        $this->assertSame($c, $filtered->getMemberByIndex(2));
        // Traits are copied through, filtering the body must not
        // silently drop @required/@sensitive/etc.
        $this->assertSame([Schema::T_REQUIRED => true], $filtered->getTraits());
    }

    public function testWithMembersSilentlySkipsUnknownIndices(): void
    {
        // Requesting an index that isn't in the source schema is a
        // no-op for that index, the filter is a "keep these" operation,
        // not a "these must exist" invariant.
        $only = new Schema(
            id: 'com.example#S$only',
            type: ShapeType::String_,
            memberName: 'only',
            memberIndex: 0,
        );
        $full = new Schema(
            id: 'com.example#S',
            type: ShapeType::Structure,
            members: [$only],
        );

        $filtered = $full->withMembers([0, 5, 99]);

        $this->assertSame(['only' => $only], $filtered->getMembers());
    }
}
