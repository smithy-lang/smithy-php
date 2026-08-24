<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Utils;

use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\BigInteger;
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\Exception\InvalidShapeValueException;
use Smithy\Core\Shape\Document\{
    IntegerDocument,
    StringDocument,
    StructDocument
};
use Smithy\Core\Shape\Utils\ArrayShapeSerializer;
use Smithy\Tests\Core\Shape\Utils\Utils\{
    ArrayShapeSerializerAliasedFixture,
    ArrayShapeSerializerBigNumberFixture,
    ArrayShapeSerializerDocumentFixture,
    ArrayShapeSerializerEmptyFixture,
    ArrayShapeSerializerFloatFixture,
    ArrayShapeSerializerListFixture,
    ArrayShapeSerializerScalarFixture,
    ArrayShapeSerializerTimestampFixture,
    ArrayShapeSerializerUnionFixture,
    ArrayShapeSerializerUnionHolderFixture,
    ArrayShapeSerializerV3CompatFixture
};

final class ArrayShapeSerializerTest extends TestCase
{
    public function testToArrayPassesThroughScalars(): void
    {
        $obj = new ArrayShapeSerializerScalarFixture(
            name: 'sam',
            count: 42,
            active: true
        );
        $this->assertSame(
            ['name' => 'sam', 'count' => 42, 'active' => true],
            ArrayShapeSerializer::toArray($obj)
        );
    }

    public function testToArraySkipsAbsentOptionals(): void
    {
        $obj = new ArrayShapeSerializerScalarFixture(name: 'sam', count: 42);
        $this->assertSame(
            ['name' => 'sam', 'count' => 42],
            ArrayShapeSerializer::toArray($obj)
        );
    }

    public function testToArrayUsesModeledMemberAccessor(): void
    {
        $obj = new ArrayShapeSerializerAliasedFixture(message: 'Hi');

        $this->assertSame(
            ['Message' => 'Hi'],
            ArrayShapeSerializer::toArray($obj)
        );
    }

