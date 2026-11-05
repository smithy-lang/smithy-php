<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Utils;

use JsonSerializable;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\SensitiveStringableTrait;

final class SensitiveStructFixture implements
    SerializableStructInterface,
    JsonSerializable
{
    use SensitiveStringableTrait;

    /**
     * @param array<string, mixed> $values
     */
    public function __construct(
        private readonly Schema $schema,
        public readonly array $values
    ) {}

    public function getSchema(): Schema
    {
        return $this->schema;
    }

    public function serialize(
        ShapeSerializerInterface $serializer,
        int $idx
    ): void
    {}

    public function serializeMembers(
        ShapeSerializerInterface $serializer
    ): void
    {}

    public function getMemberValue(Schema $member): mixed
    {
        return $this->values[$member->memberName] ?? null;
    }

    public function withMemberValue(Schema $member, mixed $value): static
    {
        $values = $this->values;
        $values[$member->memberName] = $value;

        return new self($this->schema, $values);
    }
}
