<?php

declare(strict_types=1);

namespace Smithy\ProtocolTests\RpcV2Json\Exception;

use Smithy\Core\Exception\ErrorFault;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RpcV2Json\Schemas;

/**
 * A standard error for input validation failures. This should be thrown by services when a member of the input
 * structure falls outside of the modeled or documented constraints.
 */
final class ValidationException extends RpcV2JsonException implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'smithy.framework#ValidationException';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_MESSAGE;
    private static Schema $SCHEMA_FIELD_LIST;

    private readonly ?string $modeledMessage;

    /**
     * @param ?string $message A summary of the validation failure.
     * @param list<\Smithy\ProtocolTests\RpcV2Json\Shape\ValidationExceptionField>|null $fieldList
     */
    public function __construct(
        ?string $message = null,
        public readonly ?array $fieldList = null
    ) {
        parent::__construct($message ?? '', ErrorFault::Client);
        $this->modeledMessage = $message;
    }

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::validationException();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_MESSAGE = $m[0];
        self::$SCHEMA_FIELD_LIST = $m[1];
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
        if (isset($this->modeledMessage)) {
            $serializer->serializeString(self::$SCHEMA_MESSAGE, $this->modeledMessage, 0);
        }
        if (isset($this->fieldList)) {
            $serializer->serializeList(self::$SCHEMA_FIELD_LIST, $this->fieldList, 1);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->modeledMessage,
            1 => $this->fieldList,
            default => null,
        };
    }

}
