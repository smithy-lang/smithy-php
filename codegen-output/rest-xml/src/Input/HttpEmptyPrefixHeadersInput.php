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

final class HttpEmptyPrefixHeadersInput implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.restxml#HttpEmptyPrefixHeadersInput';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_PREFIX_HEADERS;
    private static Schema $SCHEMA_SPECIFIC_HEADER;

    /**
     * @param array<string, string>|null $prefixHeaders
     */
    public function __construct(
        public readonly ?array $prefixHeaders = null,
        public readonly ?string $specificHeader = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::httpEmptyPrefixHeadersInput();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_PREFIX_HEADERS = $m[0];
        self::$SCHEMA_SPECIFIC_HEADER = $m[1];
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
        if (isset($this->prefixHeaders)) {
            $serializer->serializeMap(self::$SCHEMA_PREFIX_HEADERS, $this->prefixHeaders, 0);
        }
        if (isset($this->specificHeader)) {
            $serializer->serializeString(self::$SCHEMA_SPECIFIC_HEADER, $this->specificHeader, 1);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->prefixHeaders,
            1 => $this->specificHeader,
            default => null,
        };
    }

    /**
     * Return a copy with one member replaced.
     */
    public function withMemberValue(Schema $member, mixed $value): static
    {
        $members = [
            $this->prefixHeaders,
            $this->specificHeader
        ];
        $members[$member->memberIndex] = $value;

        return new self(...$members);
    }

}
