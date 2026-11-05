<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Dynamic\Utils;

use Smithy\Core\Serde\{
    Schema,
    ShapeSerializerInterface
};
use Smithy\Core\Shape\UnionInterface;

/**
 * Schema-backed typed union used by document-factory tests.
 */
final class UnionFixture implements UnionInterface
{
    public function __construct(
        private readonly Schema $schema,
        private readonly string $tag,
        private readonly mixed $value
    ) {}

    public function getSchema(): Schema
    {
        return $this->schema;
    }

    public function getMemberIndex(): int
    {
        return $this->schema->getMember($this->tag)?->memberIndex ?? -1;
    }

    public function tag(): string
    {
        return $this->tag;
    }

    public function value(): mixed
    {
        return $this->value;
    }

    public function serialize(
        ShapeSerializerInterface $serializer,
        int $idx
    ): void {}
}
