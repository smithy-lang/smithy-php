<?php

declare(strict_types=1);

namespace Smithy\CodegenOutput\TrimmedPetStore\Output;

use Smithy\CodegenOutput\TrimmedPetStore\Schemas;
use Smithy\CodegenOutput\TrimmedPetStore\Shape\Pet;
use Smithy\CodegenOutput\TrimmedPetStore\Shape\UnitUnion;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\Core\Shape\ShapeType;

final class GetPetOutput implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'smithy.example#GetPetOutput';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_PET;
    private static Schema $SCHEMA_MARKER;

    public function __construct(
        public readonly ?Pet $pet = null,
        public readonly ?UnitUnion $marker = null
    ) {}

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::getPetOutput();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_PET = $m[0];
        self::$SCHEMA_MARKER = $m[1];
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
        if (isset($this->pet)) {
            $serializer->serializeStruct(self::$SCHEMA_PET, $this->pet, 0);
        }
        if (isset($this->marker)) {
            $serializer->serializeUnion(self::$SCHEMA_MARKER, $this->marker, 1);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->pet,
            1 => $this->marker,
            default => null,
        };
    }

}
