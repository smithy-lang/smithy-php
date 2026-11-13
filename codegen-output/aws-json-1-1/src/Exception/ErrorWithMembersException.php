<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\AwsJson1_1\Exception;

use Smithy\Core\Exception\ErrorFault;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\AwsJson1_1\Schemas;
use Smithy\ProtocolTests\AwsJson1_1\Shape\KitchenSink;

final class ErrorWithMembersException extends AwsJson1_1Exception implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.json#ErrorWithMembers';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_CODE;
    private static Schema $SCHEMA_COMPLEX_DATA;
    private static Schema $SCHEMA_INTEGER_FIELD;
    private static Schema $SCHEMA_LIST_FIELD;
    private static Schema $SCHEMA_MAP_FIELD;
    private static Schema $SCHEMA_MESSAGE;
    private static Schema $SCHEMA_STRING_FIELD;

    private readonly ?string $modeledCode;
    private readonly ?string $modeledMessage;

    /**
     * @param list<string>|null $listField
     * @param array<string, string>|null $mapField
     * @param ?string $stringField abc
     */
    public function __construct(
        ?string $code = null,
        public readonly ?KitchenSink $complexData = null,
        public readonly ?int $integerField = null,
        public readonly ?array $listField = null,
        public readonly ?array $mapField = null,
        ?string $message = null,
        public readonly ?string $stringField = null
    ) {
        parent::__construct($message ?? '', ErrorFault::Client);
        $this->modeledCode = $code;
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
        $s = Schemas::errorWithMembersException();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_CODE = $m[0];
        self::$SCHEMA_COMPLEX_DATA = $m[1];
        self::$SCHEMA_INTEGER_FIELD = $m[2];
        self::$SCHEMA_LIST_FIELD = $m[3];
        self::$SCHEMA_MAP_FIELD = $m[4];
        self::$SCHEMA_MESSAGE = $m[5];
        self::$SCHEMA_STRING_FIELD = $m[6];
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
        if (isset($this->modeledCode)) {
            $serializer->serializeString(self::$SCHEMA_CODE, $this->modeledCode, 0);
        }
        if (isset($this->complexData)) {
            $serializer->serializeStruct(self::$SCHEMA_COMPLEX_DATA, $this->complexData, 1);
        }
        if (isset($this->integerField)) {
            $serializer->serializeInteger(self::$SCHEMA_INTEGER_FIELD, $this->integerField, 2);
        }
        if (isset($this->listField)) {
            $serializer->serializeList(self::$SCHEMA_LIST_FIELD, $this->listField, 3);
        }
        if (isset($this->mapField)) {
            $serializer->serializeMap(self::$SCHEMA_MAP_FIELD, $this->mapField, 4);
        }
        if (isset($this->modeledMessage)) {
            $serializer->serializeString(self::$SCHEMA_MESSAGE, $this->modeledMessage, 5);
        }
        if (isset($this->stringField)) {
            $serializer->serializeString(self::$SCHEMA_STRING_FIELD, $this->stringField, 6);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->modeledCode,
            1 => $this->complexData,
            2 => $this->integerField,
            3 => $this->listField,
            4 => $this->mapField,
            5 => $this->modeledMessage,
            6 => $this->stringField,
            default => null,
        };
    }

}
