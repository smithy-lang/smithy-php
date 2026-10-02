<?php

declare(strict_types=1);

namespace Smithy\ProtocolTests\RpcV2Cbor\Shape;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RpcV2Cbor\Schemas;

final class RecursiveShapesInputOutputNested2 implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'smithy.protocoltests.rpcv2Cbor#RecursiveShapesInputOutputNested2';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_BAR;
    private static Schema $SCHEMA_RECURSIVE_MEMBER;

    public function __construct(
        public readonly ?string $bar = null,
        public readonly ?RecursiveShapesInputOutputNested1 $recursiveMember = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::recursiveShapesInputOutputNested2();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_BAR = $m[0];
        self::$SCHEMA_RECURSIVE_MEMBER = $m[1];
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
        if (isset($this->bar)) {
            $serializer->serializeString(self::$SCHEMA_BAR, $this->bar, 0);
        }
        if (isset($this->recursiveMember)) {
            $serializer->serializeStruct(self::$SCHEMA_RECURSIVE_MEMBER, $this->recursiveMember, 1);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->bar,
            1 => $this->recursiveMember,
            default => null,
        };
    }

}
