<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Fake;

use Smithy\Core\Serde\{
    Schema,
    SerializableStructInterface,
    ShapeSerializerInterface
};
use Smithy\Core\Shape\ShapeType;

/**
 * Minimal input shape for pipeline tests. Carries a single string field
 * so interceptors can assert values pre- and post-modification.
 */
final class FakeInput implements SerializableStructInterface
{
    use \Smithy\Core\Shape\WithMemberValueTrait;
    private static ?Schema $schema = null;

    public function __construct(public readonly string $value = 'v0') {}

    public static function schema(): Schema
    {
        return self::$schema ??= new Schema(
            id: 'smithy.tests#FakeInput',
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
        // Body-less struct; the fake protocol ignores this.
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
        return new self();
    }
}
