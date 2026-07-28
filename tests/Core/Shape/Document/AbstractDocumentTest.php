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
use Smithy\Core\Shape\Document\NullDocument;
use Smithy\Core\Shape\Document\ShortDocument;
use Smithy\Core\Shape\Document\StringDocument;
use Smithy\Core\Shape\Document\TimestampDocument;
use Smithy\Core\Shape\Document\TimestampValue;

/**
 * Covers `AbstractDocument`'s factory routing, `ofObject()` recursive coercion, schema attachment,
 * and unsupported-type rejection. Per-concrete accessor and serialization behavior lives in each
 * concrete's own test file.
 */
#[CoversClass(AbstractDocument::class)]
final class AbstractDocumentTest extends TestCase
{
    // -- Static factories dispatch to the right concrete ---------------------

    public function testOfStringReturnsStringDocument(): void
    {
        $this->assertInstanceOf(
            StringDocument::class,
            AbstractDocument::ofString('hello')
        );
    }

    public function testOfBooleanReturnsBooleanDocument(): void
    {
        $this->assertInstanceOf(
            BooleanDocument::class,
            AbstractDocument::ofBoolean(true)
        );
    }

    public function testOfIntegerReturnsIntegerDocument(): void
    {
        $this->assertInstanceOf(
            IntegerDocument::class,
            AbstractDocument::ofInteger(42)
        );
    }

    public function testOfLongReturnsLongDocument(): void
    {
        $this->assertInstanceOf(
            LongDocument::class,
            AbstractDocument::ofLong(2_147_483_648)
        );
    }

    public function testOfShortReturnsShortDocument(): void
    {
        $this->assertInstanceOf(
            ShortDocument::class,
            AbstractDocument::ofShort(100)
        );
    }

    public function testOfByteReturnsByteDocument(): void
    {
        $this->assertInstanceOf(
            ByteDocument::class,
            AbstractDocument::ofByte(7)
        );
    }

    public function testOfFloatReturnsFloatDocument(): void
    {
        $this->assertInstanceOf(
            FloatDocument::class,
            AbstractDocument::ofFloat(3.14)
        );
    }

    public function testOfDoubleReturnsDoubleDocument(): void
    {
        $this->assertInstanceOf(
            DoubleDocument::class,
            AbstractDocument::ofDouble(2.71828)
        );
    }

    public function testOfBigIntegerReturnsBigIntegerDocument(): void
    {
        $doc = AbstractDocument::ofBigInteger(
            new BigInteger('12345678901234567890')
        );
        $this->assertInstanceOf(BigIntegerDocument::class, $doc);
    }

    public function testOfBigDecimalReturnsBigDecimalDocument(): void
    {
        $doc = AbstractDocument::ofBigDecimal(
            new BigDecimal('3.14159265358979323846')
        );
        $this->assertInstanceOf(BigDecimalDocument::class, $doc);
    }

    public function testOfBlobReturnsBlobDocument(): void
    {
        $this->assertInstanceOf(
            BlobDocument::class,
            AbstractDocument::ofBlob("\x00\x01\x02")
        );
    }

    public function testOfTimestampReturnsTimestampDocument(): void
    {
        $this->assertInstanceOf(
            TimestampDocument::class,
            AbstractDocument::ofTimestamp(1_700_000_000)
        );
    }

    public function testOfNullReturnsNullDocument(): void
    {
        $this->assertInstanceOf(
            NullDocument::class,
            AbstractDocument::ofNull()
        );
    }

    public function testOfListReturnsListDocument(): void
    {
        $this->assertInstanceOf(
            ListDocument::class,
            AbstractDocument::ofList([])
        );
    }

    public function testOfMapReturnsMapDocument(): void
    {
        $this->assertInstanceOf(
            MapDocument::class,
            AbstractDocument::ofMap([])
        );
    }

    // -- ofObject dispatch on PHP runtime type -------------------------------

    public function testOfObjectFromString(): void
    {
        $this->assertInstanceOf(
            StringDocument::class,
            AbstractDocument::ofObject('hello')
        );
    }

    public function testOfObjectFromInt(): void
    {
        $this->assertInstanceOf(
            IntegerDocument::class,
            AbstractDocument::ofObject(42)
        );
    }

    public function testOfObjectFromFloatGivesDouble(): void
    {
        $this->assertInstanceOf(
            DoubleDocument::class,
            AbstractDocument::ofObject(3.14)
        );
    }

