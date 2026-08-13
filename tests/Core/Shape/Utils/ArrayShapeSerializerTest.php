<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Utils;

use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Serde\ShapeType;
use Smithy\Core\Shape\BigInteger;
use Smithy\Core\Shape\Document\AbstractDocument;
use Smithy\Core\Shape\Document\IntegerDocument;
use Smithy\Core\Shape\Document\StringDocument;
use Smithy\Core\Shape\Utils\ArrayShapeSerializer;

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
        $this->expectException(\InvalidArgumentException::class);
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
}

abstract class AbstractArrayShapeSerializerFixture implements SerializableStructInterface
{
    public function serialize(ShapeSerializerInterface $serializer, int $idx): void
    {
        // Not exercised by these tests — ArrayShapeSerializer drives the
        // schema-walk path, not the codec dispatch path.
    }

    public function serializeMembers(ShapeSerializerInterface $serializer): void {}

    public function getMemberValue(Schema $member): mixed
    {
        return null;
    }

    public static function fromMembers(array $fields): static
    {
        throw new \BadMethodCallException('Not used in these tests.');
    }
}

final class ArrayShapeSerializerScalarFixture extends AbstractArrayShapeSerializerFixture
{
    private static Schema $SCHEMA;

    public function __construct(
        public readonly string $name,
        public readonly int $count,
        public readonly ?bool $active = null
    )
    {
    }

    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: 'test#ArrayShapeSerializerScalarFixture',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.api#String',
                    type: ShapeType::String_,
                    memberName: 'name',
                    memberIndex: 0
                ),
                new Schema(
                    id: 'smithy.api#Integer',
                    type: ShapeType::Integer,
                    memberName: 'count',
                    memberIndex: 1
                ),
                new Schema(
                    id: 'smithy.api#Boolean',
                    type: ShapeType::Boolean,
                    memberName: 'active',
                    memberIndex: 2
                )
            ],
            targetClass: self::class
        );
    }
}

final class ArrayShapeSerializerBigNumberFixture extends AbstractArrayShapeSerializerFixture
{
    private static Schema $SCHEMA;

    public function __construct(public readonly BigInteger $big) {}

    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: 'test#ArrayShapeSerializerBigNumberFixture',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.api#BigInteger',
                    type: ShapeType::BigInteger,
                    memberName: 'big',
                    memberIndex: 0
                )
            ],
            targetClass: self::class
        );
    }
}

final class ArrayShapeSerializerListFixture extends AbstractArrayShapeSerializerFixture
{
    private static Schema $SCHEMA;

    /**
     * @param list<BigInteger> $big
     */
    public function __construct(public readonly array $big) {}

    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: 'test#ArrayShapeSerializerListFixture',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'test#bigList',
                    type: ShapeType::List_,
                    memberName: 'big',
                    memberIndex: 0,
                    listMember: new Schema(
                        id: 'smithy.api#BigInteger',
                        type: ShapeType::BigInteger
                    )
                )
            ],
            targetClass: self::class
        );
    }
}

final class ArrayShapeSerializerFloatFixture extends AbstractArrayShapeSerializerFixture
{
    private static Schema $SCHEMA;

    public function __construct(public readonly float $v) {}

    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: 'test#ArrayShapeSerializerFloatFixture',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.api#Float',
                    type: ShapeType::Float_,
                    memberName: 'v',
                    memberIndex: 0
                )
            ],
            targetClass: self::class
        );
    }
}

final class ArrayShapeSerializerTimestampFixture extends AbstractArrayShapeSerializerFixture
{
    private static Schema $SCHEMA;

    public function __construct(public readonly float $t) {}

    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: 'test#ArrayShapeSerializerTimestampFixture',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.api#Timestamp',
                    type: ShapeType::Timestamp,
                    memberName: 't',
                    memberIndex: 0
                )
            ],
            targetClass: self::class
        );
    }
}

final class ArrayShapeSerializerV3CompatFixture extends AbstractArrayShapeSerializerFixture
{
    private static Schema $SCHEMA;

    public function __construct(
        public readonly string $name,
        public readonly int $count
    )
    {
    }

    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: 'test#ArrayShapeSerializerV3CompatFixture',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.api#String',
                    type: ShapeType::String_,
                    memberName: 'name',
                    memberIndex: 0
                ),
                new Schema(
                    id: 'smithy.api#Integer',
                    type: ShapeType::Integer,
                    memberName: 'count',
                    memberIndex: 1
                )
            ],
            targetClass: self::class
        );
    }
}

final class ArrayShapeSerializerDocumentFixture extends AbstractArrayShapeSerializerFixture
{
    private static Schema $SCHEMA;

    public function __construct(public readonly ?AbstractDocument $doc = null) {}

    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: 'test#ArrayShapeSerializerDocumentFixture',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.api#Document',
                    type: ShapeType::Document,
                    memberName: 'doc',
                    memberIndex: 0
                )
            ],
            targetClass: self::class
        );
    }
}
