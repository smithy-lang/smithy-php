<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Document;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Serde\ShapeType;
use Smithy\Core\Shape\Document\UnionDocument;

#[CoversClass(UnionDocument::class)]
final class UnionDocumentTest extends TestCase
{
    public function testTypeIsUnion(): void
    {
        $doc = UnionDocument::create($this->schema(), 'value', 'hello');
        $this->assertSame(ShapeType::Union, $doc->type());
    }

    public function testExposesActiveMemberAsDocument(): void
    {
        $doc = UnionDocument::create($this->schema(), 'value', 'hello');

        $this->assertSame(0, $doc->getMemberIndex());
        $this->assertSame('value', $doc->tag());
        $this->assertSame('hello', $doc->value());
        $member = $doc->getMember('value');
        $this->assertSame('hello', $member->asString());
        $this->assertSame($member, $doc->getMember('value'));
        $this->assertNull($doc->getMember('other'));
    }

    public function testExplicitSchemaIsCarriedForward(): void
    {
        $schema = $this->schema();
        $doc = UnionDocument::create($schema, 'value', 'hello');
        $this->assertSame($schema, $doc->schema());
    }

    public function testSerializeDispatchesToSerializeUnion(): void
    {
        $schema = $this->schema();
        $doc = UnionDocument::create($schema, 'value', 'hello');
        $serializer = $this->createMock(ShapeSerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serializeUnion')
            ->with($schema, $doc, 2);

        $doc->serialize($serializer, 2);
    }

    private function schema(): Schema
    {
        return new Schema(
            id: 'com.example#Union',
            type: ShapeType::Union,
            members: [
                new Schema(
                    id: 'com.example#String',
                    type: ShapeType::String_,
                    memberName: 'value',
                    memberIndex: 0
                )
            ]
        );
    }
}
