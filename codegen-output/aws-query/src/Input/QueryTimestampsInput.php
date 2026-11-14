<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\AwsQuery\Input;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\ProtocolTests\AwsQuery\Schemas;

final class QueryTimestampsInput implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.query#QueryTimestampsInput';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_NORMAL_FORMAT;
    private static Schema $SCHEMA_EPOCH_MEMBER;
    private static Schema $SCHEMA_EPOCH_TARGET;

    public function __construct(
        public readonly ?float $normalFormat = null,
        public readonly ?float $epochMember = null,
        public readonly ?float $epochTarget = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::queryTimestampsInput();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_NORMAL_FORMAT = $m[0];
        self::$SCHEMA_EPOCH_MEMBER = $m[1];
        self::$SCHEMA_EPOCH_TARGET = $m[2];
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
        if (isset($this->normalFormat)) {
            $serializer->serializeTimestamp(self::$SCHEMA_NORMAL_FORMAT, $this->normalFormat, 0);
        }
        if (isset($this->epochMember)) {
            $serializer->serializeTimestamp(self::$SCHEMA_EPOCH_MEMBER, $this->epochMember, 1);
        }
        if (isset($this->epochTarget)) {
            $serializer->serializeTimestamp(self::$SCHEMA_EPOCH_TARGET, $this->epochTarget, 2);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->normalFormat,
            1 => $this->epochMember,
            2 => $this->epochTarget,
            default => null,
        };
    }

    /**
     * Return a copy with one member replaced.
     */
    public function withMemberValue(Schema $member, mixed $value): static
    {
        $members = [
            $this->normalFormat,
            $this->epochMember,
            $this->epochTarget
        ];
        $members[$member->memberIndex] = $value;

        return new self(...$members);
    }

}
