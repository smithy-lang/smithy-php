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

final class QueryPrecedenceInput implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.restxml#QueryPrecedenceInput';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_FOO;
    private static Schema $SCHEMA_BAZ;

    /**
     * @param array<string, string>|null $baz
     */
    public function __construct(
        public readonly ?string $foo = null,
        public readonly ?array $baz = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::queryPrecedenceInput();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_FOO = $m[0];
        self::$SCHEMA_BAZ = $m[1];
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
        if (isset($this->foo)) {
            $serializer->serializeString(self::$SCHEMA_FOO, $this->foo, 0);
        }
        if (isset($this->baz)) {
            $serializer->serializeMap(self::$SCHEMA_BAZ, $this->baz, 1);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->foo,
            1 => $this->baz,
            default => null,
        };
    }

    /**
     * Return a copy with one member replaced.
     */
    public function withMemberValue(Schema $member, mixed $value): static
    {
        $members = [
            $this->foo,
            $this->baz
        ];
        $members[$member->memberIndex] = $value;

        return new self(...$members);
    }

}
