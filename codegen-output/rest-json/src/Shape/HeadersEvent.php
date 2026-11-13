<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestJson\Shape;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RestJson\Schemas;

final class HeadersEvent implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.restjson#HeadersEvent';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_BOOLEAN_HEADER;
    private static Schema $SCHEMA_BYTE_HEADER;
    private static Schema $SCHEMA_SHORT_HEADER;
    private static Schema $SCHEMA_INT_HEADER;
    private static Schema $SCHEMA_LONG_HEADER;
    private static Schema $SCHEMA_BLOB_HEADER;
    private static Schema $SCHEMA_STRING_HEADER;
    private static Schema $SCHEMA_TIMESTAMP_HEADER;

    public function __construct(
        public readonly ?bool $booleanHeader = null,
        public readonly ?int $byteHeader = null,
        public readonly ?int $shortHeader = null,
        public readonly ?int $intHeader = null,
        public readonly ?int $longHeader = null,
        public readonly ?string $blobHeader = null,
        public readonly ?string $stringHeader = null,
        public readonly ?float $timestampHeader = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::headersEvent();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_BOOLEAN_HEADER = $m[0];
        self::$SCHEMA_BYTE_HEADER = $m[1];
        self::$SCHEMA_SHORT_HEADER = $m[2];
        self::$SCHEMA_INT_HEADER = $m[3];
        self::$SCHEMA_LONG_HEADER = $m[4];
        self::$SCHEMA_BLOB_HEADER = $m[5];
        self::$SCHEMA_STRING_HEADER = $m[6];
        self::$SCHEMA_TIMESTAMP_HEADER = $m[7];
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
        if (isset($this->booleanHeader)) {
            $serializer->serializeBoolean(self::$SCHEMA_BOOLEAN_HEADER, $this->booleanHeader, 0);
        }
        if (isset($this->byteHeader)) {
            $serializer->serializeInteger(self::$SCHEMA_BYTE_HEADER, $this->byteHeader, 1);
        }
        if (isset($this->shortHeader)) {
            $serializer->serializeInteger(self::$SCHEMA_SHORT_HEADER, $this->shortHeader, 2);
        }
        if (isset($this->intHeader)) {
            $serializer->serializeInteger(self::$SCHEMA_INT_HEADER, $this->intHeader, 3);
        }
        if (isset($this->longHeader)) {
            $serializer->serializeInteger(self::$SCHEMA_LONG_HEADER, $this->longHeader, 4);
        }
        if (isset($this->blobHeader)) {
            $serializer->serializeBlob(self::$SCHEMA_BLOB_HEADER, $this->blobHeader, 5);
        }
        if (isset($this->stringHeader)) {
            $serializer->serializeString(self::$SCHEMA_STRING_HEADER, $this->stringHeader, 6);
        }
        if (isset($this->timestampHeader)) {
            $serializer->serializeTimestamp(self::$SCHEMA_TIMESTAMP_HEADER, $this->timestampHeader, 7);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->booleanHeader,
            1 => $this->byteHeader,
            2 => $this->shortHeader,
            3 => $this->intHeader,
            4 => $this->longHeader,
            5 => $this->blobHeader,
            6 => $this->stringHeader,
            7 => $this->timestampHeader,
            default => null,
        };
    }

}
