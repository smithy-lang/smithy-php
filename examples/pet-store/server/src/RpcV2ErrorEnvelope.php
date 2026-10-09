<?php

declare(strict_types=1);

namespace Smithy\Examples\PetStore\Server;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\Core\Shape\ShapeType;

/**
 * RPCv2 error envelope containing the fields used by the PetStore model.
 */
final class RpcV2ErrorEnvelope implements SerializableStructInterface
{
    use FromMembersTrait;

    private static ?Schema $schema = null;

    /**
     * @param array<string, string> $fields
     */
    public function __construct(
        public readonly string $type,
        public readonly string $message,
        public readonly array $fields = []
    ) {}

    public static function schema(): Schema
    {
        return self::$schema ??= new Schema(
            id: 'smithy.example.petstore#RpcV2ErrorEnvelope',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.example.petstore#RpcV2ErrorEnvelope$__type',
                    type: ShapeType::String_,
                    memberName: '__type',
                    memberIndex: 0
                ),
                new Schema(
                    id: 'smithy.example.petstore#RpcV2ErrorEnvelope$message',
                    type: ShapeType::String_,
                    memberName: 'message',
                    memberIndex: 1
                ),
                new Schema(
                    id: 'smithy.example.petstore#RpcV2ErrorEnvelope$petId',
                    type: ShapeType::String_,
                    memberName: 'petId',
                    memberIndex: 2
                ),
                new Schema(
                    id: 'smithy.example.petstore#RpcV2ErrorEnvelope$field',
                    type: ShapeType::String_,
                    memberName: 'field',
                    memberIndex: 3
                )
            ]
        );
    }

    public function getSchema(): Schema
    {
        return self::schema();
    }

    /**
     * @return void
     */
    public function serialize(
        ShapeSerializerInterface $serializer,
        int $idx
    ): void {
        $serializer->serializeStruct(self::schema(), $this, $idx);
    }

    /**
     * @return void
     */
    public function serializeMembers(
        ShapeSerializerInterface $serializer
    ): void {
        $members = self::schema()->getMembersByIndex();
        $serializer->serializeString($members[0], $this->type, 0);
        $serializer->serializeString($members[1], $this->message, 1);

        if (isset($this->fields['petId'])) {
            $serializer->serializeString(
                $members[2],
                $this->fields['petId'],
                2
            );
        }
        if (isset($this->fields['field'])) {
            $serializer->serializeString(
                $members[3],
                $this->fields['field'],
                3
            );
        }
    }

    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->type,
            1 => $this->message,
            2 => $this->fields['petId'] ?? null,
            3 => $this->fields['field'] ?? null,
            default => null
        };
    }
}