    public function testToArrayResolvesReferencedAggregateSchemas(): void
    {
        $itemSchema = new Schema(
            id: 'example#Item',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.api#String',
                    type: ShapeType::String_,
                    memberName: 'name',
                    memberIndex: 0
                )
            ]
        );
        $listSchema = new Schema(
            id: 'example#Items',
            type: ShapeType::List_,
            listMember: new Schema(
                id: $itemSchema->id,
                type: ShapeType::Structure,
                target: $itemSchema
            )
        );
        $containerSchema = new Schema(
            id: 'example#Container',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: $listSchema->id,
                    type: ShapeType::List_,
                    memberName: 'items',
                    memberIndex: 0,
                    target: $listSchema
                )
            ]
        );
        $item = StructDocument::fromMembers($itemSchema, ['one']);
        $container = StructDocument::fromMembers(
            $containerSchema,
            [[$item]]
        );

        self::assertSame(
            ['items' => [['name' => 'one']]],
            ArrayShapeSerializer::toArray($container)
        );
    }

    public function testFromArrayHydratesScalars(): void
    {
        /** @var ArrayShapeSerializerScalarFixture $obj */
        $obj = ArrayShapeSerializer::fromArray(
            ArrayShapeSerializerScalarFixture::class,
            ['name' => 'sam', 'count' => 42, 'active' => true]
        );
        $this->assertSame('sam', $obj->name);
        $this->assertSame(42, $obj->count);
        $this->assertTrue($obj->active);
    }

    public function testFromArrayUsesConstructorDefaultForMissingOptional(): void
    {
        /** @var ArrayShapeSerializerScalarFixture $obj */
        $obj = ArrayShapeSerializer::fromArray(
            ArrayShapeSerializerScalarFixture::class,
            ['name' => 'sam', 'count' => 1]
        );
        $this->assertNull($obj->active);
    }

    public function testFromArrayPreservesNullValuePassedExplicitly(): void
    {
        /** @var ArrayShapeSerializerScalarFixture $obj */
        $obj = ArrayShapeSerializer::fromArray(
            ArrayShapeSerializerScalarFixture::class,
            ['name' => 'sam', 'count' => 1, 'active' => null]
        );
        $this->assertNull($obj->active);
    }

    public function testFromArrayRejectsUnsupportedClass(): void
    {
        $this->expectException(InvalidShapeValueException::class);
        $this->expectExceptionMessage(
            'ArrayShapeSerializer::fromArray() expects a class implementing '
            . 'Smithy\Core\Serde\SerializableStructInterface or '
            . 'Smithy\Core\Shape\UnionInterface; got '
            . 'Smithy\Core\Shape\Document\StringDocument.'
        );

        ArrayShapeSerializer::fromArray(StringDocument::class, []);
    }

    public function testFromArraySupportsEmptyStructSchema(): void
    {
        $obj = ArrayShapeSerializer::fromArray(
            ArrayShapeSerializerEmptyFixture::class,
            []
        );

        $this->assertInstanceOf(ArrayShapeSerializerEmptyFixture::class, $obj);
    }

    public function testRoundTripRetainsValues(): void
    {
        $original = ['name' => 'sam', 'count' => 42, 'active' => false];
        $obj = ArrayShapeSerializer::fromArray(
            ArrayShapeSerializerScalarFixture::class,
            $original
        );
        $this->assertSame($original, ArrayShapeSerializer::toArray($obj));
    }

    public function testToArrayUnwrapsBigInteger(): void
    {
        $obj = new ArrayShapeSerializerBigNumberFixture(
            big: new BigInteger('99999999999999999999')
        );
        $this->assertSame(
            ['big' => '99999999999999999999'],
            ArrayShapeSerializer::toArray($obj)
        );
    }

    public function testFromArrayWrapsStringIntoBigInteger(): void
    {
        /** @var ArrayShapeSerializerBigNumberFixture $obj */
        $obj = ArrayShapeSerializer::fromArray(
            ArrayShapeSerializerBigNumberFixture::class,
            ['big' => '12345']
        );
        $this->assertInstanceOf(BigInteger::class, $obj->big);
        $this->assertSame('12345', $obj->big->value);
    }

    public function testFromArrayPassesThroughBigIntegerInstance(): void
    {
        $bi = new BigInteger('12345');
        /** @var ArrayShapeSerializerBigNumberFixture $obj */
        $obj = ArrayShapeSerializer::fromArray(
            ArrayShapeSerializerBigNumberFixture::class,
            ['big' => $bi]
        );
        $this->assertSame($bi, $obj->big);
    }

    public function testToArrayUnwrapsListOfBigIntegers(): void
    {
        $obj = new ArrayShapeSerializerListFixture(
            big: [new BigInteger('1'), new BigInteger('2'), new BigInteger('3')]
        );
        $this->assertSame(
            ['big' => ['1', '2', '3']],
            ArrayShapeSerializer::toArray($obj)
        );
    }

    public function testFromArrayHydratesListOfBigIntegers(): void
    {
        /** @var ArrayShapeSerializerListFixture $obj */
        $obj = ArrayShapeSerializer::fromArray(
            ArrayShapeSerializerListFixture::class,
            ['big' => ['1', '2', '3']]
        );
        $this->assertCount(3, $obj->big);
        $this->assertSame('2', $obj->big[1]->value);
    }

    public function testFromArrayCoercesFloatNanInfinityFromString(): void
    {
        /** @var ArrayShapeSerializerFloatFixture $obj */
        $obj = ArrayShapeSerializer::fromArray(
            ArrayShapeSerializerFloatFixture::class,
            ['v' => 'NaN']
        );
        $this->assertNan($obj->v);

        /** @var ArrayShapeSerializerFloatFixture $obj */
        $obj = ArrayShapeSerializer::fromArray(
            ArrayShapeSerializerFloatFixture::class,
            ['v' => 'Infinity']
        );
        $this->assertSame(\INF, $obj->v);

        /** @var ArrayShapeSerializerFloatFixture $obj */
        $obj = ArrayShapeSerializer::fromArray(
            ArrayShapeSerializerFloatFixture::class,
            ['v' => '-Infinity']
        );
        $this->assertSame(-\INF, $obj->v);
    }

    public function testFromArrayConvertsDateTimeImmutableToEpoch(): void
    {
        $dt = new \DateTimeImmutable('@1700000000');
        /** @var ArrayShapeSerializerTimestampFixture $obj */
        $obj = ArrayShapeSerializer::fromArray(
            ArrayShapeSerializerTimestampFixture::class,
            ['t' => $dt]
        );
        $this->assertSame(1700000000.0, $obj->t);
    }

    public function testFromArrayConvertsIso8601StringToEpoch(): void
    {
        /** @var ArrayShapeSerializerTimestampFixture $obj */
        $obj = ArrayShapeSerializer::fromArray(
            ArrayShapeSerializerTimestampFixture::class,
            ['t' => '2024-01-01T00:00:00Z']
        );
        $this->assertSame(1704067200.0, $obj->t);
    }

    public function testFromArrayThrowsWhenTimestampStringCannotBeParsed(): void
    {
        $this->expectException(InvalidShapeValueException::class);
        $this->expectExceptionMessage(
            "Invalid timestamp value 'not-a-timestamp' for schema 't'."
        );

        ArrayShapeSerializer::fromArray(
            ArrayShapeSerializerTimestampFixture::class,
            ['t' => 'not-a-timestamp']
        );
    }

    public function testFromArrayCoercesNumericStringIntoIntegerMember(): void
    {
        /** @var ArrayShapeSerializerV3CompatFixture $obj */
        $obj = ArrayShapeSerializer::fromArray(
            ArrayShapeSerializerV3CompatFixture::class,
            ['name' => 'sam', 'count' => '42']
        );
        $this->assertSame(42, $obj->count);
    }

    public function testFromArrayCoercesIntoStringMember(): void
    {
        /** @var ArrayShapeSerializerV3CompatFixture $obj */
        $obj = ArrayShapeSerializer::fromArray(
            ArrayShapeSerializerV3CompatFixture::class,
            ['name' => 12345, 'count' => 1]
        );
        $this->assertSame('12345', $obj->name);
    }

    public function testFromArrayCoercesStringableObjectIntoStringMember(): void
    {
        $stringable = new class () {
            public function __toString(): string
            {
                return 'sam';
            }
        };
        /** @var ArrayShapeSerializerV3CompatFixture $obj */
        $obj = ArrayShapeSerializer::fromArray(
            ArrayShapeSerializerV3CompatFixture::class,
            ['name' => $stringable, 'count' => 1]
        );
        $this->assertSame('sam', $obj->name);
    }

    public function testFromArrayWrapsScalarIntoDocumentMember(): void
    {
        /** @var ArrayShapeSerializerDocumentFixture $obj */
        $obj = ArrayShapeSerializer::fromArray(
            ArrayShapeSerializerDocumentFixture::class,
            ['doc' => 'hello']
        );
        $this->assertInstanceOf(StringDocument::class, $obj->doc);
        $this->assertSame('hello', $obj->doc->asString());
    }

    public function testFromArrayWrapsIntegerIntoDocumentMember(): void
    {
        /** @var ArrayShapeSerializerDocumentFixture $obj */
        $obj = ArrayShapeSerializer::fromArray(
            ArrayShapeSerializerDocumentFixture::class,
            ['doc' => 42]
        );
        $this->assertInstanceOf(IntegerDocument::class, $obj->doc);
        $this->assertSame(42, $obj->doc->asInteger());
    }

    public function testFromArrayPassesThroughDocumentInstance(): void
    {
        $doc = new StringDocument('hello');
        /** @var ArrayShapeSerializerDocumentFixture $obj */
        $obj = ArrayShapeSerializer::fromArray(
            ArrayShapeSerializerDocumentFixture::class,
            ['doc' => $doc]
        );
        $this->assertSame($doc, $obj->doc);
    }

    public function testFromArrayHydratesRootUnionVariant(): void
    {
        /** @var ArrayShapeSerializerUnionFixture $union */
        $union = ArrayShapeSerializer::fromArray(
            ArrayShapeSerializerUnionFixture::class,
            ['text' => 'hello']
        );

        $this->assertSame('text', $union->tag());
        $this->assertSame('hello', $union->value());
    }

    public function testToArrayUnwrapsRootUnionStructVariant(): void
    {
        $union = ArrayShapeSerializerUnionFixture::scalar(
            new ArrayShapeSerializerScalarFixture(name: 'sam', count: 42)
        );

        $this->assertSame(
            ['scalar' => ['name' => 'sam', 'count' => 42]],
            ArrayShapeSerializer::toArray($union)
        );
    }

    public function testFromArrayHydratesNestedUnionMember(): void
    {
        /** @var ArrayShapeSerializerUnionHolderFixture $obj */
        $obj = ArrayShapeSerializer::fromArray(
            ArrayShapeSerializerUnionHolderFixture::class,
            ['choice' => ['scalar' => ['name' => 'sam', 'count' => 42]]]
        );

        $this->assertSame('scalar', $obj->choice->tag());
        $this->assertInstanceOf(
            ArrayShapeSerializerScalarFixture::class,
            $obj->choice->value()
        );
        $this->assertSame('sam', $obj->choice->value()->name);
    }

    public function testToArrayUnwrapsNestedUnionMember(): void
    {
        $obj = new ArrayShapeSerializerUnionHolderFixture(
            ArrayShapeSerializerUnionFixture::scalar(
                new ArrayShapeSerializerScalarFixture(name: 'sam', count: 42)
            )
        );

        $this->assertSame(
            ['choice' => ['scalar' => ['name' => 'sam', 'count' => 42]]],
            ArrayShapeSerializer::toArray($obj)
        );
    }
}
