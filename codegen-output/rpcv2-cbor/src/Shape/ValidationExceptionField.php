<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RpcV2Cbor\Shape;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RpcV2Cbor\Schemas;

/**
 * Describes one specific validation failure for an input member.
 */
final class ValidationExceptionField implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'smithy.framework#ValidationExceptionField';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_PATH;
    private static Schema $SCHEMA_MESSAGE;

    /**
     * @param ?string $path A JSONPointer expression to the structure member whose value failed to satisfy the modeled
     *        constraints.
     * @param ?string $message A detailed description of the validation failure.
     */
    public function __construct(
        public readonly ?string $path = null,
        public readonly ?string $message = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::validationExceptionField();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_PATH = $m[0];
        self::$SCHEMA_MESSAGE = $m[1];
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
        if (isset($this->path)) {
            $serializer->serializeString(self::$SCHEMA_PATH, $this->path, 0);
        }
        if (isset($this->message)) {
            $serializer->serializeString(self::$SCHEMA_MESSAGE, $this->message, 1);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->path,
            1 => $this->message,
            default => null,
        };
    }

}
