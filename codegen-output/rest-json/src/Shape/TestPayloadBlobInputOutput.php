<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestJson\Shape;

use Psr\Http\Message\StreamInterface;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\ProtocolTests\RestJson\Schemas;

final class TestPayloadBlobInputOutput implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.restjson#TestPayloadBlobInputOutput';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_CONTENT_TYPE;
    private static Schema $SCHEMA_DATA;

    public function __construct(
        public readonly ?string $contentType = null,
        public readonly string|StreamInterface|null $data = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::testPayloadBlobInputOutput();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_CONTENT_TYPE = $m[0];
        self::$SCHEMA_DATA = $m[1];
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
        if (isset($this->contentType)) {
            $serializer->serializeString(self::$SCHEMA_CONTENT_TYPE, $this->contentType, 0);
        }
        if (isset($this->data)) {
            $serializer->serializeBlob(self::$SCHEMA_DATA, $this->data, 1);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->contentType,
            1 => $this->data,
            default => null,
        };
    }

    /**
     * Return a copy with one member replaced.
     */
    public function withMemberValue(Schema $member, mixed $value): static
    {
        $members = [
            $this->contentType,
            $this->data
        ];
        $members[$member->memberIndex] = $value;

        return new self(...$members);
    }

}
