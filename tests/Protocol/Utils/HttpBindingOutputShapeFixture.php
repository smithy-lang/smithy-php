<?php

declare(strict_types=1);

namespace Smithy\Tests\Protocol\Utils;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;

final class HttpBindingOutputShapeFixture implements SerializableStructInterface
{
    use FromMembersTrait;

    /**
     * @param list<string>|null $list
     * @param array<string, string>|null $metadata
     */
    public function __construct(
        public readonly ?string $media = null,
        public readonly ?int $count = null,
        public readonly ?array $list = null,
        public readonly ?array $metadata = null,
        public readonly ?int $status = null,
        public readonly ?string $body = null
    )
    {
        self::schema();
    }

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        return HttpBindingFixtureSchemas::response();
    }

    /**
     * Serialize the shape to the desired wire format.
     *
     * @return void
     */
    public function serialize(ShapeSerializerInterface $serializer, int $idx): void
    {
        $serializer->serializeStruct(self::schema(), $this, $idx);
    }

    /**
     * Serialize the shape members to the desired wire format.
     *
     * @return void
     */
    public function serializeMembers(ShapeSerializerInterface $serializer): void
    {
        $members = self::schema()->getMembersByIndex();
        if ($this->media !== null) {
            $serializer->serializeString($members[0], $this->media, 0);
        }
        if ($this->count !== null) {
            $serializer->serializeInteger($members[1], $this->count, 1);
        }
        if ($this->list !== null) {
            $serializer->serializeList($members[2], $this->list, 2);
        }
        if ($this->metadata !== null) {
            $serializer->serializeMap($members[3], $this->metadata, 3);
        }
        if ($this->status !== null) {
            $serializer->serializeInteger($members[4], $this->status, 4);
        }
        if ($this->body !== null) {
            $serializer->serializeString($members[5], $this->body, 5);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->media,
            1 => $this->count,
            2 => $this->list,
            3 => $this->metadata,
            4 => $this->status,
            5 => $this->body,
            default => null
        };
    }
}
