<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestXml\Input;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\ProtocolTests\RestXml\Schemas;
use Smithy\ProtocolTests\RestXml\Shape\XmlAttributesInMiddlePayloadRequest;

final class XmlAttributesInMiddleRequest implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.restxml#XmlAttributesInMiddleRequest';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_PAYLOAD;

    public function __construct(
        public readonly ?XmlAttributesInMiddlePayloadRequest $payload = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::xmlAttributesInMiddleRequest();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_PAYLOAD = $m[0];
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
        if (isset($this->payload)) {
            $serializer->serializeStruct(self::$SCHEMA_PAYLOAD, $this->payload, 0);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->payload,
            default => null,
        };
    }

    /**
     * Return a copy with one member replaced.
     */
    public function withMemberValue(Schema $member, mixed $value): static
    {
        $members = [
            $this->payload
        ];
        $members[$member->memberIndex] = $value;

        return new self(...$members);
    }

}