    public function testOfObjectFromBool(): void
    {
        $this->assertInstanceOf(
            BooleanDocument::class,
            AbstractDocument::ofObject(true)
        );
    }

    public function testOfObjectFromNull(): void
    {
        $this->assertInstanceOf(
            NullDocument::class,
            AbstractDocument::ofObject(null)
        );
    }

    public function testOfObjectFromListArrayGivesList(): void
    {
        $doc = AbstractDocument::ofObject(['a', 'b', 1]);
        $this->assertInstanceOf(ListDocument::class, $doc);
        $items = $doc->asList();
        $this->assertCount(3, $items);
        $this->assertSame('a', $items[0]->asString());
        $this->assertSame(1, $items[2]->asInteger());
    }

    public function testOfObjectFromAssocArrayGivesMap(): void
    {
        $doc = AbstractDocument::ofObject(['name' => 'Sam', 'age' => 40]);
        $this->assertInstanceOf(MapDocument::class, $doc);
        $this->assertSame('Sam', $doc->getMember('name')->asString());
        $this->assertSame(40, $doc->getMember('age')->asInteger());
    }

    public function testOfObjectFromStdClass(): void
    {
        $doc = AbstractDocument::ofObject(
            (object) ['name' => 'Sam', 'age' => 40]
        );
        $this->assertInstanceOf(MapDocument::class, $doc);
        $this->assertSame('Sam', $doc->getMember('name')->asString());
    }

    public function testOfObjectRecursesIntoNestedStructure(): void
    {
        $doc = AbstractDocument::ofObject([
            'tags' => ['x', 'y'],
            'meta' => ['active' => true, 'version' => 3]
        ]);
        $tags = $doc->getMember('tags');
        $this->assertInstanceOf(ListDocument::class, $tags);
        $this->assertSame('y', $tags->asList()[1]->asString());
        $meta = $doc->getMember('meta');
        $this->assertInstanceOf(MapDocument::class, $meta);
        $this->assertTrue($meta->getMember('active')->asBoolean());
    }

    public function testOfObjectRecognizesBlobValueWrapper(): void
    {
        $doc = AbstractDocument::ofObject(new BlobValue("\x00\x01"));
        $this->assertInstanceOf(BlobDocument::class, $doc);
        $this->assertSame("\x00\x01", $doc->asBlob());
    }

    public function testOfObjectRecognizesTimestampValueWrapper(): void
    {
        $doc = AbstractDocument::ofObject(new TimestampValue(1_700_000_000));
        $this->assertInstanceOf(TimestampDocument::class, $doc);
        $this->assertSame(1_700_000_000.0, $doc->asTimestamp());
    }

    public function testOfObjectRecognizesBigInteger(): void
    {
        $doc = AbstractDocument::ofObject(
            new BigInteger('99999999999999999999999')
        );
        $this->assertInstanceOf(BigIntegerDocument::class, $doc);
    }

    public function testOfObjectRecognizesBigDecimal(): void
    {
        $doc = AbstractDocument::ofObject(new BigDecimal('1.234e-10'));
        $this->assertInstanceOf(BigDecimalDocument::class, $doc);
    }

    public function testOfObjectReturnsExistingDocumentUnchanged(): void
    {
        $existing = AbstractDocument::ofString('hello');
        $this->assertSame($existing, AbstractDocument::ofObject($existing));
    }

    public function testOfObjectRejectsResource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches(
            '/Cannot build a document from value of type/'
        );
        AbstractDocument::ofObject(\fopen('php://memory', 'r'));
    }

    public function testOfObjectRejectsUnknownObjectClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches(
            '/Cannot build a document from value of class/'
        );
        AbstractDocument::ofObject(new \DateTimeImmutable());
    }

    // -- Schema attachment forwards to discriminator -------------------------

    public function testSchemaIsCarriedForward(): void
    {
        $schema = new Schema(id: 'com.example#Foo', type: ShapeType::String_);
        $doc = AbstractDocument::ofString('hello', $schema);
        $this->assertSame($schema, $doc->schema());
        $this->assertSame('com.example#Foo', $doc->discriminator());
    }

    public function testDiscriminatorIsNullForUntypedDocuments(): void
    {
        $this->assertNull(AbstractDocument::ofString('hello')->discriminator());
    }

    public function testSchemaAccessorReturnsNullWhenAbsent(): void
    {
        $this->assertNull(AbstractDocument::ofString('hello')->schema());
    }
}
