<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Dynamic\Utils;

use Smithy\Core\Serde\{
    Schema,
    SerializableStructInterface,
    ShapeSerializerInterface
};

/**
 * Schema-backed typed structure used by document-factory tests.
 */
final class SerializableStructFixture implements SerializableStructInterface
{
    /** @param array<int, mixed> $members Positional member values. */
    public function __construct(
        private readonly Schema $schema,
        private readonly array $members
    ) {}

    public function getSchema(): Schema
    {
        return $this->schema;
    }

    public function serialize(
        ShapeSerializerInterface $serializer,
        int $idx
    ): void {}

    public function serializeMembers(
        ShapeSerializerInterface $serializer
    ): void {}

    public function getMemberValue(Schema $member): mixed
    {
        return $this->members[$member->memberIndex] ?? null;
    }

    public function withMemberValue(Schema $member, mixed $value): static
    {
        $members = $this->members;
        $members[$member->memberIndex] = $value;

        return new self($this->schema, $members);
    }
}
