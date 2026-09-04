<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Dynamic\Utils;

use Smithy\Core\Serde\{
    Schema,
    ShapeSerializerInterface
};
use Smithy\Core\Shape\EnumInterface;

/**
 * Schema-backed typed enum used by document-factory tests.
 */
final class EnumFixture implements EnumInterface
{
    public function __construct(
        private readonly Schema $schema,
        private readonly string|int $value
    ) {}

    public function getSchema(): Schema
    {
        return $this->schema;
    }

    public function getValue(): string|int
    {
        return $this->value;
    }

    public function serialize(
        ShapeSerializerInterface $serializer,
        int $idx
    ): void {}
}
