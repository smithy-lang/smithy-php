<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Utils\Utils;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;

abstract class AbstractArrayShapeSerializerFixture implements SerializableStructInterface
{
    use \Smithy\Core\Shape\WithMemberValueTrait;

    /** @inheritDoc */
    public function getSchema(): Schema
    {
        return static::schema();
    }

    /** @inheritDoc */
    public function serialize(
        ShapeSerializerInterface $serializer,
        int $idx
    ): void {}

    /** @inheritDoc */
    public function serializeMembers(ShapeSerializerInterface $serializer): void {}

    /** @inheritDoc */
    public function getMemberValue(Schema $member): mixed
    {
        $property = \lcfirst($member->memberName);

        return $this->{$property} ?? null;
    }

    /**
     * Reject construction through positional members in these fixtures.
     *
     * @throws \BadMethodCallException Always.
     */
    public static function fromMembers(array $fields): static
    {
        throw new \BadMethodCallException('Not used in these tests.');
    }
}
