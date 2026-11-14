<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestJson\Output;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\ProtocolTests\RestJson\Schemas;
use Smithy\ProtocolTests\RestJson\Shape\EventStream;

final class OutputStreamWithInitialResponseOutput implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.restjson#OutputStreamWithInitialResponseOutput';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_INITIAL_RESPONSE_MEMBER;
    private static Schema $SCHEMA_STREAM;

    public function __construct(
        public readonly ?string $initialResponseMember = null,
        public readonly ?EventStream $stream = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::outputStreamWithInitialResponseOutput();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_INITIAL_RESPONSE_MEMBER = $m[0];
        self::$SCHEMA_STREAM = $m[1];
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
        if (isset($this->initialResponseMember)) {
            $serializer->serializeString(self::$SCHEMA_INITIAL_RESPONSE_MEMBER, $this->initialResponseMember, 0);
        }
        if (isset($this->stream)) {
            $serializer->serializeUnion(self::$SCHEMA_STREAM, $this->stream, 1);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->initialResponseMember,
            1 => $this->stream,
            default => null,
        };
    }

    /**
     * Return a copy with one member replaced.
     */
    public function withMemberValue(Schema $member, mixed $value): static
    {
        $members = [
            $this->initialResponseMember,
            $this->stream
        ];
        $members[$member->memberIndex] = $value;

        return new self(...$members);
    }

}
