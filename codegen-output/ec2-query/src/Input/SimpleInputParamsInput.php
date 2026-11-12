<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\Ec2Query\Input;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\Ec2Query\Schemas;
use Smithy\ProtocolTests\Ec2Query\Shape\FooEnum;

final class SimpleInputParamsInput implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.ec2#SimpleInputParamsInput';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_FOO;
    private static Schema $SCHEMA_BAR;
    private static Schema $SCHEMA_BAZ;
    private static Schema $SCHEMA_BAM;
    private static Schema $SCHEMA_FLOAT_VALUE;
    private static Schema $SCHEMA_BOO;
    private static Schema $SCHEMA_QUX;
    private static Schema $SCHEMA_FOO_ENUM;
    private static Schema $SCHEMA_HAS_QUERY_NAME;
    private static Schema $SCHEMA_HAS_QUERY_AND_XML_NAME;
    private static Schema $SCHEMA_USES_XML_NAME;
    private static Schema $SCHEMA_DISTINCT_QUERY_NAME;
    private static Schema $SCHEMA_DISTINCT_QUERY_AND_XML_NAME;
    private static Schema $SCHEMA_DISTINCT_XML_NAME;

    public readonly ?FooEnum $fooEnum;

    public function __construct(
        public readonly ?string $foo = null,
        public readonly ?string $bar = null,
        public readonly ?bool $baz = null,
        public readonly ?int $bam = null,
        public readonly ?float $floatValue = null,
        public readonly ?float $boo = null,
        public readonly ?string $qux = null,
        FooEnum|string|null $fooEnum = null,
        public readonly ?string $hasQueryName = null,
        public readonly ?string $hasQueryAndXmlName = null,
        public readonly ?string $usesXmlName = null,
        public readonly ?string $distinctQueryName = null,
        public readonly ?string $distinctQueryAndXmlName = null,
        public readonly ?string $distinctXmlName = null
    ) {
        if ($fooEnum !== null) {
            $this->fooEnum = $fooEnum instanceof FooEnum ? $fooEnum : FooEnum::fromValue($fooEnum);
        }
    }

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::simpleInputParamsInput();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_FOO = $m[0];
        self::$SCHEMA_BAR = $m[1];
        self::$SCHEMA_BAZ = $m[2];
        self::$SCHEMA_BAM = $m[3];
        self::$SCHEMA_FLOAT_VALUE = $m[4];
        self::$SCHEMA_BOO = $m[5];
        self::$SCHEMA_QUX = $m[6];
        self::$SCHEMA_FOO_ENUM = $m[7];
        self::$SCHEMA_HAS_QUERY_NAME = $m[8];
        self::$SCHEMA_HAS_QUERY_AND_XML_NAME = $m[9];
        self::$SCHEMA_USES_XML_NAME = $m[10];
        self::$SCHEMA_DISTINCT_QUERY_NAME = $m[11];
        self::$SCHEMA_DISTINCT_QUERY_AND_XML_NAME = $m[12];
        self::$SCHEMA_DISTINCT_XML_NAME = $m[13];
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
        if (isset($this->bar)) {
            $serializer->serializeString(self::$SCHEMA_BAR, $this->bar, 1);
        }
        if (isset($this->baz)) {
            $serializer->serializeBoolean(self::$SCHEMA_BAZ, $this->baz, 2);
        }
        if (isset($this->bam)) {
            $serializer->serializeInteger(self::$SCHEMA_BAM, $this->bam, 3);
        }
        if (isset($this->floatValue)) {
            $serializer->serializeFloat(self::$SCHEMA_FLOAT_VALUE, $this->floatValue, 4);
        }
        if (isset($this->boo)) {
            $serializer->serializeFloat(self::$SCHEMA_BOO, $this->boo, 5);
        }
        if (isset($this->qux)) {
            $serializer->serializeBlob(self::$SCHEMA_QUX, $this->qux, 6);
        }
        if (isset($this->fooEnum)) {
            $serializer->serializeString(self::$SCHEMA_FOO_ENUM, $this->fooEnum->value, 7);
        }
        if (isset($this->hasQueryName)) {
            $serializer->serializeString(self::$SCHEMA_HAS_QUERY_NAME, $this->hasQueryName, 8);
        }
        if (isset($this->hasQueryAndXmlName)) {
            $serializer->serializeString(self::$SCHEMA_HAS_QUERY_AND_XML_NAME, $this->hasQueryAndXmlName, 9);
        }
        if (isset($this->usesXmlName)) {
            $serializer->serializeString(self::$SCHEMA_USES_XML_NAME, $this->usesXmlName, 10);
        }
        if (isset($this->distinctQueryName)) {
            $serializer->serializeString(self::$SCHEMA_DISTINCT_QUERY_NAME, $this->distinctQueryName, 11);
        }
        if (isset($this->distinctQueryAndXmlName)) {
            $serializer->serializeString(self::$SCHEMA_DISTINCT_QUERY_AND_XML_NAME, $this->distinctQueryAndXmlName, 12);
        }
        if (isset($this->distinctXmlName)) {
            $serializer->serializeString(self::$SCHEMA_DISTINCT_XML_NAME, $this->distinctXmlName, 13);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->foo,
            1 => $this->bar,
            2 => $this->baz,
            3 => $this->bam,
            4 => $this->floatValue,
            5 => $this->boo,
            6 => $this->qux,
            7 => $this->fooEnum ?? null,
            8 => $this->hasQueryName,
            9 => $this->hasQueryAndXmlName,
            10 => $this->usesXmlName,
            11 => $this->distinctQueryName,
            12 => $this->distinctQueryAndXmlName,
            13 => $this->distinctXmlName,
            default => null,
        };
    }

}
