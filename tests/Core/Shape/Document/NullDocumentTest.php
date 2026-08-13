<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Document;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Serde\ShapeType;
use Smithy\Core\Shape\Document\IncompatibleDocumentTypeException;
use Smithy\Core\Shape\Document\NullDocument;

#[CoversClass(NullDocument::class)]
final class NullDocumentTest extends TestCase
{
    public function testTypeIsDocument(): void
    {
        // Smithy has no dedicated null shape; NullDocument surfaces the
        // catch-all Document type so callers can branch on `instanceof`.
        $this->assertSame(ShapeType::Document, (new NullDocument())->type());
    }

    public function testAsStringThrows(): void
    {
        // NullDocument overrides no accessors, so every asXxx() throws via
        // the AbstractDocument default. One sample suffices for coverage.
        $this->expectException(IncompatibleDocumentTypeException::class);
        (new NullDocument())->asString();
    }

    public function testSerializeIsNoOp(): void
    {
        // Codecs that emit an explicit null token detect the type via
        // `instanceof NullDocument` before calling `serialize()`; the
        // method itself writes nothing.
        $serializer = $this->createMock(ShapeSerializerInterface::class);
        $serializer->expects($this->never())->method($this->anything());

        (new NullDocument())->serialize($serializer, 0);
    }
}
