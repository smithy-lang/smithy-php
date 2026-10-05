<?php

declare(strict_types=1);

namespace Smithy\ProtocolTests\RpcV2Cbor\Shape;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RpcV2Cbor\Schemas;

final class SimpleScalarStructure implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'smithy.protocoltests.rpcv2Cbor#SimpleScalarStructure';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_TRUE_BOOLEAN_VALUE;
    private static Schema $SCHEMA_FALSE_BOOLEAN_VALUE;
    private static Schema $SCHEMA_BYTE_VALUE;
    private static Schema $SCHEMA_DOUBLE_VALUE;
    private static Schema $SCHEMA_FLOAT_VALUE;
    private static Schema $SCHEMA_INTEGER_VALUE;
    private static Schema $SCHEMA_LONG_VALUE;
    private static Schema $SCHEMA_SHORT_VALUE;
    private static Schema $SCHEMA_STRING_VALUE;
    private static Schema $SCHEMA_BLOB_VALUE;

    public function __construct(
        public readonly ?bool $trueBooleanValue = null,
        public readonly ?bool $falseBooleanValue = null,
        public readonly ?int $byteValue = null,
        public readonly ?float $doubleValue = null,
        public readonly ?float $floatValue = null,
        public readonly ?int $integerValue = null,
        public readonly ?int $longValue = null,
        public readonly ?int $shortValue = null,
        public readonly ?string $stringValue = null,
        public readonly ?string $blobValue = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::simpleScalarStructure();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_TRUE_BOOLEAN_VALUE = $m[0];
        self::$SCHEMA_FALSE_BOOLEAN_VALUE = $m[1];
        self::$SCHEMA_BYTE_VALUE = $m[2];
        self::$SCHEMA_DOUBLE_VALUE = $m[3];
        self::$SCHEMA_FLOAT_VALUE = $m[4];
        self::$SCHEMA_INTEGER_VALUE = $m[5];
        self::$SCHEMA_LONG_VALUE = $m[6];
        self::$SCHEMA_SHORT_VALUE = $m[7];
        self::$SCHEMA_STRING_VALUE = $m[8];
        self::$SCHEMA_BLOB_VALUE = $m[9];
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
        if (isset($this->trueBooleanValue)) {
            $serializer->serializeBoolean(self::$SCHEMA_TRUE_BOOLEAN_VALUE, $this->trueBooleanValue, 0);
        }
        if (isset($this->falseBooleanValue)) {
            $serializer->serializeBoolean(self::$SCHEMA_FALSE_BOOLEAN_VALUE, $this->falseBooleanValue, 1);
        }
        if (isset($this->byteValue)) {
            $serializer->serializeInteger(self::$SCHEMA_BYTE_VALUE, $this->byteValue, 2);
        }
        if (isset($this->doubleValue)) {
            $serializer->serializeFloat(self::$SCHEMA_DOUBLE_VALUE, $this->doubleValue, 3);
        }
        if (isset($this->floatValue)) {
            $serializer->serializeFloat(self::$SCHEMA_FLOAT_VALUE, $this->floatValue, 4);
        }
        if (isset($this->integerValue)) {
            $serializer->serializeInteger(self::$SCHEMA_INTEGER_VALUE, $this->integerValue, 5);
        }
        if (isset($this->longValue)) {
            $serializer->serializeInteger(self::$SCHEMA_LONG_VALUE, $this->longValue, 6);
        }
        if (isset($this->shortValue)) {
            $serializer->serializeInteger(self::$SCHEMA_SHORT_VALUE, $this->shortValue, 7);
        }
        if (isset($this->stringValue)) {
            $serializer->serializeString(self::$SCHEMA_STRING_VALUE, $this->stringValue, 8);
        }
        if (isset($this->blobValue)) {
            $serializer->serializeBlob(self::$SCHEMA_BLOB_VALUE, $this->blobValue, 9);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->trueBooleanValue,
            1 => $this->falseBooleanValue,
            2 => $this->byteValue,
            3 => $this->doubleValue,
            4 => $this->floatValue,
            5 => $this->integerValue,
            6 => $this->longValue,
            7 => $this->shortValue,
            8 => $this->stringValue,
            9 => $this->blobValue,
            default => null,
        };
    }

}
