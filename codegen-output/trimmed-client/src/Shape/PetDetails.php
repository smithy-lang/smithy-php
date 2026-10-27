<?php

declare(strict_types=1);

namespace Smithy\CodegenOutput\TrimmedPetStore\Shape;

use Smithy\CodegenOutput\TrimmedPetStore\Schemas;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\Core\Shape\ShapeType;

final class PetDetails implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'smithy.example#PetDetails';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_BREED;

    public function __construct(
        public readonly ?string $breed = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::petDetails();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_BREED = $m[0];
        return self::$SCHEMA = $s;
    }

    /**
     * Get the shape schema for this instance.
     */
    public function getSchema(): Schema
    {
        return self::schema();
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
        if (isset($this->breed)) {
            $serializer->serializeString(self::$SCHEMA_BREED, $this->breed, 0);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->breed,
            default => null,
        };
    }

}
