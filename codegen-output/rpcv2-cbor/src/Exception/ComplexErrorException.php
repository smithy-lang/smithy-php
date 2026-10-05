<?php

declare(strict_types=1);

namespace Smithy\ProtocolTests\RpcV2Cbor\Exception;

use Smithy\Core\Exception\ApiException;
use Smithy\Core\Exception\ErrorFault;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RpcV2Cbor\Schemas;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\ComplexNestedErrorData;

/**
 * This error is thrown when a request is invalid.
 */
final class ComplexErrorException extends ApiException implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'smithy.protocoltests.rpcv2Cbor#ComplexError';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_TOP_LEVEL;
    private static Schema $SCHEMA_NESTED;

    public function __construct(
        public readonly ?string $topLevel = null,
        public readonly ?ComplexNestedErrorData $nested = null
    ) {
        parent::__construct('', ErrorFault::Client);
    }

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::complexErrorException();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_TOP_LEVEL = $m[0];
        self::$SCHEMA_NESTED = $m[1];
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
        if (isset($this->topLevel)) {
            $serializer->serializeString(self::$SCHEMA_TOP_LEVEL, $this->topLevel, 0);
        }
        if (isset($this->nested)) {
            $serializer->serializeStruct(self::$SCHEMA_NESTED, $this->nested, 1);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->topLevel,
            1 => $this->nested,
            default => null,
        };
    }

}
