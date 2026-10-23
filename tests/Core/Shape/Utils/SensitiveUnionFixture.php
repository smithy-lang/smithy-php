<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Utils;

use JsonSerializable;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\SensitiveStringableTrait;
use Smithy\Core\Shape\UnionInterface;

final class SensitiveUnionFixture implements UnionInterface, JsonSerializable
{
    use SensitiveStringableTrait;

    public function __construct(
        private readonly Schema $schema,
        private readonly string $activeTag,
        private readonly int $activeIndex,
        public readonly mixed $activeValue
    ) {}

    public function getSchema(): Schema
    {
        return $this->schema;
    }

    public function getMemberIndex(): int
    {
        return $this->activeIndex;
    }

    public function tag(): string
    {
        return $this->activeTag;
    }

    public function value(): mixed
    {
        return $this->activeValue;
    }

    public function serialize(
        ShapeSerializerInterface $serializer,
        int $idx
    ): void
    {}
}
