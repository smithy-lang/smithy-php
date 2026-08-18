<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Utils\Utils;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;

abstract class AbstractArrayShapeSerializerFixture implements SerializableStructInterface
{
    public function getSchema(): Schema
    {
        return static::schema();
    }

    public function serialize(ShapeSerializerInterface $serializer, int $idx): void
    {
        // Not exercised by these tests — ArrayShapeSerializer drives the
        // schema-walk path, not the codec dispatch path.
    }

    public function serializeMembers(ShapeSerializerInterface $serializer): void {}

    public function getMemberValue(Schema $member): mixed
    {
        return null;
    }

    public static function fromMembers(array $fields): static
    {
        throw new \BadMethodCallException('Not used in these tests.');
    }
}
