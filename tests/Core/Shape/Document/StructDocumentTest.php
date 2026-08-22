<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Document;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\Document\AbstractDocument;
use Smithy\Core\Shape\Document\IncompatibleDocumentTypeException;
use Smithy\Core\Shape\Document\StructDocument;

#[CoversClass(StructDocument::class)]
final class StructDocumentTest extends TestCase
{
    public function testTypeIsStructure(): void
    {
        $doc = StructDocument::fromMembers($this->schema(), ['hello']);
        $this->assertSame(ShapeType::Structure, $doc->type());
    }

    public function testAsListThrows(): void
    {
        $doc = StructDocument::fromMembers($this->schema(), ['hello']);
        $this->expectException(IncompatibleDocumentTypeException::class);
        $doc->asList();
    }

    public function testExplicitSchemaIsCarriedForward(): void
    {
        $schema = $this->schema();
        $doc = StructDocument::fromMembers($schema, ['hello']);
        $this->assertSame($schema, $doc->schema());
    }

    public function testMembersRemainRawUntilDocumentAccess(): void
    {
        $schema = $this->schema();
        $doc = StructDocument::fromMembers($schema, ['hello']);
        $member = $schema->getMember('value');

        $this->assertSame('hello', $doc->getMemberValue($member));
        $memberDocument = $doc->getMember('value');
        $this->assertSame('hello', $memberDocument->asString());
        $this->assertSame($memberDocument, $doc->getMember('value'));
    }

    public function testSerializeDispatchesToSerializeStruct(): void
    {
        $schema = $this->schema();
        $doc = StructDocument::fromMembers($schema, ['hello']);
        $serializer = $this->createMock(ShapeSerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serializeStruct')
            ->with($schema, $doc, 4);

        $doc->serialize($serializer, 4);
    }

    public function testSerializeMembersDispatchesRawValuesByModeledType(): void
    {
        $schema = new Schema(
            id: 'com.example#Struct',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'com.example#String',
                    type: ShapeType::String_,
                    memberName: 'value',
                    memberIndex: 0
                ),
                new Schema(
                    id: 'com.example#Strings',
                    type: ShapeType::List_,
                    memberName: 'values',
                    memberIndex: 1,
                    listMember: new Schema(
                        id: 'smithy.api#String',
                        type: ShapeType::String_
                    )
                ),
                new Schema(
                    id: 'smithy.api#Document',
                    type: ShapeType::Document,
                    memberName: 'document',
                    memberIndex: 2
                )
            ]
        );
        $members = $schema->getMembersByIndex();
        $document = AbstractDocument::fromValue(['enabled' => true]);
        $doc = StructDocument::fromMembers(
            $schema,
            ['hello', ['one', 'two'], $document]
        );
        $serializer = $this->createMock(ShapeSerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serializeString')
            ->with($members[0], 'hello', 0);
        $serializer->expects($this->once())
            ->method('serializeList')
            ->with($members[1], ['one', 'two'], 1);
        $serializer->expects($this->once())
            ->method('serializeDocument')
            ->with($members[2], $document, 2);

        $doc->serializeMembers($serializer);
    }

    public function testWithMemberValueReturnsCopiedDocument(): void
    {
        $schema = $this->schema();
        $member = $schema->getMember('value');
        $document = StructDocument::fromMembers($schema, ['before']);

        $copy = $document->withMemberValue($member, 'after');

        $this->assertNotSame($document, $copy);
        $this->assertSame('before', $document->getMemberValue($member));
        $this->assertSame('after', $copy->getMemberValue($member));
    }

    private function schema(): Schema
    {
        return new Schema(
            id: 'com.example#Struct',
            type: ShapeType::Structure,
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
