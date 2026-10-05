<?php

declare(strict_types=1);

namespace Smithy\ProtocolTests\RpcV2Cbor\Shape;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RpcV2Cbor\Schemas;

final class RpcV2CborUnionInputOutput implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'smithy.protocoltests.rpcv2Cbor#RpcV2CborUnionInputOutput';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_CONTENTS;
    private static Schema $SCHEMA_OTHER_VALUE;

    public function __construct(
        public readonly ?RpcV2CborUnion $contents = null,
        public readonly ?string $otherValue = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::rpcV2CborUnionInputOutput();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_CONTENTS = $m[0];
        self::$SCHEMA_OTHER_VALUE = $m[1];
        return self::$SCHEMA = $s;
    }

    /**
     * Get the shape schema for this instance.
     */
    public function getSchema(): Schema
    {
        return self::schema();
    }

    /**
     * Serialize the shape to the desired wire format.
     *
     * @return void
     */
    public function serialize(ShapeSerializerInterface $serializer, int $idx): void
    {
        $serializer->serializeStruct(self::schema(), $this, $idx);
    }

    /**
     * Serialize the shape members to the desired wire format.
     *
     * @return void
     */
    public function serializeMembers(ShapeSerializerInterface $serializer): void
    {
        if (isset($this->contents)) {
            $serializer->serializeUnion(self::$SCHEMA_CONTENTS, $this->contents, 0);
        }
        if (isset($this->otherValue)) {
            $serializer->serializeString(self::$SCHEMA_OTHER_VALUE, $this->otherValue, 1);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->contents,
            1 => $this->otherValue,
            default => null,
        };
    }

}
