<?php

declare(strict_types=1);

namespace Smithy\ProtocolTests\RpcV2Json\Input;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RpcV2Json\Schemas;
use Smithy\ProtocolTests\RpcV2Json\Shape\ClientOptionalDefaults;
use Smithy\ProtocolTests\RpcV2Json\Shape\Defaults;

final class OperationWithDefaultsInput implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsInput';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_DEFAULTS;
    private static Schema $SCHEMA_CLIENT_OPTIONAL_DEFAULTS;
    private static Schema $SCHEMA_TOP_LEVEL_DEFAULT;
    private static Schema $SCHEMA_OTHER_TOP_LEVEL_DEFAULT;

    public function __construct(
        public readonly ?Defaults $defaults = null,
        public readonly ?ClientOptionalDefaults $clientOptionalDefaults = null,
        public readonly ?string $topLevelDefault = null,
        public readonly ?int $otherTopLevelDefault = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::operationWithDefaultsInput();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_DEFAULTS = $m[0];
        self::$SCHEMA_CLIENT_OPTIONAL_DEFAULTS = $m[1];
        self::$SCHEMA_TOP_LEVEL_DEFAULT = $m[2];
        self::$SCHEMA_OTHER_TOP_LEVEL_DEFAULT = $m[3];
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
        if (isset($this->defaults)) {
            $serializer->serializeStruct(self::$SCHEMA_DEFAULTS, $this->defaults, 0);
        }
        if (isset($this->clientOptionalDefaults)) {
            $serializer->serializeStruct(self::$SCHEMA_CLIENT_OPTIONAL_DEFAULTS, $this->clientOptionalDefaults, 1);
        }
        if (isset($this->topLevelDefault)) {
            $serializer->serializeString(self::$SCHEMA_TOP_LEVEL_DEFAULT, $this->topLevelDefault, 2);
        }
        if (isset($this->otherTopLevelDefault)) {
            $serializer->serializeInteger(self::$SCHEMA_OTHER_TOP_LEVEL_DEFAULT, $this->otherTopLevelDefault, 3);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->defaults,
            1 => $this->clientOptionalDefaults,
            2 => $this->topLevelDefault,
            3 => $this->otherTopLevelDefault,
            default => null,
        };
    }

}
