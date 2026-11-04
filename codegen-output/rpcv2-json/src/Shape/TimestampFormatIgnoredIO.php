<?php

declare(strict_types=1);

namespace Smithy\ProtocolTests\RpcV2Json\Shape;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RpcV2Json\Schemas;

final class TimestampFormatIgnoredIO implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'smithy.protocoltests.rpcv2Json#TimestampFormatIgnoredIO';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_DATE_TIME;
    private static Schema $SCHEMA_HTTP_DATE;
    private static Schema $SCHEMA_EPOCH_SECONDS;
    private static Schema $SCHEMA_NORMAL;

    public function __construct(
        public readonly ?float $dateTime = null,
        public readonly ?float $httpDate = null,
        public readonly ?float $epochSeconds = null,
        public readonly ?float $normal = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::timestampFormatIgnoredIO();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_DATE_TIME = $m[0];
        self::$SCHEMA_HTTP_DATE = $m[1];
        self::$SCHEMA_EPOCH_SECONDS = $m[2];
        self::$SCHEMA_NORMAL = $m[3];
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
        if (isset($this->dateTime)) {
            $serializer->serializeTimestamp(self::$SCHEMA_DATE_TIME, $this->dateTime, 0);
        }
        if (isset($this->httpDate)) {
            $serializer->serializeTimestamp(self::$SCHEMA_HTTP_DATE, $this->httpDate, 1);
        }
        if (isset($this->epochSeconds)) {
            $serializer->serializeTimestamp(self::$SCHEMA_EPOCH_SECONDS, $this->epochSeconds, 2);
        }
        if (isset($this->normal)) {
            $serializer->serializeTimestamp(self::$SCHEMA_NORMAL, $this->normal, 3);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->dateTime,
            1 => $this->httpDate,
            2 => $this->epochSeconds,
            3 => $this->normal,
            default => null,
        };
    }

}
