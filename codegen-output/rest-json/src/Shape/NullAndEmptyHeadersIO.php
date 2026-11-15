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
use Smithy\ProtocolTests\RestJson\Schemas;

final class NullAndEmptyHeadersIO implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.restjson#NullAndEmptyHeadersIO';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_A;
    private static Schema $SCHEMA_B;
    private static Schema $SCHEMA_C;

    /**
     * @param list<string>|null $c
     */
    public function __construct(
        public readonly ?string $a = null,
        public readonly ?string $b = null,
        public readonly ?array $c = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::nullAndEmptyHeadersIO();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_A = $m[0];
        self::$SCHEMA_B = $m[1];
        self::$SCHEMA_C = $m[2];
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
        if (isset($this->a)) {
            $serializer->serializeString(self::$SCHEMA_A, $this->a, 0);
        }
        if (isset($this->b)) {
            $serializer->serializeString(self::$SCHEMA_B, $this->b, 1);
        }
        if (isset($this->c)) {
            $serializer->serializeList(self::$SCHEMA_C, $this->c, 2);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->a,
            1 => $this->b,
            2 => $this->c,
            default => null,
        };
    }

    /**
     * Return a copy with one member replaced.
     */
    public function withMemberValue(Schema $member, mixed $value): static
    {
        $members = [
            $this->a,
            $this->b,
            $this->c
        ];
        $members[$member->memberIndex] = $value;

        return new self(...$members);
    }

}
