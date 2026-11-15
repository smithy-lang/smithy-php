<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\AwsQuery\Shape;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\ProtocolTests\AwsQuery\Schemas;

final class StructArg implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.query#StructArg';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_STRING_ARG;
    private static Schema $SCHEMA_OTHER_ARG;
    private static Schema $SCHEMA_RECURSIVE_ARG;

    public function __construct(
        public readonly ?string $stringArg = null,
        public readonly ?bool $otherArg = null,
        public readonly ?StructArg $recursiveArg = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::structArg();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_STRING_ARG = $m[0];
        self::$SCHEMA_OTHER_ARG = $m[1];
        self::$SCHEMA_RECURSIVE_ARG = $m[2];
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
        if (isset($this->stringArg)) {
            $serializer->serializeString(self::$SCHEMA_STRING_ARG, $this->stringArg, 0);
        }
        if (isset($this->otherArg)) {
            $serializer->serializeBoolean(self::$SCHEMA_OTHER_ARG, $this->otherArg, 1);
        }
        if (isset($this->recursiveArg)) {
            $serializer->serializeStruct(self::$SCHEMA_RECURSIVE_ARG, $this->recursiveArg, 2);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->stringArg,
            1 => $this->otherArg,
            2 => $this->recursiveArg,
            default => null,
        };
    }

    /**
     * Return a copy with one member replaced.
     */
    public function withMemberValue(Schema $member, mixed $value): static
    {
        $members = [
            $this->stringArg,
            $this->otherArg,
            $this->recursiveArg
        ];
        $members[$member->memberIndex] = $value;

        return new self(...$members);
    }

}
