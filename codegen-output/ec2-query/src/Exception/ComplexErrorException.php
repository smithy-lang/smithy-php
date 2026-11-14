<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\Ec2Query\Exception;

use Smithy\Core\Exception\ErrorFault;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\ProtocolTests\Ec2Query\Schemas;
use Smithy\ProtocolTests\Ec2Query\Shape\ComplexNestedErrorData;

/**
 * This error is thrown when a request is invalid.
 */
final class ComplexErrorException extends Ec2QueryException implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.ec2#ComplexError';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_TOP_LEVEL;
    private static Schema $SCHEMA_NESTED;

    public function __construct(
        public readonly ?string $topLevel = null,
        public readonly ?ComplexNestedErrorData $nested = null
    ) {
        parent::__construct('', ErrorFault::Client);
    }

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::complexErrorException();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_TOP_LEVEL = $m[0];
        self::$SCHEMA_NESTED = $m[1];
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
        if (isset($this->topLevel)) {
            $serializer->serializeString(self::$SCHEMA_TOP_LEVEL, $this->topLevel, 0);
        }
        if (isset($this->nested)) {
            $serializer->serializeStruct(self::$SCHEMA_NESTED, $this->nested, 1);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->topLevel,
            1 => $this->nested,
            default => null,
        };
    }

    /**
     * Return a copy with one member replaced.
     */
    public function withMemberValue(Schema $member, mixed $value): static
    {
        $members = [
            $this->topLevel,
            $this->nested
        ];
        $members[$member->memberIndex] = $value;

        return new self(...$members);
    }

}
