<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Protocol\Utils;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;

final class HttpBindingPayloadShapeFixture implements SerializableStructInterface
{
    use FromMembersTrait;

    public function __construct(
        public readonly ?string $value = null
    )
    {
        self::schema();
    }

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        return HttpBindingFixtureSchemas::payloadShape();
    }

    /**
     * Serialize the shape to the desired wire format.
     *
     * @return void
     */
    public function getSchema(): Schema
    {
        return self::schema();
    }

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
        if ($this->value !== null) {
            $serializer->serializeString(
                self::schema()->getMemberByIndex(0),
                $this->value,
                0
            );
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->value,
            default => null
        };
    }
}
