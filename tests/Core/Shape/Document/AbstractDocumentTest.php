<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Document;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeType;
use Smithy\Core\Shape\BigDecimal;
use Smithy\Core\Shape\BigInteger;
use Smithy\Core\Shape\Document\AbstractDocument;
use Smithy\Core\Shape\Document\BigDecimalDocument;
use Smithy\Core\Shape\Document\BigIntegerDocument;
use Smithy\Core\Shape\Document\BlobDocument;
use Smithy\Core\Shape\Document\BlobValue;
use Smithy\Core\Shape\Document\BooleanDocument;
use Smithy\Core\Shape\Document\ByteDocument;
use Smithy\Core\Shape\Document\DoubleDocument;
use Smithy\Core\Shape\Document\FloatDocument;
use Smithy\Core\Shape\Document\IntegerDocument;
use Smithy\Core\Shape\Document\ListDocument;
use Smithy\Core\Shape\Document\LongDocument;
use Smithy\Core\Shape\Document\MapDocument;
use Smithy\Core\Shape\Document\ShortDocument;
use Smithy\Core\Shape\Document\StringDocument;
use Smithy\Core\Shape\Document\TimestampDocument;
use Smithy\Core\Shape\Document\TimestampValue;

/**
 * Covers `AbstractDocument`'s factory routing, `fromValue()` recursive coercion, schema
 * attachment, and unsupported-type rejection. Per-concrete accessor and serialization behavior
 * lives in each concrete's own test file.
 */
#[CoversClass(AbstractDocument::class)]
final class AbstractDocumentTest extends TestCase
{
    public function testFromStringReturnsStringDocument(): void
    {
        $this->assertInstanceOf(
            StringDocument::class,
            AbstractDocument::fromString('hello')
        );
    }

    public function testFromBooleanReturnsBooleanDocument(): void
    {
        $this->assertInstanceOf(
            BooleanDocument::class,
            AbstractDocument::fromBoolean(true)
        );
    }

    public function testFromIntegerReturnsIntegerDocument(): void
    {
        $this->assertInstanceOf(
            IntegerDocument::class,
            AbstractDocument::fromInteger(42)
        );
    }

    public function testFromLongReturnsLongDocument(): void
    {
        $this->assertInstanceOf(
            LongDocument::class,
            AbstractDocument::fromLong(2_147_483_648)
        );
    }

    public function testFromShortReturnsShortDocument(): void
    {
        $this->assertInstanceOf(
            ShortDocument::class,
            AbstractDocument::fromShort(100)
        );
    }

    public function testFromByteReturnsByteDocument(): void
    {
        $this->assertInstanceOf(
            ByteDocument::class,
            AbstractDocument::fromByte(7)
        );
    }

    public function testFromFloatReturnsFloatDocument(): void
    {
        $this->assertInstanceOf(
            FloatDocument::class,
            AbstractDocument::fromFloat(3.14)
        );
    }

    public function testFromDoubleReturnsDoubleDocument(): void
    {
        $this->assertInstanceOf(
            DoubleDocument::class,
            AbstractDocument::fromDouble(2.71828)
        );
    }

    public function testFromBigIntegerReturnsBigIntegerDocument(): void
    {
        $doc = AbstractDocument::fromBigInteger(
            new BigInteger('12345678901234567890')
        );
        $this->assertInstanceOf(BigIntegerDocument::class, $doc);
    }

    public function testFromBigDecimalReturnsBigDecimalDocument(): void
    {
        $doc = AbstractDocument::fromBigDecimal(
            new BigDecimal('3.14159265358979323846')
        );
        $this->assertInstanceOf(BigDecimalDocument::class, $doc);
    }

    public function testFromBlobReturnsBlobDocument(): void
    {
        $this->assertInstanceOf(
            BlobDocument::class,
            AbstractDocument::fromBlob("\x00\x01\x02")
        );
    }

    public function testFromBlobAcceptsBlobValue(): void
    {
        $doc = AbstractDocument::fromBlob(new BlobValue("\x00\x01\x02"));
        $this->assertInstanceOf(BlobDocument::class, $doc);
        $this->assertSame("\x00\x01\x02", $doc->asBlob());
    }

    public function testFromTimestampReturnsTimestampDocument(): void
    {
        $this->assertInstanceOf(
            TimestampDocument::class,
            AbstractDocument::fromTimestamp(1_700_000_000)
        );
    }

    public function testFromTimestampAcceptsTimestampValue(): void
    {
        $doc = AbstractDocument::fromTimestamp(
            new TimestampValue(1_700_000_000)
        );
        $this->assertInstanceOf(TimestampDocument::class, $doc);
        $this->assertSame(1_700_000_000.0, $doc->asTimestamp());
    }

    public function testFromValueNullReturnsNull(): void
    {
        $this->assertNull(AbstractDocument::fromValue(null));
    }

    public function testFromListReturnsListDocument(): void
    {
        $this->assertInstanceOf(
            ListDocument::class,
            AbstractDocument::fromList([])
        );
    }

