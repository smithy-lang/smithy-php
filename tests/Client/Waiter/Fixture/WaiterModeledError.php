<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Waiter\Fixture;

use Smithy\Core\Exception\ApiException;
use Smithy\Core\Serde\{
    Schema,
    SerializableStructInterface,
    ShapeSerializerInterface
};
use Smithy\Core\Shape\ShapeType;
final class WaiterModeledError extends ApiException implements
    SerializableStructInterface
{
    private static ?Schema $schema = null;

    public static function schema(): Schema
    {
        return self::$schema ??= new Schema(
            id: 'smithy.tests#WaiterModeledError',
            type: ShapeType::Structure,
            members: []
        );
    }

    public function getSchema(): Schema
    {
        return self::schema();
    }

    public function serialize(
        ShapeSerializerInterface $serializer,
        int $memberIndex
    ): void
    {
    }

    public function serializeMembers(
        ShapeSerializerInterface $serializer
    ): void
    {
    }

    public function getMemberValue(Schema $member): mixed
    {
        return null;
    }

    public function withMemberValue(Schema $member, mixed $value): static
    {
        return new self(
            $this->getMessage(),
            $this->fault,
            $this->getPrevious()
        );
    }
}
