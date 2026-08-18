<?php

declare(strict_types=1);

namespace Smithy\Tests\Protocol\Utils;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;

final class HttpBindingInputShapeFixture implements SerializableStructInterface
{
    use FromMembersTrait;

    /**
     * @param array<string, string>|null $metadata
     * @param array<string, mixed>|null $extra
     */
    public function __construct(
        public readonly ?string $header = null,
        public readonly ?bool $mode = null,
        public readonly ?string $id = null,
        public readonly ?array $metadata = null,
        public readonly ?array $extra = null,
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
        return HttpBindingFixtureSchemas::request();
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
        if ($this->header !== null) {
            $serializer->serializeString($members[0], $this->header, 0);
        }
        if ($this->mode !== null) {
            $serializer->serializeBoolean($members[1], $this->mode, 1);
        }
        if ($this->id !== null) {
            $serializer->serializeString($members[2], $this->id, 2);
        }
        if ($this->metadata !== null) {
            $serializer->serializeMap($members[3], $this->metadata, 3);
        }
        if ($this->extra !== null) {
            $serializer->serializeMap($members[4], $this->extra, 4);
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
            0 => $this->header,
            1 => $this->mode,
            2 => $this->id,
            3 => $this->metadata,
            4 => $this->extra,
            5 => $this->body,
            default => null
        };
    }
}