    public function testFromMapReturnsMapDocument(): void
    {
        $this->assertInstanceOf(
            MapDocument::class,
            AbstractDocument::fromMap([])
        );
    }

    public function testFromValueFromString(): void
    {
        $this->assertInstanceOf(
            StringDocument::class,
            AbstractDocument::fromValue('hello')
        );
    }

    public function testFromValueFromInt(): void
    {
        $this->assertInstanceOf(
            IntegerDocument::class,
            AbstractDocument::fromValue(42)
        );
    }

    public function testFromValueFromFloatGivesDouble(): void
    {
        $this->assertInstanceOf(
            DoubleDocument::class,
            AbstractDocument::fromValue(3.14)
        );
    }

    public function testFromValueFromBool(): void
    {
        $this->assertInstanceOf(
            BooleanDocument::class,
            AbstractDocument::fromValue(true)
        );
    }

    public function testFromValueFromNull(): void
    {
        $this->assertNull(AbstractDocument::fromValue(null));
    }

    public function testFromValueFromListArrayGivesList(): void
    {
        $doc = AbstractDocument::fromValue(['a', 'b', 1]);
        $this->assertInstanceOf(ListDocument::class, $doc);
        $items = $doc->asList();
        $this->assertCount(3, $items);
        $this->assertSame('a', $items[0]->asString());
        $this->assertSame(1, $items[2]->asInteger());
    }

    public function testFromValueFromAssocArrayGivesMap(): void
    {
        $doc = AbstractDocument::fromValue(['name' => 'Sam', 'age' => 40]);
        $this->assertInstanceOf(MapDocument::class, $doc);
        $this->assertSame('Sam', $doc->getMember('name')->asString());
        $this->assertSame(40, $doc->getMember('age')->asInteger());
    }

    public function testFromValueFromStdClass(): void
    {
        $doc = AbstractDocument::fromValue(
            (object) ['name' => 'Sam', 'age' => 40]
        );
        $this->assertInstanceOf(MapDocument::class, $doc);
        $this->assertSame('Sam', $doc->getMember('name')->asString());
    }

    public function testFromValueRecursesIntoNestedStructure(): void
    {
        $doc = AbstractDocument::fromValue([
            'tags' => ['x', null, 'y'],
            'meta' => ['active' => true, 'version' => 3, 'note' => null]
        ]);
        $tags = $doc->getMember('tags');
        $this->assertInstanceOf(ListDocument::class, $tags);
        $this->assertNull($tags->asList()[1]);
        $this->assertSame('y', $tags->asList()[2]->asString());
        $meta = $doc->getMember('meta');
        $this->assertInstanceOf(MapDocument::class, $meta);
        $this->assertTrue($meta->getMember('active')->asBoolean());
        $this->assertNull($meta->getMember('note'));
    }

    public function testFromValueRecognizesBlobValueWrapper(): void
    {
        $doc = AbstractDocument::fromValue(new BlobValue("\x00\x01"));
        $this->assertInstanceOf(BlobDocument::class, $doc);
        $this->assertSame("\x00\x01", $doc->asBlob());
    }

    public function testFromValueRecognizesTimestampValueWrapper(): void
    {
        $doc = AbstractDocument::fromValue(new TimestampValue(1_700_000_000));
        $this->assertInstanceOf(TimestampDocument::class, $doc);
        $this->assertSame(1_700_000_000.0, $doc->asTimestamp());
    }

    public function testFromValueRecognizesBigInteger(): void
    {
        $doc = AbstractDocument::fromValue(
            new BigInteger('99999999999999999999999')
        );
        $this->assertInstanceOf(BigIntegerDocument::class, $doc);
    }

    public function testFromValueRecognizesBigDecimal(): void
    {
        $doc = AbstractDocument::fromValue(new BigDecimal('1.234e-10'));
        $this->assertInstanceOf(BigDecimalDocument::class, $doc);
    }

    public function testFromValueReturnsExistingDocumentUnchanged(): void
    {
        $existing = AbstractDocument::fromString('hello');
        $this->assertSame($existing, AbstractDocument::fromValue($existing));
    }

    public function testFromValueRejectsResource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches(
            '/Cannot build a document from value of type/'
        );
        AbstractDocument::fromValue(\fopen('php://memory', 'r'));
    }

    public function testFromValueRejectsUnknownObjectClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches(
            '/Cannot build a document from value of class/'
        );
        AbstractDocument::fromValue(new \DateTimeImmutable());
    }

    public function testSchemaIsCarriedForward(): void
    {
        $schema = new Schema(id: 'com.example#Foo', type: ShapeType::String_);
        $doc = AbstractDocument::fromString('hello', $schema);
        $this->assertSame($schema, $doc->schema());
        $this->assertSame('com.example#Foo', $doc->discriminator());
    }

    public function testDiscriminatorIsNullForUntypedDocuments(): void
    {
        $this->assertNull(AbstractDocument::fromString('hello')->discriminator());
    }

    public function testSchemaAccessorReturnsNullWhenAbsent(): void
    {
        $this->assertNull(AbstractDocument::fromString('hello')->schema());
    }
}
