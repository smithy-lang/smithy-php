<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Fake;

use Smithy\Core\Serde\{
    Schema,
    SerializableStructInterface,
    ShapeSerializerInterface
};
use Smithy\Core\Shape\ShapeType;

/**
 * Minimal output shape for pipeline tests.
 */
final class FakeOutput implements SerializableStructInterface
{
    use \Smithy\Core\Shape\WithMemberValueTrait;
    private static ?Schema $schema = null;

    public function __construct(public readonly string $value = 'out') {}

    public static function schema(): Schema
    {
        return self::$schema ??= new Schema(
            id: 'smithy.tests#FakeOutput',
            type: ShapeType::Structure
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

    public static function fromMembers(array $fields): static
    {
        return new self($fields[0] ?? 'out');
    }
}
