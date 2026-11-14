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

final class TestPayloadStructureInputOutput implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.restjson#TestPayloadStructureInputOutput';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_TEST_ID;
    private static Schema $SCHEMA_PAYLOAD_CONFIG;

    public function __construct(
        public readonly ?string $testId = null,
        public readonly ?PayloadConfig $payloadConfig = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::testPayloadStructureInputOutput();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_TEST_ID = $m[0];
        self::$SCHEMA_PAYLOAD_CONFIG = $m[1];
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
        if (isset($this->testId)) {
            $serializer->serializeString(self::$SCHEMA_TEST_ID, $this->testId, 0);
        }
        if (isset($this->payloadConfig)) {
            $serializer->serializeStruct(self::$SCHEMA_PAYLOAD_CONFIG, $this->payloadConfig, 1);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->testId,
            1 => $this->payloadConfig,
            default => null,
        };
    }

    /**
     * Return a copy with one member replaced.
     */
    public function withMemberValue(Schema $member, mixed $value): static
    {
        $members = [
            $this->testId,
            $this->payloadConfig
        ];
        $members[$member->memberIndex] = $value;

        return new self(...$members);
    }

}
